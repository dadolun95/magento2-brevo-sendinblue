<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Observer\Tracking;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Cart as CartHelper;

/**
 * Class RemoveFromCart
 * @package Dadolun\SibOrderSync\Observer\Tracking
 */
class CreatedCart implements ObserverInterface
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var CartHelper
     */
    protected $cartHelper;

    /**
     * CreatedCart constructor.
     * @param CheckoutSession $checkoutSession
     * @param Session $customerSession
     * @param ImageHelper $imageHelper
     * @param CartHelper $cartHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Session $customerSession,
        ImageHelper $imageHelper,
        CartHelper $cartHelper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->imageHelper = $imageHelper;
        $this->cartHelper = $cartHelper;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        try {
            /**
             * @var Item $quoteItem
             */
            $quoteItem = $observer->getData("quote_item");
            $lastCreatedQuoteId = $this->checkoutSession->getSibLastCreatedQuoteId();
            $quote = $quoteItem->getQuote();
            $customer = $this->customerSession->getCustomer();
            $quoteItemsData = [];
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                /**
                 * @var Product $quoteProduct
                 */
                $quoteProduct = $quoteItem->getProduct();
                $quoteItemsData[] = [
                    'id' => $quoteProduct->getId(),
                    'url' => $quoteProduct->getUrlInStore($quoteProduct, ['_scope' => $quote->getStoreId(), '_nosid' => true]),
                    'name' => $quoteProduct->getName(),
                    'quantity' => $quoteItem->getQty(),
                    'price' => $quoteProduct->getFinalPrice(),
                    'image' => $this->imageHelper->init($quoteProduct, "product_page_image_small")
                        ->setImageFile($quoteProduct->getSmallImage())
                        ->getUrl()
                ];
            }
            if (!$lastCreatedQuoteId || $lastCreatedQuoteId !== $quote->getId()) {
                $this->checkoutSession->setSibLastCreatedQuoteId($quote->getId());
                $quoteData = [
                    'email' => $customer->getEmail(),
                    'event' => 'cart_created',
                    'properties' => array(
                        'FIRSTNAME' => $customer->getFirstname(),
                        'LASTNAME' => $customer->getLastname()
                    ),
                    'eventdata' => array(
                        'id' => $quote->getId(),
                        'data' => [
                            'items' => $quoteItemsData
                        ]
                    )
                ];
                $this->checkoutSession->setSibCreatedQuoteData($quoteData);
            } else {
                $quoteUpdateData = array(
                    'email' => $customer->getEmail(),
                    'event' => 'cart_updated',
                    'properties' => array(
                        'FIRSTNAME' => $customer->getFirstname(),
                        'LASTNAME' => $customer->getLastname()
                    ),
                    'eventdata' => array(
                        'id' => 'cart:' . $quote->getId(),
                        'data' => [
                            'url' => $this->cartHelper->getCartUrl(),
                            'items' => $quoteItemsData
                        ]
                    )
                );
                $this->checkoutSession->setSibUpdatedQuoteData($quoteUpdateData);
            }
        } catch (\Exception $e) {}

        return $this;
    }
}
