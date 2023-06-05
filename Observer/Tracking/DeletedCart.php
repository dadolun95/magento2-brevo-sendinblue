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

/**
 * Class RemoveFromCart
 * @package Dadolun\SibOrderSync\Observer\Tracking
 */
class DeletedCart implements ObserverInterface
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
     * DeletedCart constructor.
     * @param CheckoutSession $checkoutSession
     * @param Session $customerSession
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Session $customerSession
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
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
            $sessionQuote = $this->checkoutSession->getQuote();
            $customer = $this->customerSession->getCustomer();
            $quoteItemsData = [];
            foreach ($sessionQuote->getAllVisibleItems() as $quoteItem) {
                $quoteProduct = $quoteItem->getProduct();
                $quoteItemsData[] = [
                    "product_id" => $quoteProduct->getId(),
                    "product_name" => $quoteProduct->getName(),
                    "amount" => $quoteItem->getQty(),
                    "price" => $quoteProduct->getFinalPrice()
                ];
            }
            if (count($sessionQuote->getAllItems()) === 0) {
                $customer = $this->customerSession->getCustomer();
                $quoteData = [
                    'email' => $customer->getEmail(),
                    'event' => 'cart_deleted',
                    'properties' => array(
                        'FIRSTNAME' => $customer->getFirstname(),
                        'LASTNAME' => $customer->getLastname()
                    ),
                    'eventdata' => array(
                        'id' => $quoteItem->getQuoteId(),
                        'data' => array("items" => array())
                    )
                ];
                $this->checkoutSession->setSibDeletedQuoteData($quoteData);
                $this->checkoutSession->setSibLastCreatedQuoteId(null);
            } else {
                $quoteUpdateData = array(
                    'email' => $customer->getEmail(),
                    'event' => 'cart_updated',
                    'properties' => array(
                        'FIRSTNAME' => $customer->getFirstname(),
                        'LASTNAME' => $customer->getLastname()
                    ),
                    'eventdata' => array(
                        'id' => 'cart:' . $sessionQuote->getId(),
                        'data' => [
                            "products" => $quoteItemsData
                        ]
                    )
                );
                $this->checkoutSession->setSibUpdatedQuoteData($quoteUpdateData);
            }
        } catch (\Exception $e) {}

        return $this;
    }
}
