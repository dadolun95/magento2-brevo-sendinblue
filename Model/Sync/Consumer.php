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
use Magento\Quote\Api\Data\CartInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;

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
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

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
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
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
        QuoteCollectionFactory $quoteCollectionFactory,
        DateTimeFactory $dateTimeFactory,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->configHelper = $configHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->debugLogger = $debugLogger;
        $this->storeManager = $storeManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
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
            try {
                if ($syncData->getIsQuote()) {
                    $quote = $this->quoteRepository->get($syncData->getId());
                    $this->syncQuote($quote, $subscriber);
                } else {
                    $order = $this->orderRepository->get($syncData->getId());
                    $this->syncOrder($order, $subscriber);
                }
            } catch (\Exception $e) {
                if ($syncData->getIsQuote()) {
                    $this->debugLogger->error(__('Quote with id %1 not found, sync skipped.', $syncData->getId()));
                } else {
                    $this->debugLogger->error(__('Order with id %1 not found, sync skipped.', $syncData->getId()));
                }
            }
        }
    }

    /**
     * @param OrderInterface $order
     * @param $subscriber
     */
    private function syncOrder($order, $subscriber) {
        $dateTime = $this->dateTimeFactory->create();
        $orderDate = $dateTime->gmtDate('Y-m-d', $order->getCreatedAt());
        $updateDataInSib = [
            ConfigurationHelper::ORDER_ID_ATTRIBUTE => $order->getIncrementId(),
            ConfigurationHelper::ORDER_DATE_ATTRIBUTE => $orderDate,
            ConfigurationHelper::ORDER_TOTAL_ATTRIBUTE => $order->getGrandTotal(),
            ConfigurationHelper::ORDER_TOTAL_INVOICED_ATTRIBUTE => $order->getTotalInvoiced(),
            ConfigurationHelper::ORDER_STATUS_ATTRIBUTE => $order->getStatus(),
            ConfigurationHelper::QUOTE_ID_ATTRIBUTE => $order->getQuoteId(),
            ConfigurationHelper::QUOTE_DATE_ATTRIBUTE => $orderDate,
            ConfigurationHelper::QUOTE_TOTAL_ATTRIBUTE => $order->getGrandTotal(),
        ];
        $email = $subscriber->getEmail();
        $this->debugLogger->info(__('Orders for subscription %1 will synced', $email));
        $this->subscriptionManager->subscribe($email, $updateDataInSib, $subscriber->getSubscriberStatus());
        $this->debugLogger->info(__('Orders for subscription %1 synced by queue runner successfully', $email));
    }

    /**
     * @param CartInterface $quote
     * @param $subscriber
     */
    private function syncQuote($quote, $subscriber) {
        $dateTime = $this->dateTimeFactory->create();
        $quoteDate = $dateTime->gmtDate('Y-m-d', $quote->getCreatedAt());
        $updateDataInSib = [
            ConfigurationHelper::QUOTE_ID_ATTRIBUTE => $quote->getId(),
            ConfigurationHelper::QUOTE_DATE_ATTRIBUTE => $quoteDate,
            ConfigurationHelper::QUOTE_TOTAL_ATTRIBUTE => $quote->getGrandTotal(),
        ];
        $email = $subscriber->getEmail();
        $this->debugLogger->info(__('Quotes for subscription %1 will synced', $email));
        $this->subscriptionManager->subscribe($email, $updateDataInSib, $subscriber->getSubscriberStatus());
        $this->debugLogger->info(__('Quotes for subscription %1 synced by queue runner successfully', $email));
    }

    /**
     * @param $storeId
     */
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

                /**
                 * @var CartInterface[] $quotes
                 */
                $quotes = $this->quoteCollectionFactory->create()
                    ->addFieldToFilter('store_id', $storeId)
                    ->addFieldToFilter('reserved_order_id', ['null' => true])
                    ->addFieldToFilter('customer_email', ['eq' => $email])
                    ->getItems();

                foreach ($quotes as $quote) {
                    $this->syncQuote($quote, $subscriber);
                }
            }
        }
    }
}
