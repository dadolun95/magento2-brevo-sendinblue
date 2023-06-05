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
use Magento\Sales\Model\Order;

/**
 * Class OrderCompleted
 * @package Dadolun\SibOrderSync\Observer\Tracking
 */
class OrderCompleted implements ObserverInterface
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
     * OrderCompleted constructor.
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
             * @var Order $order
             */
            $order = $observer->getData("order");
            $customer = $this->customerSession->getCustomer();
            foreach ($order->getAllVisibleItems() as $orderItem) {
                $orderProduct = $orderItem->getProduct();
                $orderItemsData[] = [
                    "product_id" => $orderProduct->getId(),
                    "product_name" => $orderProduct->getName(),
                    "amount" => $orderItem->getQty(),
                    "price" => $orderProduct->getFinalPrice()
                ];
            }
            $orderData = [
                'email' => $customer->getEmail(),
                'event' => 'order_completed',
                'properties' => array(
                    'FIRSTNAME' => $customer->getFirstname(),
                    'LASTNAME' => $customer->getLastname()
                ),
                'eventdata' => array(
                    'id' => 'cart:' . $order->getQuoteId(),
                    'data' => [
                        "products" => $orderItemsData
                    ]
                )
            ];
            $this->checkoutSession->setSibPurchaseData($orderData);
        } catch (\Exception $e) {}

        return $this;
    }
}
