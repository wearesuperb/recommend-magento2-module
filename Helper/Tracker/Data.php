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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends \Superb\Recommend\Helper\Data
{
    const XML_PATH_TRACKING_URL                 = 'superbrecommend/general_settings/server_url';
    const XML_PATH_TRACKING_URL_SECURE          = 'superbrecommend/general_settings/server_secure_url';
    const XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES = 'superbrecommend/general_settings/customer_attributes';
    const XML_PATH_TRACKING_MEDIA_THUMB_SOURCE  = 'superbrecommend/panels/media_thumb_source';
    const XML_PATH_TRACKING_MEDIA_THUMB_WIDTH   = 'superbrecommend/panels/media_thumb_width';
    const XML_PATH_TRACKING_MEDIA_THUMB_HEIGHT  = 'superbrecommend/panels/media_thumb_height';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;


    protected $productMetadata;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
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

    public function getApiJsUrl()
    {
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
        return $this->unserialize($value);
    }

    public function getCustomerUpdateAttributes()
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $this->unserialize($value);
    }

    protected function unserialize($value)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            try {
                $value = ObjectManager::getInstance()->create(SerializerInterface::class)->unserialize($value);
            } catch (\InvalidArgumentException $invalidArgumentException) {
                $value = [];
            }
        } else {
            $value = @unserialize($value);
        }
        return is_array($value) ? $value : [];
    }
}
