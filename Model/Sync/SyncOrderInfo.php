<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Model\Sync;

use Dadolun\SibOrderSync\Api\Data\SyncOrderInfoInterface;

/**
 * Class SyncInfo
 * @package Dadolun\SibContactSync\Model\Sync
 */
class SyncOrderInfo implements SyncOrderInfoInterface
{
    private $store;
    private $id;
    private $isQuote;
    private $type;
    private $email;

    /**
     * @return int|null
     */
    public function getStoreId() {
        return $this->store;
    }

    /**
     * Set sib store sync
     *
     * @param int $store
     * @return void
     */
    public function setStoreId($store) {
        $this->store = $store;
    }

    /**
     * @return string|null
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return boolean|null
     */
    public function getIsQuote() {
        return $this->isQuote;
    }

    /**
     * @param boolean $isQuote
     */
    public function setIsQuote($isQuote) {
        $this->isQuote = $isQuote;
    }

    /**
     * @return string|null
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return string|int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string|int $id
     */
    public function setId($id) {
        $this->id = $id;
    }
}
