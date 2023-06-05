<?php
namespace Dadolun\SibOrderSync\CustomerData;

use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Json\Helper\Data;
use Dadolun\SibOrderSync\Helper\Configuration as ConfigHelper;

/**
 * Class SibQuoteData
 * @package Dadolun\SibOrderSync\CustomerData
 */
class SibQuoteData extends \Magento\Framework\DataObject implements SectionSourceInterface
{

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * SibQuoteData constructor.
     * @param Data $jsonHelper
     * @param Session $checkoutSession
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Data $jsonHelper,
        Session $checkoutSession,
        ConfigHelper $configHelper,
        array $data = []
    )
    {
        parent::__construct($data);
        $this->jsonHelper = $jsonHelper;
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $data = [];

        /** cart_created event data */
        if ($this->checkoutSession->getSibCreatedQuoteData()) {
            $data[] = $this->checkoutSession->getSibCreatedQuoteData();
        }
        $this->checkoutSession->setSibCreatedQuoteData(null);

        /** cart_updated event data */
        if ($this->checkoutSession->getSibUpdatedQuoteData()) {
            $data[] = $this->checkoutSession->getSibUpdatedQuoteData();
        }
        $this->checkoutSession->setSibUpdatedQuoteData(null);

        /** cart_deleted event data */
        if ($this->checkoutSession->getSibDeletedQuoteData()) {
            $data[] = $this->checkoutSession->getSibDeletedQuoteData();
        }
        $this->checkoutSession->setSibDeletedQuoteData(null);

        /** purchase event data */
        if ($this->checkoutSession->getSibPurchaseData()) {
            $data[] = $this->checkoutSession->getSibPurchaseData();
        }
        $this->checkoutSession->setSibPurchaseData(null);

        return [
            'sib_quote_data' => $this->jsonHelper->jsonEncode($data)
        ];
    }
}
