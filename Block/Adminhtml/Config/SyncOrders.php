<?php
/**
 * @package     Dadolun_SibOrderSync
 * @copyright   Copyright (c) 2021 Dadolun (https://github.com/dadolun95)
 * @license     Open Source License
 */

namespace Dadolun\SibOrderSync\Block\Adminhtml\Config;

/**
 * Class SyncOrders
 * @package Dadolun\SibOrderSync\Block\Adminhtml\Config
 */
class SyncOrders extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Set template to itself
     *
     * @return $this|\Magento\Config\Block\System\Config\Form\Field
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Dadolun_SibOrderSync::config/sync-orders.phtml');
        }
        return $this;
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : __('Sync orders');
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('sibordersync/config/syncorders'),
            ]
        );

        return $this->_toHtml();
    }
}
