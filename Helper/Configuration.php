<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class Configuration
 * @package Dadolun\SibOrderSync\Helper
 */
class Configuration extends \Dadolun\SibCore\Helper\Configuration
{

    const CONFIG_GROUP_ORDER_PATH = 'sendinblue_order';
    const MODULE_ORDER_CONFIG_PATH = self::CONFIG_SECTION_PATH . '/' . self::CONFIG_GROUP_ORDER_PATH;

    const ORDER_ID_ATTRIBUTE = 'ORDER_ID';
    const ORDER_DATE_ATTRIBUTE = 'ORDER_DATE';
    const ORDER_TOTAL_ATTRIBUTE = 'ORDER_PRICE';
    const ORDER_TOTAL_INVOICED_ATTRIBUTE = 'ORDER_PRICE_INVOICED';
    const ORDER_STATUS_ATTRIBUTE = 'ORDER_STATUS';

    const QUOTE_ID_ATTRIBUTE = 'QUOTE_ID';
    const QUOTE_DATE_ATTRIBUTE = 'QUOTE_DATE';
    const QUOTE_TOTAL_ATTRIBUTE = 'QUOTE_PRICE';

    const ALLOWED_SUBSCRIBER_STATUSES = [
        Subscriber::STATUS_SUBSCRIBED
    ];

    /**
     * @param $val
     * @return mixed
     */
    public function getOrderValue($val) {
        return $this->scopeConfig->getValue(self::MODULE_ORDER_CONFIG_PATH . '/' . $val, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $val
     * @return bool
     */
    public function getOrderFlag($val) {
        return $this->scopeConfig->isSetFlag(self::MODULE_ORDER_CONFIG_PATH . '/' . $val, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Set module config value
     *
     * @param $pathVal
     * @param $val
     * @param string $scope
     * @param int $scopeId
     */
    public function setOrderValue($pathVal, $val, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configWriter->save(self::MODULE_ORDER_CONFIG_PATH . '/' . $pathVal, $val, $scope, $scopeId);
    }

    /**
     * Checks whether the Brevo API key and the Brevo order form is enabled
     * and returns the true|false accordingly.
     *
     * @return bool
     */
    public function isSyncEnabled() {
        $subsStatus = $this->getOrderFlag('order_setting');
        if ($this->isServiceActive() && $subsStatus) {
            return true;
        }
        return false;
    }

}
