<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Observer;

use Dadolun\SibContactSync\Model\Config\Source\SyncType;
use Dadolun\SibOrderSync\Model\Sync\SyncOrderInfoFactory;
use \Dadolun\SibOrderSync\Helper\Configuration as ConfigurationHelper;
use \Dadolun\SibContactSync\Model\SubscriptionManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Dadolun\SibCore\Helper\DebugLogger;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\MessageQueue\PublisherInterface;
use \Dadolun\SibOrderSync\Api\Data\SyncOrderInfoInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class QuoteUpdate
 * @package Dadolun\SibOrderSync\Observer
 */
class QuoteUpdate implements ObserverInterface
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
     * @var SyncOrderInfoFactory
     */
    protected $syncInfoFactory;

    /**
     * @var PublisherInterface
     */
    protected $messagePublisher;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * QuoteUpdate constructor.
     * @param SubscriptionManager $subscriptionManager
     * @param DateTimeFactory $dateTimeFactory
     * @param DebugLogger $debugLogger
     * @param ConfigurationHelper $configHelper
     * @param SyncOrderInfoFactory $syncInfoFactory
     * @param PublisherInterface $messagePublisher
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SubscriptionManager $subscriptionManager,
        DateTimeFactory $dateTimeFactory,
        DebugLogger $debugLogger,
        ConfigurationHelper $configHelper,
        SyncOrderInfoFactory $syncInfoFactory,
        PublisherInterface $messagePublisher,
        StoreManagerInterface $storeManager
    )
    {
        $this->subscriptionManager = $subscriptionManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->debugLogger = $debugLogger;
        $this->configHelper = $configHelper;
        $this->syncInfoFactory = $syncInfoFactory;
        $this->messagePublisher = $messagePublisher;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $this->debugLogger->info(__('QuoteUpdate observer START'));
            /* @var Quote $quote */
            $quote = $observer->getEvent()->getQuote();
            $email = $quote->getCustomerEmail() ? $quote->getCustomerEmail() : $quote->getBillingAddress()->getEmail();
            if ($email) {
                $subscriberStatus = $this->subscriptionManager->checkSubscriberStatus($email);
                $orderSyncStatus = $this->configHelper->isSyncEnabled();
                if ($this->configHelper->getOrderValue('sync_type') === SyncType::ASYNC) {

                    /** @var SyncOrderInfoInterface $dataObject */
                    $dataObject = $this->syncInfoFactory->create(
                        $this->storeManager->getStore()->getId(),
                        SyncOrderInfoInterface::PARTIAL_SYNC_TYPE,
                        $quote->getId(),
                        true,
                        $email
                    );
                    $this->messagePublisher->publish('sibSync.order', $dataObject);
                    $this->debugLogger->info(__('Subscription quote added to queue'));
                } else {
                    $this->debugLogger->info(__('Try update quote (for customer with email: %1)', $email));
                    if (in_array($subscriberStatus, ConfigurationHelper::ALLOWED_SUBSCRIBER_STATUSES) && $orderSyncStatus) {
                        $dateTime = $this->dateTimeFactory->create();
                        $quoteDate = $dateTime->gmtDate('Y-m-d', $quote->getCreatedAt());
                        $updateDataInSib = [
                            ConfigurationHelper::QUOTE_ID_ATTRIBUTE => $quoteDate->getQuoteId(),
                            ConfigurationHelper::QUOTE_DATE_ATTRIBUTE => $quoteDate,
                            ConfigurationHelper::QUOTE_TOTAL_ATTRIBUTE => $quote->getGrandTotal(),
                        ];
                        $this->subscriptionManager->subscribe($email, $updateDataInSib, $subscriberStatus);
                    } else {
                        if (!$orderSyncStatus) {
                            $this->debugLogger->info(__('Order Sync is not available for this subscriber'));
                        }
                    }
                }
            }
            $this->debugLogger->info(__('QuoteUpdate observer END'));
        } catch (\Exception $e) {
            $this->debugLogger->error(__('Error: ') . $e->getMessage());
        }
    }
}
