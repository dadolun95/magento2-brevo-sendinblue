<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Observer\Tracking;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Cart as CartHelper;

/**
 * Class UpdatedCart
 * @package Dadolun\SibOrderSync\Observer\Tracking
 */
class UpdatedCart implements ObserverInterface
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

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
     * @param ImageHelper $imageHelper
     * @param CartHelper $cartHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ImageHelper $imageHelper,
        CartHelper $cartHelper
    )
    {
        $this->checkoutSession = $checkoutSession;
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
            $billingAddress = $quote->getBillingAddress();
            $customer = $quote->getCustomer();
            $quoteItemsData = [];
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                /**
                 * @var Product $quoteProduct
                 */
                $quoteProduct = $quoteItem->getProduct();
                $quoteItemsData[] = [
                    'id' => $quoteProduct->getId(),
                    'url' => $quoteProduct->getUrlInStore(['_scope' => $quote->getStoreId(), '_nosid' => true]),
                    'name' => $quoteProduct->getName(),
                    'quantity' => $quoteItem->getQty(),
                    'price' => $quoteProduct->getFinalPrice(),
                    'image' => $this->imageHelper->init($quoteProduct, "product_page_image_small")
                        ->setImageFile($quoteProduct->getSmallImage())
                        ->getUrl()
                ];
            }
            if ($customer && $customer->getId() || $billingAddress && $billingAddress->getEmail()) {
                if ($quote->getItemsCount()) {
                    if (!$lastCreatedQuoteId || $lastCreatedQuoteId !== $quote->getId()) {
                        $this->checkoutSession->setSibLastCreatedQuoteId($quote->getId());
                        $quoteData = [
                            'email' => $customer ? $customer->getEmail() : $billingAddress->getEmail(),
                            'event' => 'cart_created',
                            'properties' => array(
                                'FIRSTNAME' => $customer ? $customer->getFirstname() : $billingAddress->getFirstname(),
                                'LASTNAME' => $customer ? $customer->getLastname() : $billingAddress->getLastname()
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
                            'email' => $customer ? $customer->getEmail() : $billingAddress->getEmail(),
                            'event' => 'cart_updated',
                            'properties' => array(
                                'FIRSTNAME' => $customer ? $customer->getFirstname() : $billingAddress->getFirstname(),
                                'LASTNAME' => $customer ? $customer->getLastname() : $billingAddress->getLastname()
                            ),
                            'eventdata' => array(
                                'id' => 'cart:' . $quote->getId(),
                                'data' => [
                                    'url' => $this->cartHelper->getCartUrl(),
                                    'currency' => $quote->getCurrency()->getQuoteCurrencyCode(),
                                    'items' => $quoteItemsData
                                ]
                            )
                        );
                        $this->checkoutSession->setSibUpdatedQuoteData($quoteUpdateData);
                    }
                } else {
                    $quoteUpdateData = array(
                        'email' => $customer ? $customer->getEmail() : $billingAddress->getEmail(),
                        'event' => 'cart_deleted',
                        'properties' => array(
                            'FIRSTNAME' => $customer ? $customer->getFirstname() : $billingAddress->getFirstname(),
                            'LASTNAME' => $customer ? $customer->getLastname() : $billingAddress->getLastname()
                        ),
                        'eventdata' => array(
                            'id' => 'cart:' . $quote->getId(),
                            'data' => [
                                'items' => []
                            ]
                        )
                    );
                    $this->checkoutSession->setSibDeletedQuoteData($quoteUpdateData);
                    $this->checkoutSession->setSibLastCreatedQuoteId(null);
                }
            }
        } catch (\Exception $e) {}

        return $this;
    }
}
