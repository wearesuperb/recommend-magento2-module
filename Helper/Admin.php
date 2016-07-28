<?php
/*
 * Superb_Recommend
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Superb
 * @package    Superb_Recommend
 * @author     Superb <hello@wearesuperb.com>
 * @copyright  Copyright (c) 2015 Superb Media Limited
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Superb\Recommend\Helper;

class Admin extends \Magento\Framework\App\Helper\AbstractHelper
{
    static protected $_slotSystemConfigSaveData = [];
    static protected $_slotsData = [];
    static protected $_panelsData = [];
    static protected $_productAttributesData = [];
    static protected $_customerAttributesData = [];
    static protected $_systemConfigStoreId = -1;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $storeWebsiteFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\WebsiteFactory $storeWebsiteFactory,
        \Superb\Recommend\Helper\Api $apiHelper
    ) {
        $this->storeWebsiteFactory = $storeWebsiteFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_apiHelper = $apiHelper;
        parent::__construct($context);
    }

    public function setSaveSlotSystemConfig($slotId, $data)
    {
        self::$_slotSystemConfigSaveData[$slotId] = $data;
    }

    public function getSaveSlotSystemConfig()
    {
        return self::$_slotSystemConfigSaveData;
    }

    public function getSlotsData($storeId = null)
    {
        if (!isset(self::$_slotsData[$storeId])) {
            self::$_slotsData[$storeId] = $this->_apiHelper->getSlotsData($storeId);
        }
        return self::$_slotsData[$storeId];
    }

    public function getPanelsListData($storeId = null)
    {
        if (!isset(self::$_panelsData[$storeId])) {
            self::$_panelsData[$storeId] = $this->_apiHelper->getPanelsListData($storeId);
        }
        return self::$_panelsData[$storeId];
    }

    public function getProductAttributesListData($storeId = null)
    {
        if (!isset(self::$_productAttributesData[$storeId])) {
            self::$_productAttributesData[$storeId] = $this->_apiHelper->getProductAttributesListData($storeId);
        }
        return self::$_productAttributesData[$storeId];
    }

    public function getCustomerAttributesListData($storeId = null)
    {
        if (!isset(self::$_customerAttributesData[$storeId])) {
            self::$_customerAttributesData[$storeId] = $this->_apiHelper->getCustomerAttributesListData($storeId);
        }
        return self::$_customerAttributesData[$storeId];
    }

    public function getSystemConfigStoreId()
    {
        if (self::$_systemConfigStoreId==-1) {
            if (strlen($this->_getRequest()->getParam('store'))) {
                self::$_systemConfigStoreId = $this->_getRequest()->getParam('store');
            } elseif (strlen($this->_getRequest()->getParam('website'))) {
                self::$_systemConfigStoreId = $this->storeWebsiteFactory->create()
                    ->load($this->_getRequest()->getParam('website'))
                    ->getDefaultStore()
                    ->getId();
            } else {
                self::$_systemConfigStoreId = null;
            }
        }
        return self::$_systemConfigStoreId;
    }

    public function isSingleMode()
    {
        return $this->scopeConfig->getValue(
            Superb_Recommend_Helper_Api::XML_PATH_TRACKING_ACCOUNT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getSystemConfigStoreId()
        )==$this->scopeConfig->getValue(
            Superb_Recommend_Helper_Api::XML_PATH_TRACKING_ACCOUNT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isStoreMode()
    {
        return strlen($this->_getRequest()->getParam('store'));
    }
}
