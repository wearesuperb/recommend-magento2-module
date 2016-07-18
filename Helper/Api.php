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

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLED                          = 'superbrecommend/general_settings/enabled';
    const XML_PATH_TRACKING_ACCOUNT_ID              = 'superbrecommend/general_settings/account_id';
    const XML_PATH_API_URL                          = 'superbrecommend/general_settings/api_url';
    const XML_PATH_API_USERNAME                     = 'superbrecommend/api_settings/username';
    const XML_PATH_API_KEY                          = 'superbrecommend/general_settings/api_key';
    const XML_PATH_API_ACCESS_TOKEN                 = 'superbrecommend/general_settings/api_access_token';
    const XML_PATH_API_SHOW_OUT_OF_STOCK_PRODUCTS   = 'superbrecommend/panels/show_out_of_stock_products';

    protected $_tokenData = array();

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
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

    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->backendSession = $backendSession;
        $this->_encryptor = $encryptor;
        $this->productMetadata = $productMetadata;
    }

    protected function _getGetTokenUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/authenticate';
    }

    protected function _getUpdateAccountUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/update';
    }

    protected function _getUploadProductsDataUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/products/update';
    }

    protected function _getGetProductsPageviewsDataUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/products/pageviews';
    }

    protected function _getGetSlotsPageTypesDataUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/pagetypes';
    }

    protected function _getGetPanelsListDataUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/panels/search';
    }

    protected function _getGetProductAttributesListDataUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/productattributes/search';
    }

    protected function _getGetCustomerAttributesListDataUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/customerattributes/search';
    }

    protected function _getUpdateSlotsUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/slots/update';
    }

    protected function _getGetSlotsDataUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId).'v1/'.urlencode($this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId)).'/slots';
    }

    protected function _getAccessToken($storeId = null)
    {
        if (!isset($this->_tokenData[$storeId]) || (is_array($this->_tokenData[$storeId]) && !isset($this->_tokenData[$storeId]['token'])) || (is_array($this->_tokenData[$storeId]) && isset($this->_tokenData[$storeId]['expires_date']) && (time()>$this->_tokenData[$storeId]['expires_date'])))
        {
            $ch = curl_init();
            $data_string = json_encode(array('key'=>$this->_encryptor->decrypt($this->scopeConfig->getValue(self::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$storeId))));
            curl_setopt($ch, CURLOPT_URL, $this->_getGetTokenUrl($storeId));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($data_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            try {
                $responseBody = curl_exec($ch);
                $tokenData = @json_decode($responseBody,true);
                if (isset($tokenData['success']) && $tokenData['success']==true)
                {
                    $this->_tokenData[$storeId] = $tokenData['token'];
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
        if (isset($this->_tokenData[$storeId]) && is_array($this->_tokenData[$storeId]) && isset($this->_tokenData[$storeId]['token']))
        {
            return $this->_tokenData[$storeId]['token'];
        }
    }

    public function uploadProductsData($products,$storeId = null)
    {
        $ch = curl_init();
        $data_string = json_encode(array('products'=>$products));
        curl_setopt($ch, CURLOPT_URL, $this->_getUploadProductsDataUrl($storeId));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getProductsPageviewsData($hours,$storeId = null)
    {
        $ch = curl_init();
        $data_string = json_encode(array('hours'=>$hours));
        curl_setopt($ch, CURLOPT_URL, $this->_getGetProductsPageviewsDataUrl($storeId));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true && isset($response['products']) && is_array($response['products']))
            {
                $productsData = $response['products'];
                return $productsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getSlotsPageTypesData($storeId = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_getGetSlotsPageTypesDataUrl($storeId));
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true && isset($response['results']) && is_array($response['results']))
            {
                $slotsPageTypesData = $response['results'];
                return $slotsPageTypesData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getSlotsData($storeId = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_getGetSlotsDataUrl($storeId));
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true && isset($response['results']) && is_array($response['results']))
            {
                $slotsData = $response['results'];
                return $slotsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function updateSlots($slotsData,$storeId = null)
    {
        $ch = curl_init();
        $data_string = json_encode(array('slots'=>$slotsData));
        curl_setopt($ch, CURLOPT_URL, $this->_getUpdateSlotsUrl($storeId));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true)
                return true;
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getPanelsListData($storeId = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_getGetPanelsListDataUrl($storeId));
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true && isset($response['results']) && is_array($response['results']))
            {
                $panelsData = $response['results'];
                return $panelsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getProductAttributesListData($storeId = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_getGetProductAttributesListDataUrl($storeId));
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true && isset($response['results']) && is_array($response['results']))
            {
                $panelsData = $response['results'];
                return $panelsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getCustomerAttributesListData($storeId = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_getGetCustomerAttributesListDataUrl($storeId));
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true && isset($response['results']) && is_array($response['results']))
            {
                $panelsData = $response['results'];
                return $panelsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function updateAccount($storeId = null)
    {
        $ch = curl_init();
        $data_string = json_encode(array('currency'=>$this->storeManager->getStore($storeId)->getBaseCurrencyCode(),'platform'=>'magento','platform_version'=>$this->productMetadata->getVersion()));
        curl_setopt($ch, CURLOPT_URL, $this->_getUpdateAccountUrl($storeId));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $headers = array();
        $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        try {
            $responseBody = curl_exec($ch);
            $response = json_decode($responseBody,true);
            if (isset($response['success']) && $response['success']==true)
                return true;
            elseif (isset($response['error']) && $response['error']==true && isset($response['error_message']) && $response['error_message']=='Base currency can not be changed after build.')
            {
                $this->backendSession->addError(__('Once you have posted transactions and accounts using the base currency, you cannot change the base currency.'));
                return false;
            }
            elseif (isset($response['error']) && isset($response['error_message']) && $response['error_message']=='Access denied')
            {
                $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getShowOutOfStockProduct(){
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_API_SHOW_OUT_OF_STOCK_PRODUCTS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
