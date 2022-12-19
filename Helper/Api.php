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

use Magento\Framework\AppInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_API_URL                               = 'https://api.recommend.pro/v3/';
    const XML_PATH_API_KEY                          = 'superbrecommend/general_settings/api_key';
    const XML_PATH_API_ACCESS_TOKEN                 = 'superbrecommend/general_settings/api_access_token';
    const XML_PATH_API_SHOW_OUT_OF_STOCK_PRODUCTS   = 'superbrecommend/panels/show_out_of_stock_products';
    const XML_PATH_TRACKING_PRODUCT_ATTRIBUTES  = 'superbrecommend/general_settings/product_attributes';
    const XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES  = 'superbrecommend/general_settings/customer_attributes';

    protected $_tokenData = [];

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Superb\Recommend\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Superb\Recommend\Helper\Data $helper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Superb\Recommend\Logger\Logger $logger,
        SerializerInterface $serializer,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->backendSession = $backendSession;
        $this->_encryptor = $encryptor;
        $this->productMetadata = $productMetadata;
        $this->_helper = $helper;
        $this->serializer = $serializer;
        $this->eavConfig = $eavConfig;
        $this->websiteRepository = $websiteRepository;
        parent::__construct($context);
    }

    protected function _getApiUrl($websiteCode=null)
    {
        if(isset($websiteCode)){
            return self::XML_API_URL.$this->_helper->getAccountId($this->getWebsite($websiteCode)->getId());
        } else {
            return self::XML_API_URL.$this->_helper->getAccountId($this->getDefaultWebsite()->getId());
        }
    }

    protected function _getGetTokenUrl($websiteCode)
    {
        return $this->_getApiUrl($websiteCode).'/authenticate';
    }

    protected function _getUpdateAccountUrl($storeId=null)
    {
        return $this->_getApiUrl($storeId).'/config';
    }

    protected function _getBuildAccountUrl($storeId=null)
    {
        return $this->_getApiUrl($storeId).'/build';
    }

    protected function _getAttributesUrl($type, $storeId = null, $code = null)
    {
        if ($code) {
            return $this->_getApiUrl($storeId).'/attribute/'.$type.'/'.$code;
        } else {
            return $this->_getApiUrl($storeId).'/attribute/'.$type;
        }
    }

    protected function _getUploadCatalogUrl($websiteCode,$upload_id=null,$type=null)
    {
        if(isset($upload_id)&&$type=='commit'){
            return $this->_getApiUrl($websiteCode).'/catalog/upload/'.$upload_id.'/commit';
        } elseif ($type=='init'){
            return $this->_getApiUrl($websiteCode).'/catalog/upload/';
        } elseif (isset($upload_id)&&isset($websiteCode)){
            return $this->_getApiUrl($websiteCode).'/catalog/upload/'.$upload_id.'/store/'.$websiteCode.'/'.$type;
        } else {
            return $this->_getApiUrl($websiteCode).'/catalog/upload/store/'.$websiteCode.'/'.$type;
        }
    }

    protected function _getEnviromentsUrl($websiteCode,$code=null)
    {
        if(isset($code)) {
            return $this->_getApiUrl($websiteCode).'/environment/'.$code;
        } else {
            return $this->_getApiUrl($websiteCode).'/environment';
        }
    }

    protected function _getStoreUrl($websiteCode)
    {
        return $this->_getApiUrl($websiteCode).'/store/'.$websiteCode;
    }

    protected function _getUploadOrdersUrl($websiteCode=null)
    {
        return $this->_getApiUrl($websiteCode).'/order/batch';
    }

    protected function _getChennelUrl($websiteCode=null)
    {
        return $this->_getApiUrl($websiteCode).'/messaging/channel/batch/email';
    }

    protected function _getContactUrl($websiteCode=null)
    {
        return $this->_getApiUrl($websiteCode).'/contact/batch/email';
    }

    protected function _getUploadCartUrl($websiteCode=null)
    {
        return $this->_getApiUrl($websiteCode).'/cart/batch';
    }

    protected function _getPanelsUrl($websiteCode=null)
    {
        return $this->_getApiUrl($websiteCode).'/recommendation/panel';
    }

    protected function _getRulesetUrl($websiteCode=null)
    {
        return $this->_getApiUrl($websiteCode).'/catalog/ruleset';
    }

    protected function _getPositionUrl($websiteCode,$category,$ruleset)
    {
        return $this->_getApiUrl($websiteCode).'/catalog/list/'.$category.'/ruleset/'.$ruleset.'/scores';
    }

    protected function _getCreateWebhookUrl($websiteCode, string $webhookCode)
    {
        return $this->_getApiUrl($websiteCode) . '/webhook/' . $webhookCode;
    }

    protected function _getDeleteWebhookUrl($websiteCode, string $webhookCode)
    {
        return $this->_getApiUrl($websiteCode) . '/webhook/' . $webhookCode;
    }

    public function getWebsite($websiteCode)
    {
        return $this->websiteRepository->get($websiteCode);
    }

    public function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }

    public function getAttributes($websiteCode)
    {
        $productAttributes = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_PRODUCT_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->getWebsite($websiteCode)->getId()
        );

        return $this->serializer->unserialize($productAttributes);
    }

    public function getCustomerAttributes($websiteCode)
    {
        $customerAttributes = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->getWebsite($websiteCode)->getId()
        );

        return $this->serializer->unserialize($customerAttributes);
    }

    public function getDefaultWebsite()
    {
        $websiteId = (int)$this->storeManager->getDefaultStoreView()->getWebsiteId();
        $website = $this->storeManager->getWebsite($websiteId);
        return $website;
    }

    protected function getRecommendAttributes($storeId,$type)
    {
        try {
            $response = $this->_callApi(
                $this->_getAttributesUrl($type, $storeId),
                $storeId,
                [],
                'GET'
            );

            if (isset($response['success']) && $response['success']==true) {
                return $response['result'];
            }
            $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
            return false;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function getPanels($storeId=null)
    {
        try {
            $response = $this->_callApi(
                $this->_getPanelsUrl($storeId),
                $storeId,
                [],
                'GET'
            );

            if (isset($response['success']) && $response['success']==true) {
                return $response['result'];
            }
            $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
            return false;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    protected function createRecommendAttribute($storeId,$type,$code,$data)
    {
        try {
            $response = $this->_callApi(
                $this->_getAttributesUrl($type, $storeId, $code),
                $storeId,
                $data,
                'POST'
            );

            if (isset($response['success']) && $response['success']==true) {
                return true;
            } elseif(isset($response['success']) && $response['success']==false) {
                $this->backendSession->addError($response['error_message']);
            }
            return false;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    protected function deleteRecommendAttribute($storeId,$type,$code)
    {
        try {
            $response = $this->_callApi(
                $this->_getAttributesUrl($type, $storeId, $code),
                $storeId,
                [],
                'DELETE'
            );

            if (isset($response['success']) && $response['success']==true) {
                return true;
            } elseif(isset($response['success']) && $response['success']==false) {
                $this->backendSession->addError($response['error_message']);
            }
            return false;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    protected function mappingAttributeType($type)
    {
        switch ($type) {
            case 'varchar' :
                $map = 'string';
                break;
            default :
                $map = $type;
        }
        return $map;
    }

    protected function updateProductAttributes($storeId = null)
    {
        $productAttributes = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_PRODUCT_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $productAttributes = $this->serializer->unserialize($productAttributes);
        $recommendProductAttributes = $this->getRecommendAttributes($storeId,'product');
        $currentAttributes = [];
        $eavConfig = $this->eavConfig;
        $recommendCodes = array_column($recommendProductAttributes, 'code');

        foreach ($productAttributes as $row) {
            $attribute = $eavConfig->getAttribute('catalog_product', $row['magento_attribute']);
            if ($attribute && $attribute->getId()) {
                $currentAttributes[] = $row['magento_attribute'];
                $data = ['title'=>$attribute->getFrontendLabel(),'type'=>'string','data_type'=>'direct'];
                if(array_search($row['magento_attribute'], $recommendCodes)===FALSE){
                    $this->createRecommendAttribute($storeId,'product',$row['magento_attribute'],$data);
                }
            }
        }

        /*foreach ($recommendProductAttributes as $rAtrr) {
            if (!in_array($rAtrr['code'],$currentAttributes)) {
                $this->deleteRecommendAttribute($storeId,'product',$rAtrr['code']);
            }
        }*/
    }

    protected function updateCustomerAttributes($storeId = null)
    {
        $customerAttributes = (string)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $customerAttributes = $this->serializer->unserialize($customerAttributes);
        $recommendCustomerAttributes = $this->getRecommendAttributes($storeId,'contact');
        $currentAttributes = [];
        $eavConfig = $this->eavConfig;
        $recommendCodes = array_column($recommendCustomerAttributes, 'code');

        foreach ($customerAttributes as $row) {
            $attribute = $eavConfig->getAttribute('customer', $row['magento_attribute']);
            if ($attribute && $attribute->getId()) {
                $currentAttributes[] = $row['magento_attribute'];
                $data = ['title'=>$attribute->getFrontendLabel(),'type'=>'string','data_type'=>'direct'];
                if(array_search($row['magento_attribute'], $recommendCodes)===FALSE){
                    $this->createRecommendAttribute($storeId,'contact',$row['magento_attribute'],$data);
                }
            }
        }

        /*foreach ($recommendCustomerAttributes as $rAtrr) {
            if (!in_array($rAtrr['code'],$currentAttributes)) {
                $this->deleteRecommendAttribute($storeId,'contact',$rAtrr['code']);
            }
        }*/
    }

    protected function _getAccessToken($websiteCode = null)
    {
        if (!isset($this->_tokenData[$websiteCode]) ||
            (
                is_array($this->_tokenData[$websiteCode]) &&
                !isset($this->_tokenData[$websiteCode]['token'])
            ) || (is_array($this->_tokenData[$websiteCode]) &&
                isset($this->_tokenData[$websiteCode]['expires_date']) &&
                (time()>$this->_tokenData[$websiteCode]['expires_date']))
        ) {
            try {
                $tokenData = $this->_callApi(
                    $this->_getGetTokenUrl($websiteCode),
                    $websiteCode,
                    [
                        'key'=>$this->_encryptor->decrypt(
                            $this->scopeConfig->getValue(
                                self::XML_PATH_API_KEY,
                                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                                $this->getWebsite($websiteCode)->getId()
                            )
                        )
                    ],
                    'POST',
                    true
                );

                if (isset($tokenData['success']) && $tokenData['success']==true) {
                    $this->_tokenData[$websiteCode] = $tokenData['result']['auth'];
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        if (isset($this->_tokenData[$websiteCode]) &&
            is_array($this->_tokenData[$websiteCode]) &&
            isset($this->_tokenData[$websiteCode]['token'])
        ) {
            return $this->_tokenData[$websiteCode]['token'];
        }
    }

    protected function _buildAccount($storeId = null)
    {
        try {
            $response = $this->_callApi(
                $this->_getBuildAccountUrl($storeId),
                $storeId,
                [
                    'currency_code' => $this->getDefaultWebsite()->getBaseCurrencyCode(),
                    'store_code' => $this->getDefaultWebsite()->getCode(),
                    'platform'=>'magento',
                    'platform_version'=>$this->productMetadata->getVersion()
                ],
                'PUT'
            );

            if (isset($response['success']) && $response['success']==true) {
                return true;
            } else {
                $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
                return false;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function updateAccount($storeId = null)
    {
        try {
            $response = $this->_callApi(
                $this->_getUpdateAccountUrl($storeId),
                $storeId,
                [
                    'default_currency'=>$this->getDefaultWebsite()->getBaseCurrencyCode(),
                    'default_store'=>$this->getDefaultWebsite()->getCode(),
                    'default_price_list'=>'default'
                ],
                'PUT'
            );

            if (isset($response['success']) && $response['success']==true) {

                $this->updateProductAttributes($storeId);
                $this->updateCustomerAttributes($storeId);

                return true;
            } elseif (isset($response['success']) && $response['success']==false &&
                isset($response['error_message']) &&
                $response['error_message']=='Base currency can not be changed after build.'
            ) {
                $this->backendSession->addError(
                    __('Once you have posted transactions and accounts using the base currency, you cannot change the base currency.')
                );
                return false;
            } elseif (isset($response['success']) && $response['success']==false &&
                $response['error_message']=='Access denied'
            ) {
                $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
                return false;
            } elseif (isset($response['success']) && $response['success']==false &&
                $response['error_message']=='Build your account'
            ) {
                return $this->_buildAccount($storeId);
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    protected function _callApi($url, $websiteCode, $data = null, $method = null, $isAuthTokenRequest = false, $headers = [])
    {
        $_ch = curl_init();
        curl_setopt($_ch, CURLOPT_URL, $url);
        if (!$isAuthTokenRequest) {
            $headers[] = 'Authorization: Bearer'.$this->_getAccessToken($websiteCode);
        }
        if($data !== null){
            $data_string = json_encode($data);
            $this->_logger->critical($data_string);
            curl_setopt($_ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($_ch, CURLOPT_POSTFIELDS, $data_string);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($data_string);
        }
        curl_setopt($_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
        $responseBody = curl_exec($_ch);
        curl_close($_ch);
        $this->_logger->critical($responseBody);
        $response = \json_decode($responseBody, true);

        return $response;
    }

    protected function _createStore($websiteCode)
    {
        try {
            $response = $this->_callApi(
                $this->_getStoreUrl($websiteCode),
                $websiteCode,
                [
                    'title' => $websiteCode
                ],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function initUpload($websiteCode)
    {
        try {
            $response = $this->_callApi(
                $this->_getUploadCatalogUrl($websiteCode,null,'init'),
                $websiteCode,
                [
                    'mode' => 'append',
                    'level' => [
                        'mode' => 'store',
                        'store_code' => $websiteCode
                    ]
                ],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return $response['result']['upload_id'];
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function syncCategories($data,$websiteCode,$upload_id)
    {
        try {
            $chunkData = array_chunk($data, 100);
            $counter = 1;
            foreach($chunkData as $batchData) {
                $response = $this->_callApi(
                    $this->_getUploadCatalogUrl($websiteCode, $upload_id, 'list_batch'),
                    $websiteCode,
                    [
                        'data' => $batchData
                    ],
                    'POST'
                );

                if (isset($response['success']) && $response['success'] == false && $response['error_message'] == 'Store not exists') {
                    if ($this->_createStore($websiteCode)) {
                        $this->syncCategories($batchData, $websiteCode, $upload_id);
                    }
                }
                $counter++;
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function commitBatch($websiteCode,$uploadId)
    {
        try {
            $response = $this->_callApi(
                $this->_getUploadCatalogUrl($websiteCode,$uploadId,'commit'),
                $websiteCode,
                [],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function syncProducts($data,$websiteCode,$upload_id)
    {
        try {
            $chunkData = array_chunk($data, 100);
            $counter = 1;
            foreach($chunkData as $batchData) {
                $response = $this->_callApi(
                    $this->_getUploadCatalogUrl($websiteCode, $upload_id, 'product_batch'),
                    $websiteCode,
                    [
                        'data' => $batchData
                    ],
                    'POST'
                );

                $counter++;
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function syncVariants($data,$websiteCode,$upload_id)
    {
        try {
            $chunkData = array_chunk($data, 100);
            $counter = 1;
            foreach($chunkData as $batchData) {
                $response = $this->_callApi(
                    $this->_getUploadCatalogUrl($websiteCode, $upload_id, 'variation_batch'),
                    $websiteCode,
                    [
                        'data' => $batchData
                    ],
                    'POST'
                );

                $counter++;
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }


    public function getEnviroment($websiteCode,$code)
    {
        try {
            $response = $this->_callApi(
                $this->_getEnviromentsUrl($websiteCode,$code),
                $websiteCode,
                [],
                'GET'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return $response['result'];
            } elseif ($response['error_message']=='Environment not exists') {
                $this->createEnviroment($websiteCode,$code);
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function createEnviroment($websiteCode,$code)
    {
        try {
            $response = $this->_callApi(
                $this->_getEnviromentsUrl($websiteCode,$code),
                $websiteCode,
                [
                    'title'=>$code,
                    'locale_code'=>'en-GB'
                ],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return $response['result'];
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function syncOrders($data)
    {
        try {
            $chunkData = array_chunk($data, 100);
            $counter = 1;
            foreach($chunkData as $batchData) {
                $response = $this->_callApi(
                    $this->_getUploadOrdersUrl($this->getDefaultWebsite()->getCode()),
                    $this->getDefaultWebsite()->getCode(),
                    [
                        'data' => $batchData
                    ],
                    'POST'
                );

                $counter++;
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function sendChennelData($data,$websiteCode=null)
    {
        if(!isset($websiteCode)){
            $websiteCode = $this->getDefaultWebsite()->getCode();
        }
        try {
            $chunkData = array_chunk($data, 500);
            $counter = 1;
            foreach($chunkData as $batchData) {
                $response = $this->_callApi(
                    $this->_getChennelUrl($websiteCode),
                    $websiteCode,
                    [
                        'data' => $batchData
                    ],
                    'POST'
                );

                $counter++;
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function sendCustomer($data,$websiteCode=null)
    {
        if(!isset($websiteCode)){
            $websiteCode = $this->getDefaultWebsite()->getCode();
        }
        try {
            $chunkData = array_chunk($data, 500);
            $counter = 1;
            foreach($chunkData as $batchData) {
                $response = $this->_callApi(
                    $this->_getContactUrl($websiteCode),
                    $websiteCode,
                    [
                        'data' => $batchData
                    ],
                    'POST'
                );
                $counter++;
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function uploadCategories($batchData,$websiteCode)
    {
        try {
            $response = $this->_callApi(
                $this->_getUploadCatalogUrl($websiteCode,null,'list_batch'),
                $websiteCode,
                [
                    'data' => $batchData
                ],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return true;
            } elseif (isset($response['success'])&&$response['success']==false&&$response['error_message']=='Store not exists') {
                if ($this->_createStore($websiteCode)) {
                    $this->uploadCategories($batchData,$websiteCode,$upload_id);
                }
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function uploadProducts($batchData,$websiteCode)
    {
        try {
            $response = $this->_callApi(
                $this->_getUploadCatalogUrl($websiteCode,null,'product_batch'),
                $websiteCode,
                [
                    'data' => $batchData
                ],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function uploadVariants($batchData,$websiteCode)
    {
        try {
            $response = $this->_callApi(
                $this->_getUploadCatalogUrl($websiteCode,null,'variation_batch'),
                $websiteCode,
                [
                    'data' => $batchData
                ],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function uploadCart($quote)
    {
        try {
            $websiteId = $this->getStore($quote->getStoreId())->getWebsiteId();
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();

            $quoteItems = $quote->getAllItems();
            $typeConfi = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
            $items = [];
            foreach($quoteItems as $item){
                if ($item->getProduct()->getTypeId() == $typeConfi) {
                    continue;
                }
		if($item->getParentItem()){
            	    $product = $item->getParentItem()->getProduct();
		    $price = (float)$item->getParentItem()->getData('price');
		    $baseprice = (float)$item->getParentItem()->getData('base_price');
		    $qty = $item->getParentItem()->getQty();
		}else{
		    $product = $item->getProduct();
		    $price = (float)$item->getData('price');
		    $baseprice = (float)$item->getData('base_price');
		    $qty = $item->getQty();
		}

                $items[] = [
                    'name' => $product->getData('name'),
                    'sku' => $product->getData('sku'),
                    'variation_sku' => $item->getSku(),
                    'price' => $price,
                    'base_price' => $baseprice,
                    'image' => $product->getData('small_image'),
                    'url' => $product->getProductUrl(),
                    'quantity' => (int) $qty
                ];
            }
            $batchData[] = [
                'action' => 'upsert_update',
                'data' => [
                    'cart_id' => $quote->getId(),
                    'customer_id' => (string)$quote->getCustomerId(),
                    'currency' => $quote->getQuoteCurrencyCode(),
                    'base_currency' => $quote->getBaseCurrencyCode(),
                    'total' => (float)$quote->getGrandTotal(),
                    'base_total' => (float)$quote->getBaseGrandTotal(),
                    'items' => $items
                ]
            ];

            $response = $this->_callApi(
                $this->_getUploadCartUrl($websiteCode),
                $websiteCode,
                [
                    'data' => $batchData
                ],
                'POST'
            );

            if (isset($response['success'])&&$response['success']==true) {
                return true;
            }
            return false;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function getRuleset($storeId=null)
    {
        try {
            $response = $this->_callApi(
                $this->_getRulesetUrl($storeId),
                $storeId,
                [],
                'GET'
            );

            if (isset($response['success']) && $response['success']==true) {
                return $response['result'];
            }
            $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
            return false;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function getPosition($category,$ruleset, $storeId = null)
    {
        try {
            $response = $this->_callApi(
                $this->_getPositionUrl($storeId,$category,$ruleset),
                $storeId,
                [],
                'GET'
            );

            if (isset($response['success']) && $response['success']==true) {
                return $response['result'];
            }
            $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
            return false;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    public function createWebhook(string $webhookUrl, string $webhookCode, string $eventName, string $secretKey, $websiteCode = null)
    {
        try {
            $result  = false;
            $apiData = [
                'url' => $webhookUrl,
                'events' => [$eventName],
                'secret_key' => $secretKey
            ];
            $response = $this->_callApi(
                $this->_getCreateWebhookUrl($websiteCode, $webhookCode),
                $websiteCode,
                $apiData,
                'POST'
            );

            if (isset($response['success']) && $response['success']==true) {
                $result = true;
            } else {
                $this->logger->error(sprintf('%s %s() Error created webhook with params: %s %s %s', __CLASS__, __FUNCTION__, $webhookCode, $webhookUrl, $eventName));
                $this->logger->error(json_encode($response));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return $result;
    }

    public function deleteWebhook(string $webhookCode, $websiteCode = null)
    {
        try {
            $result  = false;
            $apiData = [];
            $response = $this->_callApi(
                $this->_getDeleteWebhookUrl($websiteCode, $webhookCode),
                $websiteCode,
                $apiData,
                'DELETE'
            );

            if (isset($response['success']) && $response['success']==true) {
                $result = true;
            } else {
                $this->logger->error(sprintf('%s %s() Error delete webhook with params: %s', __CLASS__, __FUNCTION__, $webhookCode));
                $this->logger->error(json_encode($response));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return $result;
    }
}
