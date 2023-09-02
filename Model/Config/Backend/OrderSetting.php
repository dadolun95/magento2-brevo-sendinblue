<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Message\ManagerInterface;
use \Dadolun\SibCore\Helper\SibClientConnector;
use \Dadolun\SibCore\Model\SibClient;
use \Dadolun\SibOrderSync\Helper\Configuration;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use \Brevo\Client\ApiException;

/**
 * Class OrderSetting
 * @package Dadolun\SibOrderSync\Model\Config\Backend
 */
class OrderSetting extends Value
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
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * OrderSetting constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SibClientConnector $sibClientConnector
     * @param Configuration $configHelper
     * @param ManagerInterface $messageManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $contactTransactionalAttributes
     * @param array $contactCalculatedAttributes
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SibClientConnector $sibClientConnector,
        Configuration $configHelper,
        ManagerInterface $messageManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $contactTransactionalAttributes = [],
        array $contactCalculatedAttributes = [],
        array $data = []
    ) {
        $this->sibClientConnector = $sibClientConnector;
        $this->configHelper = $configHelper;
        $this->contactTransactionalAttributes = $contactTransactionalAttributes;
        $this->contactCalculatedAttributes = $contactCalculatedAttributes;
        $this->messageManager = $messageManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return Value|void
     */
    public function beforeSave()
    {
        $this->_dataSaveAllowed = false;
        $value = $this->getValue();
        if ($this->configHelper->isSyncEnabled() && $value === "1") {
            $apiKey = $this->configHelper->getValue('api_key_v3');
            if (!is_null($apiKey) && $apiKey !== '') {

                $this->_dataSaveAllowed = true;

                try {
                    /**
                     * @var SibClient $sibClient
                     */
                    $sibClient = $this->sibClientConnector->createSibClient($apiKey);
                    $sibClient->setApiKey($apiKey);
                    $sibClient->getAccount();
                    $lastSibCLientResponse = $sibClient->getLastResponseCode();
                } catch (ApiException $e) {
                    $this->messageManager->addErrorMessage(__('An error occurred retrieving your Brevo account. Please check your API key.'));
                    $this->_dataSaveAllowed = false;
                    return;
                }

                if ($this->_dataSaveAllowed && SibClient::RESPONSE_CODE_OK == $lastSibCLientResponse) {

                    try {
                        $contactAttributes = $sibClient->getAttributes()['attributes'];
                        $contactAttributesList = [
                            self::TRANSACTIONAL_TYPE_CONTACT_ATTRIBUTES_KEY => [],
                            self::CALCULATED_TYPE_CONTACT_ATTRIBUTES_KEY => []
                        ];
                        foreach ($contactAttributes as $attribute) {
                            $contactAttributesList[$attribute['category']][] = $attribute['name'];
                        }
                    } catch (\Exception $e) {
                        $this->_dataSaveAllowed = false;
                    }

                    if ($this->_dataSaveAllowed) {
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
        } else {
            $this->_dataSaveAllowed = true;
        }
        $this->setValue($value);
    }
}
