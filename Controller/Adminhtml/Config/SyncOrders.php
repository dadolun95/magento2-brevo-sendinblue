<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Controller\Adminhtml\Config;

use Dadolun\SibOrderSync\Model\Sync\SyncOrderInfoFactory;
use Dadolun\SibOrderSync\Api\Data\SyncOrderInfoInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class SyncOrders
 * @package Dadolun\SibOrderSync\Controller\Adminhtml\Config
 */
class SyncOrders extends \Magento\Backend\App\Action
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SyncOrderInfoFactory
     */
    protected $syncOrderInfoFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var PublisherInterface
     */
    protected $messagePublisher;

    /**
     * SyncContacts constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param SyncOrderInfoFactory $syncOrderInfoFactory
     * @param PublisherInterface $messagePublisher
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        SyncOrderInfoFactory $syncOrderInfoFactory,
        PublisherInterface $messagePublisher,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->syncOrderInfoFactory = $syncOrderInfoFactory;
        $this->messagePublisher = $messagePublisher;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Check whether vat is valid
     *
     * @return Json
     */
    public function execute()
    {
        try {

            /** @var SyncOrderInfoInterface $dataObject */
            $dataObject = $this->syncOrderInfoFactory->create(
                $this->storeManager->getStore()->getId(),
                SyncOrderInfoInterface::TOTAL_SYNC_TYPE,
                '',
                false,
                ''
            );

            $this->messagePublisher->publish('sibSync.order', $dataObject);
            $result['valid'] = true;
            $result['message'] = __('Subscribers orders sync is correctly added to queue. Please wait some time or check the logs (if enabled), data will\'be synchronized soon!');
        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['message'] = __('Something went wrong syncing your contacts, enable the debug logger and check api responses');
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'valid' => (int)$result['valid'],
            'message' => $result['message'],
        ]);
    }
}
