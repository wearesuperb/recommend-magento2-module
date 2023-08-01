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

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const IDS_IMAGES_RECOMMEND_PANELS = [];

    const CUSTOM_PRODUCT_ATTRIBUTES = [];

    const CUSTOM_CUSTOMER_ATTRIBUTES = [
        [
            'code' => 'coupon_code',
            'title' => 'Coupon code',
            'type' => 'string',
            'entity_type' => 'contact'
        ]
    ];

    const XML_PATH_ENABLED                      = 'superbrecommend/general_settings/enabled';
    const XML_PATH_ACCOUNT_ID                   = 'superbrecommend/general_settings/account_id';
    const XML_PATH_DATA_CRON_ENABLED            = 'superbrecommend/data_cron/enabled';
    const XML_PATH_STATUS_CRON_ENABLED          = 'superbrecommend/status_cron/enabled';
    const XML_PATH_STATUS_CRON_PROMO_DOB        = 'superbrecommend/promo_dob_cron/enabled';
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

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\Helper\Context $context,
        CollectionFactory $categoryCollection
    ) {
        $this->storeManager = $storeManager;
        $this->_encryptor = $encryptor;
        $this->scopeConfig = $context->getScopeConfig();
        $this->categoryCollection = $categoryCollection;
        parent::__construct($context);
    }

    public function getIdsImagesRecommendPanels()
    {
        return self::IDS_IMAGES_RECOMMEND_PANELS;
    }

    public function getCustomProductAttributes()
    {
        return self::CUSTOM_PRODUCT_ATTRIBUTES;
    }

    public function getCustomCustomerAttributes()
    {
        return self::CUSTOM_CUSTOMER_ATTRIBUTES;
    }

    public function isEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
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

    public function isStatusCronPromoDobEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_STATUS_CRON_PROMO_DOB,
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

    public function getWebsitesList(): array
    {
        return $this->storeManager->getWebsites();
    }

    public function getCategoryCollection($rootCatId)
    {
        try {
            return $this->categoryCollection->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('path', array('like' => "1/{$rootCatId}/%"));
        } catch (LocalizedException $e) {
        }
    }

    public function getStatusMapRecommend($data): ?int
    {
        $map = [
            'subscribed' => Subscriber::STATUS_SUBSCRIBED,
            'unsubscribed' => Subscriber::STATUS_UNSUBSCRIBED,
            'non_subscribed' => Subscriber::STATUS_NOT_ACTIVE
        ];
        return $map[$data] ?? null;
    }
}
