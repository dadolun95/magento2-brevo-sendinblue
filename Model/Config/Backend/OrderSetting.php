<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2021 Dadolun (https://github.com/dadolun95)
 * @license     Open Source License
 */

namespace Dadolun\SibOrderSync\Model\Config\Backend;

use \Dadolun\SibCore\Helper\SibClientConnector;
use \Dadolun\SibCore\Model\SibClient;
use \Dadolun\SibOrderSync\Helper\Configuration;

/**
 * Class OrderSetting
 * @package Dadolun\SibOrderSync\Model\Config\Backend
 */
class OrderSetting extends \Magento\Framework\App\Config\Value
{

    const TRANSACTIONAL_TYPE_CONTACT_ATTRIBUTES_KEY = "transactional";
    const CALCULATED_TYPE_CONTACT_ATTRIBUTES_KEY = "calculated";

    /**
     * @var SibClientConnector
     */
    protected $sibClientConnector;

    /**
     * @var Configuration
     */
    protected $configHelper;

    /**
     * @var array
     */
    protected $contactTransactionalAttributes;

    /**
     * @var array
     */
    protected $contactCalculatedAttributes;

    /**
     * OrderSetting constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param SibClientConnector $sibClientConnector
     * @param Configuration $configHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $contactTransactionalAttributes
     * @param array $contactCalculatedAttributes
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        SibClientConnector $sibClientConnector,
        Configuration $configHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $contactTransactionalAttributes = [],
        array $contactCalculatedAttributes = [],
        array $data = []
    ) {
        $this->sibClientConnector = $sibClientConnector;
        $this->configHelper = $configHelper;
        $this->contactTransactionalAttributes = $contactTransactionalAttributes;
        $this->contactCalculatedAttributes = $contactCalculatedAttributes;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return \Magento\Framework\App\Config\Value|void
     * @throws \SendinBlue\Client\ApiException
     */
    public function beforeSave()
    {
        $this->_dataSaveAllowed = false;
        $value = $this->getValue();
        $apiKey = $this->configHelper->getValue('api_key_v3');
        if (!is_null($apiKey) && $apiKey !== '') {
            /**
             * @var \Dadolun\SibCore\Model\SibClient $sibClient
             */
            $sibClient = $this->sibClientConnector->createSibClient($apiKey);
            $sibClient->setApiKey($apiKey);
            $sibClient->getAccount();

            if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
                $contactAttributes =  $sibClient->getAttributes()['attributes'];
                $contactAttributesList = [
                    self::TRANSACTIONAL_TYPE_CONTACT_ATTRIBUTES_KEY => [],
                    self::CALCULATED_TYPE_CONTACT_ATTRIBUTES_KEY => []
                ];
                foreach ($contactAttributes as $attribute) {
                    $contactAttributesList[$attribute['category']][] = $attribute['name'];
                }
                try {
                    foreach ($this->contactTransactionalAttributes as $contactAttribute => $type) {
                        if (!in_array($contactAttribute, $contactAttributesList[self::TRANSACTIONAL_TYPE_CONTACT_ATTRIBUTES_KEY])) {
                            $sibClient->createAttribute(self::TRANSACTIONAL_TYPE_CONTACT_ATTRIBUTES_KEY, $contactAttribute, array("type" => $type));
                        }
                    }
                    $this->_dataSaveAllowed = true;
                } catch (\Exception $e) {
                    $this->_dataSaveAllowed = false;
                }

                if ($this->_dataSaveAllowed) {
                    try {
                        foreach ($this->contactCalculatedAttributes as $contactAttribute => $expression) {
                            if (!in_array($contactAttribute, $contactAttributesList[self::CALCULATED_TYPE_CONTACT_ATTRIBUTES_KEY])) {
                                $sibClient->createAttribute(self::CALCULATED_TYPE_CONTACT_ATTRIBUTES_KEY, $contactAttribute, array("value" => $expression));
                            }
                        }
                        $this->_dataSaveAllowed = true;
                    } catch (\Exception $e) {
                        $this->_dataSaveAllowed = false;
                    }
                }
            }
        } else {
            $this->_dataSaveAllowed = false;
        }
        $this->setValue($value);
    }
}
