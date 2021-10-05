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

use Magento\Framework\App\ObjectManager;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLED                      = 'superbrecommend/general_settings/enabled';
    const XML_PATH_ACCOUNT_ID                   = 'superbrecommend/general_settings/account_id';
    const XML_PATH_DATA_CRON_ENABLED            = 'superbrecommend/data_cron/enabled';
    const XML_PATH_STATUS_CRON_ENABLED          = 'superbrecommend/status_cron/enabled';
    const XML_PATH_STATUS_HASHCODE              = 'superbrecommend/general_settings/hashcode';
    const LIMIT_STEP                            = 1000;

    const XML_PATH_TRACKING_PRODUCT_ATTRIBUTES  = 'superbrecommend/general_settings/product_attributes';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->storeManager = $storeManager;
        $this->_encryptor = $encryptor;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    public function isEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $websiteId
     * @return bool
     */
    public function isEnabledWebSiteScope($websiteId)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
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

    public function getAccountId($websiteId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ACCOUNT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    public function getHashSecretKey($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_STATUS_HASHCODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->_encryptor->decrypt($value);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    public function getWebsiteCode()
    {
        $websiteId = $this->getCurrentStore()->getWebsiteId();
        return $this->storeManager->getWebsite($websiteId)->getCode();
    }

    public function getProductData($product,$productAttributes,$enviroments,$defaultStore)
    {
        $attributes=[];
        foreach($productAttributes as $productAttribute){
            $attributes[] = [
                'code'=>$productAttribute['magento_attribute'],
                'value'=>$product->getData($productAttribute['magento_attribute'])
            ];
        }

        if($product->getSpecialPrice()){
            $original_price[] = [
                'code'=>'default',
                'prices'=>[0=>[
                    'currency'=>$defaultStore->getCurrentCurrency()->getCode(),
                    'value'=>(float)$product->getPrice()
                ]]
            ];
        } else {
            $original_price = [];
        }

        $price[] = [
            'code'=>'default',
            'prices'=>[0=>[
                'currency'=>$defaultStore->getCurrentCurrency()->getCode(),
                'value'=>(float)$product->getFinalPrice()
            ]]
        ];

        $env = [];
        if(isset($enviroments[$product->getId()])){
            foreach($enviroments[$product->getId()] as $code=>$enviroment){
                $env[] = [
                    'code'=>$code,
                    'data'=>[
                        'status'=>$enviroment['status'],
                        'name'=>$enviroment['name'],
                        'lists'=>$enviroment['lists'],
                        'url'=>$enviroment['url'],
                        'image'=>$enviroment['image'],
                        'description'=>$enviroment['description'],
                        'attributes'=>$enviroment['attributes'],
                        'price'=>$enviroment['price'],
                        'original_price'=>$enviroment['original_price']
                    ]
                ];
            }
        }

        $batchData = [
            'action' => 'upsert_update',
            'data' => [
                'id'=>$product->getId(),
                'status'=>$product->getStatus()==1?true:false,
                'sku'=>$product->getSku(),
                'name'=>$product->getName(),
                'lists'=>$product->getCategoryIds(),
                'url'=>$product->getProductUrl(),
                'image'=>$defaultStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' .$product->getImage(),
                'description'=>$product->getShortDescription(),
                'attributes'=>$attributes,
                'environment'=>$env,
                'price'=>$price,
                'original_price'=>$original_price
            ]
        ];

        return $batchData;
    }
}
