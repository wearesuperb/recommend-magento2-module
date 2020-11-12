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

namespace Superb\Recommend\Helper\Tracker;

use Magento\Framework\App\ProductMetadataInterface;

class Data extends \Superb\Recommend\Helper\Data
{
    const XML_PATH_TRACKING_URL                 = 'superbrecommend/general_settings/server_url';
    const XML_PATH_TRACKING_URL_SECURE          = 'superbrecommend/general_settings/server_secure_url';
    const XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES = 'superbrecommend/general_settings/customer_attributes';
    const XML_PATH_TRACKING_MEDIA_THUMB_SOURCE  = 'superbrecommend/panels/media_thumb_source';
    const XML_PATH_TRACKING_MEDIA_THUMB_WIDTH   = 'superbrecommend/panels/media_thumb_width';
    const XML_PATH_TRACKING_MEDIA_THUMB_HEIGHT  = 'superbrecommend/panels/media_thumb_height';
    const XML_PATH_TRACKING_VERSION  		    = 'superbrecommend/general_settings/track_version';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $helper;


    protected $productMetadata;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        \Superb\Recommend\Helper\Data $helper
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function getApiUrl()
    {
        if ($this->storeManager->getStore()->isCurrentlySecure()) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_TRACKING_URL_SECURE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            return $this->scopeConfig->getValue(
                self::XML_PATH_TRACKING_URL,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }

    public function getTrackerVersion()
    {
	    return $this->scopeConfig->getValue(
                self::XML_PATH_TRACKING_VERSION,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    public function getApiJsUrl()
    {
        if ($this->getTrackerVersion()=='35') {
            if ($this->storeManager->getStore()->isCurrentlySecure()) {
                    return $this->scopeConfig->getValue(
                        self::XML_PATH_TRACKING_URL_SECURE,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ).'trackerv35.js';
            } else {
                return $this->scopeConfig->getValue(
                        self::XML_PATH_TRACKING_URL,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ).'trackerv35.js';
            }
        } else {
            if ($this->storeManager->getStore()->isCurrentlySecure()) {
                    return $this->scopeConfig->getValue(
                        self::XML_PATH_TRACKING_URL_SECURE,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ).'trackerv14.js';
            } else {
                return $this->scopeConfig->getValue(
                        self::XML_PATH_TRACKING_URL,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ).'trackerv14.js';
            }
        }
    }

    public function getThumbSource()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_MEDIA_THUMB_SOURCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getThumbWidth()
    {
        $width = $this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_MEDIA_THUMB_WIDTH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return empty($width)?null:$width;
    }

    public function getThumbHeight()
    {
        $height = $this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_MEDIA_THUMB_HEIGHT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return empty($height)?null:$height;
    }

    public function getCurrentStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getProductUpdateAttributes()
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_PRODUCT_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $this->helper->unserialize($value);
    }

    public function getCustomerUpdateAttributes()
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $this->helper->unserialize($value);
    }
}
