<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Model\Sync;

use Dadolun\SibOrderSync\Api\Data\SyncOrderInfoInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class SyncOrderInfoFactory
 * @package Dadolun\SibOrderSync\Model\Sync
 */
class SyncOrderInfoFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * SyncOrderInfoFactory constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    )
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param $storeId
     * @param $type
     * @param $orderId
     * @param $isQuote
     * @param $email
     * @return SyncOrderInfoInterface
     */
    public function create($storeId, $type, $id, $isQuote, $email)
    {
        /**
         * @var SyncOrderInfoInterface $syncOrderInfo
         */
        $syncOrderInfo = $this->objectManager->create(SyncOrderInfoInterface::class);
        $syncOrderInfo->setStoreId($storeId);
        $syncOrderInfo->setType($type);
        $syncOrderInfo->setId($id);
        $syncOrderInfo->setIsQuote($isQuote);
        $syncOrderInfo->setEmail($email);
        return $syncOrderInfo;
    }
}
