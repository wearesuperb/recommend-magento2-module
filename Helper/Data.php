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

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLED                      = 'superbrecommend/general_settings/enabled';
    const XML_PATH_ACCOUNT_ID                   = 'superbrecommend/general_settings/account_id';
    const XML_PATH_DASHBOARD_ENABLED            = 'superbrecommend/general_settings/dashboard';
    const XML_PATH_ADVANCED                     = 'superbrecommend/general_settings/advanced';
    const XML_PATH_DATA_CRON_ENABLED            = 'superbrecommend/data_cron/enabled';
    const XML_PATH_STATUS_CRON_ENABLED          = 'superbrecommend/status_cron/enabled';
    const LIMIT_STEP                            = 1000;

    const XML_PATH_TRACKING_PRODUCT_ATTRIBUTES  = 'superbrecommend/general_settings/product_attributes';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    public function getIsAdvancedModeEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ADVANCED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isDashboardEnabled()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DASHBOARD_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isDataCronEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DATA_CRON_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isStatusCronEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_STATUS_CRON_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAccountId($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ACCOUNT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function unserialize($value)
    {
        if (class_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Serialize\SerializerInterface::class);
            $value = $serializer->unserialize($value);
        } else {
            $value = ObjectManager::getInstance()->create('Magento\Framework\Unserialize\Unserialize')->unserialize($value);
        }
        return is_array($value) ? $value : [];
    }

    public function serialize($value)
    {
        if (class_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Serialize\SerializerInterface::class);
            $value = $serializer->serialize($value);
        }else{
            $value = \serialize($value);
        }

        return is_array($value) ? $value : [];
    }
}
