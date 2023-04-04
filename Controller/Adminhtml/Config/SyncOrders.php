<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2021 Dadolun (https://github.com/dadolun95)
 * @license     Open Source License
 */

namespace Dadolun\SibOrderSync\Controller\Adminhtml\Config;

use Dadolun\SibContactSync\Model\SubscriptionManager;
use Dadolun\SibCore\Helper\DebugLogger;
use Magento\Framework\Controller\Result\JsonFactory;
use \Dadolun\SibOrderSync\Helper\Configuration as ConfigurationHelper;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

/**
 * Class SyncOrders
 * @package Dadolun\SibOrderSync\Controller\Adminhtml\Config
 */
class SyncOrders extends \Magento\Backend\App\Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var SubscriberCollectionFactory
     */
    protected $subscriberCollectionFactory;

    /**
     * @var ConfigurationHelper
     */
    protected $configHelper;

    /**
     * @var SubscriptionManager
     */
    protected $subscriptionManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * @var DebugLogger
     */
    protected $debugLogger;

    /**
     * SyncOrders constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SubscriberCollectionFactory $subscriberCollectionFactory
     * @param ConfigurationHelper $configHelper
     * @param SubscriptionManager $subscriptionManager
     * @param StoreManagerInterface $storeManager
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param DebugLogger $debugLogger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        SubscriberCollectionFactory $subscriberCollectionFactory,
        ConfigurationHelper $configHelper,
        SubscriptionManager $subscriptionManager,
        StoreManagerInterface $storeManager,
        OrderCollectionFactory $orderCollectionFactory,
        DateTimeFactory $dateTimeFactory,
        DebugLogger $debugLogger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
        $this->configHelper = $configHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->storeManager = $storeManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->debugLogger = $debugLogger;
    }

    /**
     * Check whether vat is valid
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $result = $this->syncOrders();
            $result['valid'] = true;
            $result['message'] = __('Orders correctly synced');
        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['message'] = __('Something went wrong syncing your orders, enable the debug logger and check api responses');
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'valid' => (int)$result['valid'],
            'message' => $result['message'],
        ]);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     */
    private function syncOrders() {
        $dateTime = $this->dateTimeFactory->create();
        /**
         * @var Subscriber[] $subscribers
         */
        $subscribers = $this->subscriberCollectionFactory->create()
            ->addFieldToFilter('subscriber_status', ['in' => ConfigurationHelper::ALLOWED_SUBSCRIBER_STATUSES])
            ->getItems();
        foreach ($subscribers as $subscriber) {
            $subscriberStatus = $subscriber->getSubscriberStatus();
            $email = $subscriber->getEmail();
            if ($subscriberStatus) {
                if ($subscriber->getCustomerId()) {
                    $this->debugLogger->info(__('Update user order data (admin sync)'));

                    try {

                        /**
                         * @var \Magento\Sales\Api\Data\OrderInterface[] $orders
                         */
                        $orders = $this->orderCollectionFactory->create()
                            ->addFieldToFilter(
                                ['customer_id', 'customer_email'],
                                [
                                    ['eq' => $subscriber->getCustomerId()],
                                    ['eq' => $email]
                                ]
                            )
                            ->getItems();

                        foreach ($orders as $order) {
                            $updateDataInSib = [
                                ConfigurationHelper::ORDER_ID_ATTRIBUTE => $order->getIncrementId(),
                                ConfigurationHelper::ORDER_DATE_ATTRIBUTE => $dateTime->gmtDate('Y-m-d', $order->getCreatedAt()),
                                ConfigurationHelper::ORDER_TOTAL_ATTRIBUTE => $order->getGrandTotal(),
                                ConfigurationHelper::ORDER_TOTAL_INVOICED_ATTRIBUTE => $order->getTotalInvoiced(),
                                ConfigurationHelper::ORDER_STATUS_ATTRIBUTE => $order->getStatus()
                            ];
                            $this->subscriptionManager->subscribe($email, $updateDataInSib, $subscriberStatus);
                        }
                    } catch (\Exception $e) {
                        $this->debugLogger->error($e->getMessage());
                    }

                } else {
                    $this->debugLogger->info(__('Update user order data (admin sync)'));

                    try {

                        /**
                         * @var \Magento\Sales\Api\Data\OrderInterface[] $orders
                         */
                        $orders = $this->orderCollectionFactory->create()
                            ->addFieldToFilter('customer_email', $email)
                            ->getItems();

                        foreach ($orders as $order) {
                            $updateDataInSib = [
                                ConfigurationHelper::ORDER_ID_ATTRIBUTE => $order->getIncrementId(),
                                ConfigurationHelper::ORDER_DATE_ATTRIBUTE => $dateTime->gmtDate('Y-m-d', $order->getCreatedAt()),
                                ConfigurationHelper::ORDER_TOTAL_ATTRIBUTE => $order->getGrandTotal(),
                                ConfigurationHelper::ORDER_TOTAL_INVOICED_ATTRIBUTE => $order->getTotalInvoiced(),
                                ConfigurationHelper::ORDER_STATUS_ATTRIBUTE => $order->getStatus()
                            ];
                            $this->subscriptionManager->subscribe($email, $updateDataInSib, $subscriberStatus);
                        }
                    } catch (\Exception $e) {
                        $this->debugLogger->error($e->getMessage());
                    }
                }
            }
        }
    }
}
