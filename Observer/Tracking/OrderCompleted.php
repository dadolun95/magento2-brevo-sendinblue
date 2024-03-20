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
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Cart as CartHelper;

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
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var CartHelper
     */
    protected $cartHelper;

    /**
     * OrderCompleted constructor.
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
             * @var Order $order
             */
            $order = $observer->getData("order");
            $customer = $this->customerSession->getCustomer();
            foreach ($order->getAllVisibleItems() as $orderItem) {
                /**
                 * @var Product $orderProduct
                 */
                $orderProduct = $orderItem->getProduct();
                $orderItemsData[] = [
                    'id' => $orderProduct->getId(),
                    'url' => $orderProduct->getUrlInStore($orderProduct, ['_scope' => $order->getStoreId(), '_nosid' => true]),
                    'name' => $orderProduct->getName(),
                    'quantity' => $orderItem->getQty(),
                    'price' => $orderProduct->getFinalPrice(),
                    'image' => $this->imageHelper->init($orderProduct, "product_page_image_small")
                        ->setImageFile($orderProduct->getSmallImage())
                        ->getUrl()
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
                        'items' => $orderItemsData
                    ]
                )
            ];
            $this->checkoutSession->setSibPurchaseData($orderData);
        } catch (\Exception $e) {}

        return $this;
    }
}
