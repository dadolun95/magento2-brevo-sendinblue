<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Model\Sync;

use \Dadolun\SibOrderSync\Api\Data\SyncOrderInfoInterface;
use \Dadolun\SibContactSync\Model\SubscriptionManager;
use \Dadolun\SibCore\Helper\DebugLogger;
use Magento\Store\Model\StoreManagerInterface;
use \Dadolun\SibOrderSync\Helper\Configuration as ConfigurationHelper;
use Magento\Customer\Api\AddressRepositoryInterface as CustomerAddressRepository;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class Consumer
 * @package Dadolun\SibOrderSync\Model\Sync
 */
class Consumer
{
    /**
     * @var SubscriberCollectionFactory
     */
    private $subscriberCollectionFactory;

    /**
     * @var ConfigurationHelper
     */
    private $configHelper;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @var CustomerAddressRepository
     */
    private $customerAddressRepository;

    /**
     * @var DebugLogger
     */
    private $debugLogger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Consumer constructor.
     * @param SubscriberCollectionFactory $subscriberCollectionFactory
     * @param ConfigurationHelper $configHelper
     * @param SubscriptionManager $subscriptionManager
     * @param CustomerAddressRepository $customerAddressRepository
     * @param StoreManagerInterface $storeManager
     * @param DebugLogger $debugLogger
     * @param SubscriberFactory $subscriberFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        SubscriberCollectionFactory $subscriberCollectionFactory,
        ConfigurationHelper $configHelper,
        SubscriptionManager $subscriptionManager,
        CustomerAddressRepository $customerAddressRepository,
        StoreManagerInterface $storeManager,
        DebugLogger $debugLogger,
        SubscriberFactory $subscriberFactory,
        OrderCollectionFactory $orderCollectionFactory,
        DateTimeFactory $dateTimeFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->configHelper = $configHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->debugLogger = $debugLogger;
        $this->storeManager = $storeManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Consumer logic.
     *
     * @param SyncOrderInfoInterface $syncData
     * @return void
     */
    public function process(SyncOrderInfoInterface $syncData)
    {
        $syncType = $syncData->getType();
        if ($syncType === SyncOrderInfoInterface::TOTAL_SYNC_TYPE) {
            $this->syncOrders($syncData->getStoreId());
        } else {
            $email = $syncData->getEmail();
            $subscriber = $this->subscriberFactory->create()->loadBySubscriberEmail($email, $syncData->getStoreId());
            $order = $this->orderRepository->get($syncData->getOrderId());
            $this->syncOrder($order, $subscriber);
        }
    }

    /**
     * @param $order
     * @param $subscriber
     */
    private function syncOrder($order, $subscriber) {
        $dateTime = $this->dateTimeFactory->create();
        $updateDataInSib = [
            ConfigurationHelper::ORDER_ID_ATTRIBUTE => $order->getIncrementId(),
            ConfigurationHelper::ORDER_DATE_ATTRIBUTE => $dateTime->gmtDate('Y-m-d', $order->getCreatedAt()),
            ConfigurationHelper::ORDER_TOTAL_ATTRIBUTE => $order->getGrandTotal(),
            ConfigurationHelper::ORDER_TOTAL_INVOICED_ATTRIBUTE => $order->getTotalInvoiced(),
            ConfigurationHelper::ORDER_STATUS_ATTRIBUTE => $order->getStatus()
        ];
        $email = $subscriber->getEmail();
        $this->debugLogger->info(__('Orders for subscription %1 will synced', $email));
        $this->subscriptionManager->subscribe($email, $updateDataInSib, $subscriber->getSubscriberStatus());
        $this->debugLogger->info(__('Orders for subscription %1 synced by queue runner successfully', $email));
    }

    private function syncOrders($storeId) {
        /**
         * @var Subscriber[] $subscribers
         */
        $subscribers = $this->subscriberCollectionFactory->create()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('subscriber_status', ['in' => ConfigurationHelper::ALLOWED_SUBSCRIBER_STATUSES])
            ->getItems();
        foreach ($subscribers as $subscriber) {
            $subscriberStatus = $subscriber->getSubscriberStatus();
            $email = $subscriber->getEmail();
            if ($subscriberStatus) {
                if ($subscriber->getCustomerId()) {
                    try {

                        /**
                         * @var OrderInterface[] $orders
                         */
                        $orders = $this->orderCollectionFactory->create()
                            ->addFieldToFilter('store_id', $storeId)
                            ->addFieldToFilter(
                                ['customer_id', 'customer_email'],
                                [
                                    ['eq' => $subscriber->getCustomerId()],
                                    ['eq' => $email]
                                ]
                            )
                            ->getItems();

                        foreach ($orders as $order) {
                            $this->syncOrder($order, $subscriber);
                        }
                    } catch (\Exception $e) {
                        $this->debugLogger->error($e->getMessage());
                    }

                } else {
                    try {

                        /**
                         * @var OrderInterface[] $orders
                         */
                        $orders = $this->orderCollectionFactory->create()
                            ->addFieldToFilter('customer_email', $email)
                            ->getItems();

                        foreach ($orders as $order) {
                            $this->syncOrder($order, $subscriber);
                        }
                    } catch (\Exception $e) {
                        $this->debugLogger->error($e->getMessage());
                    }
                }
            }
        }
    }
}
