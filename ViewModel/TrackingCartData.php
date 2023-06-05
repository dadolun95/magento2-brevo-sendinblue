<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2023 Dadolun (https://www.dadolun.com)
 * @license    This code is licensed under MIT license (see LICENSE for details)
 */

namespace Dadolun\SibOrderSync\ViewModel;

use Dadolun\SibCore\ViewModel\TrackingData;
use Dadolun\SibOrderSync\Helper\Configuration;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Class TrackingCartData
 * @package Dadolun\SibCore\ViewModel
 */
class TrackingCartData extends TrackingData implements ArgumentInterface
{

    /**
     * @var Configuration
     */
    protected $configurationHelper;

    /**
     * TrackingCartData constructor.
     * @param Configuration $configurationHelper
     * @param Http $request
     * @param UrlInterface $url
     * @param Registry $registry
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Configuration $configurationHelper,
        Http $request,
        UrlInterface $url,
        Registry $registry,
        JsonHelper $jsonHelper
    )
    {
        parent::__construct($configurationHelper, $request, $url, $registry, $jsonHelper);
        $this->configurationHelper = $configurationHelper;
    }

    /**
     * @return mixed|null
     */
    public function isCartTrackingEnabled()
    {
        if ($this->getAutomationKey() !== null) {
            return $this->configurationHelper->getOrderFlag("track_abandoned_cart");
        }
        return false;
    }
}
