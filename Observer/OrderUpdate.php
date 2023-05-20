<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license     Open Source License
 */

namespace Dadolun\SibOrderSync\Observer;

use \Dadolun\SibOrderSync\Helper\Configuration as ConfigurationHelper;
use \Dadolun\SibContactSync\Model\SubscriptionManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Dadolun\SibCore\Helper\DebugLogger;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

/**
 * Class OrderUpdate
 * @package Dadolun\SibOrderSync\Observer
 */
class OrderUpdate implements ObserverInterface
{
    /**
     * @var SubscriptionManager
     */
    protected $subscriptionManager;

    /**
     * @var DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * @var DebugLogger
     */
    protected $debugLogger;

    /**
     * @var ConfigurationHelper
     */
    protected $configHelper;

    /**
     * OrderUpdate constructor.
     * @param SubscriptionManager $subscriptionManager
     * @param DateTimeFactory $dateTimeFactory
     * @param DebugLogger $debugLogger
     * @param ConfigurationHelper $configHelper
     */
    public function __construct(
        SubscriptionManager $subscriptionManager,
        DateTimeFactory $dateTimeFactory,
        DebugLogger $debugLogger,
        ConfigurationHelper $configHelper
    )
    {
        $this->subscriptionManager = $subscriptionManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->debugLogger = $debugLogger;
        $this->configHelper = $configHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $this->debugLogger->info(__('OrderUpdate observer START'));
            /**
             * @var \Magento\Sales\Model\Order $order
             */
            $order = $observer->getOrder();
            $email = $order->getCustomerEmail();
            $subscriberStatus = $this->subscriptionManager->checkSubscriberStatus($email);
            $orderSyncStatus = $this->configHelper->isSyncEnabled();
            $this->debugLogger->info(__('Try update order (for customer with email: %1', $email));
            if (in_array($subscriberStatus, ConfigurationHelper::ALLOWED_SUBSCRIBER_STATUSES) && $orderSyncStatus) {
                $dateTime = $this->dateTimeFactory->create();
                $updateDataInSib = [
                    ConfigurationHelper::ORDER_ID_ATTRIBUTE => $order->getIncrementId(),
                    ConfigurationHelper::ORDER_DATE_ATTRIBUTE => $dateTime->gmtDate('Y-m-d', $order->getCreatedAt()),
                    ConfigurationHelper::ORDER_TOTAL_ATTRIBUTE => $order->getGrandTotal(),
                    ConfigurationHelper::ORDER_TOTAL_INVOICED_ATTRIBUTE => $order->getTotalInvoiced(),
                    ConfigurationHelper::ORDER_STATUS_ATTRIBUTE => $order->getStatus()
                ];
                $this->subscriptionManager->subscribe($email, $updateDataInSib, $subscriberStatus);
            } else {
                if (!$orderSyncStatus) {
                    $this->debugLogger->info(__('Order Sync is not enabled'));
                }
            }
            $this->debugLogger->info(__('OrderUpdate observer END'));
        } catch (\Exception $e) {
            $this->debugLogger->error(__('Error: ') . $e->getMessage());
        }
    }
}
