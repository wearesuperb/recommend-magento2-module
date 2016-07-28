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

    protected $_tokenData = [];

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

    protected $_ch;

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

    protected function _getApiUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_URL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    protected function _getAccountId($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_ACCOUNT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    protected function _getAccountApiUrl($storeId = null)
    {
        return $this->_getApiUrl($storeId).'v1/'.urlencode($this->_getAccountId($storeId));
    }

    protected function _getGetTokenUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/authenticate';
    }

    protected function _getUpdateAccountUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/update';
    }

    protected function _getUploadProductsDataUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/products/update';
    }

    protected function _getGetProductsPageviewsDataUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/products/pageviews';
    }

    protected function _getGetSlotsPageTypesDataUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/pagetypes';
    }

    protected function _getGetPanelsListDataUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/panels/search';
    }

    protected function _getGetProductAttributesListDataUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/productattributes/search';
    }

    protected function _getGetCustomerAttributesListDataUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/customerattributes/search';
    }

    protected function _getUpdateSlotsUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/slots/update';
    }

    protected function _getGetSlotsDataUrl($storeId = null)
    {
        return $this->_getAccountApiUrl($storeId).'/slots';
    }

    protected function _getAccessToken($storeId = null)
    {
        if (!isset($this->_tokenData[$storeId]) ||
            (
                is_array($this->_tokenData[$storeId]) &&
                !isset($this->_tokenData[$storeId]['token'])
            ) || (is_array($this->_tokenData[$storeId]) &&
            isset($this->_tokenData[$storeId]['expires_date']) &&
            (time()>$this->_tokenData[$storeId]['expires_date']))
        ) {
            try {
                $responseBody = curl_exec($ch);
                $tokenData = @json_decode($responseBody, true);
                $this->_initApiCall(
                    $this->_getGetTokenUrl($storeId),
                    [
                        'key'=>$this->_encryptor->decrypt(
                            $this->scopeConfig->getValue(
                                self::XML_PATH_API_KEY,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            )
                        )
                    ],
                    true
                );
                $this->_callApiCall();
                if (isset($tokenData['success']) && $tokenData['success']==true) {
                    $this->_tokenData[$storeId] = $tokenData['token'];
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
        if (isset($this->_tokenData[$storeId]) &&
            is_array($this->_tokenData[$storeId]) &&
            isset($this->_tokenData[$storeId]['token'])
        ) {
            return $this->_tokenData[$storeId]['token'];
        }
    }

    public function uploadProductsData($products, $storeId = null)
    {
        try {
            $this->_initApiCall($this->_getUploadProductsDataUrl($storeId), ['products'=>$products]);
            $this->_callApiCall();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getProductsPageviewsData($hours, $storeId = null)
    {
        try {
            $this->_initApiCall($this->_getGetProductsPageviewsDataUrl($storeId), ['hours'=>$hours]);
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true &&
                isset($response['products']) && is_array($response['products'])
            ) {
                $productsData = $response['products'];
                return $productsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getSlotsPageTypesData($storeId = null)
    {
        try {
            $this->_initApiCall($this->_getGetSlotsPageTypesDataUrl($storeId));
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true &&
                isset($response['results']) && is_array($response['results'])
            ) {
                $slotsPageTypesData = $response['results'];
                return $slotsPageTypesData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getSlotsData($storeId = null)
    {
        try {
            $this->_initApiCall($this->_getGetSlotsDataUrl($storeId));
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true &&
                isset($response['results']) && is_array($response['results'])
            ) {
                $slotsData = $response['results'];
                return $slotsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function updateSlots($slotsData, $storeId = null)
    {
        try {
            $this->_initApiCall($this->_getUpdateSlotsUrl($storeId), ['slots'=>$slotsData]);
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getPanelsListData($storeId = null)
    {
        try {
            $this->_initApiCall($this->_getGetPanelsListDataUrl($storeId));
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true &&
                isset($response['results']) && is_array($response['results'])
            ) {
                $panelsData = $response['results'];
                return $panelsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getProductAttributesListData($storeId = null)
    {
        try {
            $this->_initApiCall($this->_getGetProductAttributesListDataUrl($storeId));
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true &&
                isset($response['results']) && is_array($response['results'])
            ) {
                $panelsData = $response['results'];
                return $panelsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getCustomerAttributesListData($storeId = null)
    {
        try {
            $this->_initApiCall($this->_getGetCustomerAttributesListDataUrl($storeId));
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true &&
                isset($response['results']) && is_array($response['results'])
            ) {
                $panelsData = $response['results'];
                return $panelsData;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function updateAccount($storeId = null)
    {
        try {
            $this->_initApiCall(
                $this->_getUpdateAccountUrl($storeId),
                [
                    'currency'=>$this->storeManager->getStore($storeId)->getBaseCurrencyCode(),
                    'platform'=>'magento',
                    'platform_version'=>$this->productMetadata->getVersion()
                ]
            );
            $response = $this->_callApiCall();
            if (isset($response['success']) && $response['success']==true) {
                return true;
            } elseif (isset($response['error']) && $response['error']==true &&
                isset($response['error_message']) &&
                $response['error_message']=='Base currency can not be changed after build.'
            ) {
                $this->backendSession->addError(
                    __('Once you have posted transactions and accounts using the base currency, you cannot change the base currency.')
                );
                return false;
            } elseif (isset($response['error']) && isset($response['error_message']) &&
                $response['error_message']=='Access denied'
            ) {
                $this->backendSession->addError(__('API not connected. Check Account Id and API key.'));
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function getShowOutOfStockProduct()
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_API_SHOW_OUT_OF_STOCK_PRODUCTS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    protected function _initApiCall($url, $post = null, $isAuthTokenRequest = false, $headers = [])
    {
        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        if (!$isAuthTokenRequest) {
            $headers[] = 'X-Auth-Token: '.$this->_getAccessToken($storeId);
        }
        if ($post !== null) {
            $data_string = json_encode($post);
            curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $data_string);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($data_string);
        }
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
    }

    protected function _callApiCall()
    {
        $responseBody = curl_exec($this->_ch);
        curl_close($this->_ch);
        return @json_decode($responseBody, true);
    }
}
