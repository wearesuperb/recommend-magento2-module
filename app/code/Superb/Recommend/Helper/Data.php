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
    /**
     * Cookie key
     */
    const COOKIE_RECOMMENDTRACKER = 'RECOMMENDTRACKER';

    /**
     * Cookie path
     */
    const COOKIE_PATH = '/';

    const XML_PATH_ENABLED                      = 'superbrecommend/general_settings/enabled';
    const XML_PATH_TRACKING_ACCOUNT_ID          = 'superbrecommend/general_settings/account_id';
    const XML_PATH_TRACKING_URL                 = 'superbrecommend/general_settings/server_url';
    const XML_PATH_TRACKING_URL_SECURE          = 'superbrecommend/general_settings/server_secure_url';
    const XML_PATH_DASHBOARD_ENABLED            = 'superbrecommend/general_settings/dashboard';
    const XML_PATH_TRACKING_PRODUCT_ATTRIBUTES  = 'superbrecommend/general_settings/product_attributes';
    const XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES = 'superbrecommend/general_settings/customer_attributes';
    const XML_PATH_ADVANCED                     = 'superbrecommend/general_settings/advanced';
    const XML_PATH_TRACKING_MEDIA_THUMB_SOURCE  = 'superbrecommend/panels/media_thumb_source';
    const XML_PATH_TRACKING_MEDIA_THUMB_WIDTH   = 'superbrecommend/panels/media_thumb_width';
    const XML_PATH_TRACKING_MEDIA_THUMB_HEIGHT  = 'superbrecommend/panels/media_thumb_height';
    const XML_PATH_DATA_CRON_ENABLED            = 'superbrecommend/data_cron/enabled';
    const XML_PATH_STATUS_CRON_ENABLED          = 'superbrecommend/status_cron/enabled';
    const LIMIT_STEP                            = 1000;

    static protected $_staticData;

    protected $_childProductLoaded;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $subscription;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $newsletterSubscriberFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $catalogImageHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $frameworkHelperDataHelper;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $checkoutCart;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $wishlistHelper;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerCustomerFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Recommend session
     *
     * @var \Superb\Recommend\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_pricingHelper;

    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var \Magento\Checkout\CustomerData\ItemPoolInterface
     */
    protected $itemPool;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_salesOrderCollection;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $_customerAccountManagement;

    /**
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $_customerMetadataService;

    public function __construct(
		\Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Newsletter\Model\SubscriberFactory $newsletterSubscriberFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Image $catalogImageHelper,
        \Magento\Framework\Url\Helper\Data $frameworkHelperDataHelper,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Wishlist\Helper\Data $wishlistHelper,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Superb\Recommend\Model\Session $session,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository,
        \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollection,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Customer\Api\CustomerMetadataInterface $customerMetadataService
    ) {
		$this->_salesOrderCollection = $salesOrderCollection;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->itemRepository = $itemRepository;
        $this->itemPool = $itemPool;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->newsletterSubscriberFactory = $newsletterSubscriberFactory;
        $this->eavConfig = $eavConfig;
        $this->_registry = $registry;
        $this->catalogImageHelper = $catalogImageHelper;
        $this->frameworkHelperDataHelper = $frameworkHelperDataHelper;
        $this->checkoutCart = $checkoutCart;
        $this->checkoutHelper = $checkoutHelper;
        $this->wishlistHelper = $wishlistHelper;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->_session = $session;
        $this->_pricingHelper = $pricingHelper;
        $this->_customerSession = $customerSession;
        $this->_customerRepository = $customerRepository;
        $this->_customerAccountManagement = $customerAccountManagement;
        $this->_customerMetadataService = $customerMetadataService;
        parent::__construct($context);
    }

    public function getIsAdvancedModeEnabled()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getThumbSource()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRACKING_MEDIA_THUMB_SOURCE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getThumbWidth()
    {
        $width = $this->scopeConfig->getValue(self::XML_PATH_TRACKING_MEDIA_THUMB_WIDTH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return empty($width)?null:$width;
    }

    public function getThumbHeight()
    {
        $height = $this->scopeConfig->getValue(self::XML_PATH_TRACKING_MEDIA_THUMB_HEIGHT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return empty($height)?null:$height;
    }

    protected function _generateTrackingData($data)
    {
        return $data;
    }

    public function normalizeName($name)
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    public function isEnabled($storeId=null)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
    }

    public function isDashboardEnabled()
    {
        return $this->scopeConfig->getValue( self::XML_PATH_DASHBOARD_ENABLED , \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function isDataCronEnabled($storeId=null)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_DATA_CRON_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
    }

    public function isStatusCronEnabled($storeId=null)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_STATUS_CRON_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
    }

    public function getAccountId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiUrl()
    {
        if ($this->storeManager->getStore()->isCurrentlySecure())
            return $this->scopeConfig->getValue(self::XML_PATH_TRACKING_URL_SECURE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        else
            return $this->scopeConfig->getValue(self::XML_PATH_TRACKING_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiJsUrl()
    {
        if ($this->storeManager->getStore()->isCurrentlySecure())
            return $this->scopeConfig->getValue(self::XML_PATH_TRACKING_URL_SECURE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'trackerv11.js';
        else
            return $this->scopeConfig->getValue(self::XML_PATH_TRACKING_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'trackerv11.js';
    }

    public function getCurrentStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
	}

    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
	}

    /**
     * Return the Customer given the customer Id stored in the session.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        return $this->_customerRepository->getById($this->_customerSession->getCustomerId());
    }

    /**
     * Create an instance of a subscriber.
     *
     * @return \Magento\Newsletter\Model\Subscriber
     */
    protected function _createSubscriber()
    {
        return $this->newsletterSubscriberFactory->create();
    }

    /**
     * Retrieve the subscription object (i.e. the subscriber).
     *
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function getSubscriptionObject()
    {
        if ($this->subscription === null) {
            $this->subscription =
                $this->_createSubscriber()->loadByCustomerId($this->_customerSession->getCustomerId());
        }

        return $this->subscription;
    }

    public function getCustomerRegistrationConfirmData($customer=null)
    {
        if (is_null($customer))
            $customer = $this->getCustomer();
        $data = array(
            'type'              => 'customer-registration',
            'title'             => $customer->getPrefix(),
            'firstname'         => $customer->getFirstname(),
            'lastname'          => $customer->getLastname(),
            'email'             => $customer->getEmail(),
            'email_subscribed'  => $this->getSubscriptionObject()->isSubscribed() ? 'yes' : 'no'
        );
        $data = array(
            'setEcommerceData',
            $data
        );
        return $this->_generateTrackingData($data);
    }

    public function getPrimaryBillingAddressTelephone()
    {
        $customerId = $this->getCustomer()->getId();

        if ($defaultBilling = $this->_customerAccountManagement->getDefaultBillingAddress($customerId)) {
            return $defaultBilling->getTelephone();
        }
	}

    public function getCustomerUpdateDetailsData($customer=null)
    {
        if (is_null($customer))
            $customer = $this->getCustomer();
        $data = array(
            'type'              => 'customer-update',
            'title'             => $customer->getPrefix(),
            'firstname'         => $customer->getFirstname(),
            'lastname'          => $customer->getLastname(),
            'email'             => $customer->getEmail(),
            'email_subscribed'  => $this->getSubscriptionObject()->isSubscribed() ? 'yes' : 'no',
            'mobile'            => $this->getPrimaryBillingAddressTelephone(),
        );
        $data = array(
            'setEcommerceData',
            $data
        );
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerLoginData($customer=null)
    {
        if (is_null($customer))
            $customer = $this->getCustomer();
        $data = array(
            'type'              => 'login',
            'email'             => $customer->getEmail(),
            'customerId'        => $customer->getId(),
            'title'             => $customer->getPrefix(),
            'firstname'         => $customer->getFirstname(),
            'lastname'          => $customer->getLastname(),
            'email'             => $customer->getEmail(),
            'email_subscribed'  => $this->getSubscriptionObject()->isSubscribed() ? 'yes' : 'no',
            'mobile'            => $this->getPrimaryBillingAddressTelephone(),
        );
        $data = array(
            'setEcommerceData',
            $data
        );
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerCustomData($customer=null)
    {
        if (is_null($customer))
        {
            $customer = $this->getCustomer();
        }

        $data = array();
        $customerData = $customer->__toArray();
        $eavConfig = $this->eavConfig;
        foreach ($this->getCustomerUpdateAttributes() as $row)
        {
            $attribute = $eavConfig->getAttribute('customer', $row['magento_attribute']);
            if ($attribute && $attribute->getId() && isset($customerData[$attribute->getAttributeCode()]))
            {
                $_attributeText = $attribute->getSource()->getOptionText(
                    $customerData[$attribute->getAttributeCode()]
                );
                $data[] = $this->_generateTrackingData(array(
                    'setCustomerCustomVar',
                    $row['recommend_attribute'],
                    empty($_attributeText)?$customerData[$attribute->getAttributeCode()]:$_attributeText
                ));
            }
        }
        return  $data;
    }

    protected function getCategoryPathName($_category)
    {
        if (is_null($_category))
            $_category = $this->_registry->registry('current_category');

        $categoriesPath = array();
        if ($_category) {
            $pathInStore = $_category->getPathInStore();
            $pathIds = array_reverse(explode(',', $pathInStore));

            $categories = $_category->getParentCategories();

            // add category path breadcrumb
            foreach ($pathIds as $categoryId) {
                if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                    $categoriesPath[] = $this->normalizeName($categories[$categoryId]->getName());
                }
            }
        }
        return implode('/',$categoriesPath);
    }

    public function getProductUpdateAttributes()
    {
        $attributes = @unserialize((string)$this->scopeConfig->getValue(self::XML_PATH_TRACKING_PRODUCT_ATTRIBUTES, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        return is_array($attributes)?$attributes:[];
    }

    public function getCustomerUpdateAttributes()
    {
        $attributes = @unserialize((string)$this->scopeConfig->getValue(self::XML_PATH_TRACKING_CUSTOMER_ATTRIBUTES, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        return is_array($attributes)?$attributes:[];
    }

    public function getProductViewData($_product=null,$_currentCategory=null)
    {
        if (is_null($_currentCategory))
            $_currentCategory = $this->_registry->registry('current_category');

        if (is_null($_product))
            $_product = $this->_registry->registry('current_product');

        $categories = array();
        foreach ($_product->getCategoryCollection() as $_category) {
            $categoryPathName = $this->getCategoryPathName($_category);
            if (!empty($categoryPathName)) $categories[] = $this->normalizeName($categoryPathName);
        }

        $additionalAttributes = array();
        $eavConfig = $this->eavConfig;
        foreach ($this->getProductUpdateAttributes() as $row)
        {
            $attribute = $eavConfig->getAttribute('catalog_product', $row['magento_attribute']);
            if ($attribute && $attribute->getId())
            {
                $_attributeText = $_product->getAttributeText($attribute->getAttributeCode());
                $additionalAttributes[$row['recommend_attribute']] = empty($_attributeText)?$_product->getData($attribute->getAttributeCode()):$_attributeText;
                if (is_array($additionalAttributes[$row['recommend_attribute']]))
                {
                    $additionalAttributes[$row['recommend_attribute']] = implode(', ',$additionalAttributes[$row['recommend_attribute']]);
                }
            }
        }

		$_price = $_product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
		$_finalPrice = $_product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();

        $attributes = [
			'type' => $this->getThumbSource(),
			'width' => $this->getThumbWidth(),
			'height' => $this->getThumbHeight()
        ];
        $imageUrl = (string)$this->catalogImageHelper->init($_product, null, $attributes)->getUrl();
        $secureImageUrl = str_replace($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA,false),$this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA,true),$imageUrl);
        $data = array(
            'setEcommerceData',
            array(
                'type'                  => 'product-view',
                'name'                  => $this->normalizeName($_product->getName()),
                'sku'                   => $_product->getSku(),
                'image'                 => $imageUrl,
                'secure_image'          => $secureImageUrl,
                'url'                   => $_product->getUrlModel()->getUrl($_product, array('_ignore_category'=>true)),
                'categories'            => $categories,
                'price'                 => $this->_pricingHelper->currency($_finalPrice, false, false),
                'original_price'        => $this->_pricingHelper->currency($_price,false,false),
                'additional_attributes' => $additionalAttributes
            )
        );
        if (is_object($_currentCategory))
            $data[1]['current_category'] = $this->normalizeName($this->getCategoryPathName($_currentCategory));
        return  $this->_generateTrackingData($data);
    }

    public function getCategoryViewData($_category=null)
    {
        if (is_null($_category))
            $_category = $this->_registry->registry('current_category');

        $data = array(
            'setEcommerceData',
            array(
                'type'          => 'category-view',
                'name'          => $this->normalizeName($this->getCategoryPathName($_category)),
                'url'           => $this->_urlBuilder->getCurrentUrl()
            )
        );
        return  $this->_generateTrackingData($data);
    }

    public function getCartStatusData($_cart=null)
    {
        if (is_null($_cart))
            $_cart = $this->checkoutCart;
        if (!(int)$_cart->getQuote()->getId())
            return [];

        $_items = $this->itemRepository->getList($_cart->getQuote()->getId());
        $data = array(
            'type'          => 'cart-update',
            'grand-total'   => sprintf('%01.2f',$_cart->getQuote()->getGrandTotal()),
            'total-qty'     => (int)$_cart->getSummaryQty(),
            'products'      => array()
        );
        foreach($_items as $_item)
        {
            $allData = $this->itemPool->getItemData($_item);
            $itemData = array();
            $itemData['product-name']  = $this->normalizeName($allData['product_name']);
            $itemData['product-sku']  = $_item->getProduct()->getData('sku');
            $itemData['product-image'] = $allData['product_image']['src'];
            $itemData['product-url']  = $allData['product_url'];
            $itemData['product-qty']  = $allData['qty'];
            $itemData['product-price']  = sprintf('%01.2f',$this->checkoutHelper->getPriceInclTax($_item));
            $itemData['product-total-val']  = sprintf('%01.2f',$this->checkoutHelper->getSubtotalInclTax($_item));
            $data['products'][] = $itemData;
        }
        $data = array(
            'setEcommerceData',
            $data
        );
        return  $this->_generateTrackingData($data);
    }

    public function getWishlistUpdatedData()
    {
        $_wishlist = $this->wishlistHelper->getWishlist();
        $_items = $_wishlist->getItemCollection();
        $data = array(
            'type'      => 'wishlist-update',
            'products'  => array()
        );
        foreach($_items as $_item)
        {
            $itemData = array();
            $itemData['product-name']  = $this->normalizeName($_item->getProduct()->getName());
            $itemData['product-sku']  = $_item->getProduct()->getData('sku');
            $data['products'][] = $itemData;
        }
        $data = array(
            'setEcommerceData',
            $data
        );
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerUnsubscribeData($email)
    {
        $customer = $this->customerCustomerFactory->create()->setWebsiteId($this->storeManager->getWebsite()->getId())->loadByEmail($email);
        $data = array(
            'type'      => 'unsubscribe',
            'email'     => $email,
            'customerId'=> $customer && $customer->getId()?$customer->getId():''
        );
        $data = array(
            'setEcommerceData',
            $data
        );
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerSubscribeData($email)
    {
        $customer = $this->customerCustomerFactory->create()->setWebsiteId($this->storeManager->getWebsite()->getId())->loadByEmail($email);
        $data = array(
            'type'          => 'subscribe',
            'email'         => $email
        );
        $data = array(
            'setEcommerceData',
            $data
        );
        return  $this->_generateTrackingData($data);
    }

    public function getOrdersData($orderIds)
    {
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        $collection = $this->_salesOrderCollection->create();
        $collection->addFieldToFilter('entity_id', ['in' => $orderIds]);
        
        $ordersData = [];
        foreach ($collection as $order) {
			$_items = $order->getAllVisibleItems();
			$data = array(
				'type'              => 'sale',
				'sale-qty'          => '',
				'email'             => $order->getCustomerEmail(),
				'firstname'         => $order->getCustomerFirstname(),
				'lastname'          => $order->getCustomerLastname(),
				'sale-grand-total'  => $order->getBaseGrandTotal(),
				'sale-tax'          => $order->getBaseTaxAmount(),
				'sale-delivery'     => $order->getBaseShippingAmount(),
				'sale-ref'          => $order->getIncrementId(),
				'sale-currency'     => $order->getBaseCurrencyCode(),
				'products'  		=> []
			);
			$_qtyOrdered = 0;
			foreach($_items as $_item)
			{
				$_qtyOrdered += $_item->getQtyOrdered();
				$itemData = array();
				$itemData['sale-product-name'] = $this->normalizeName($_item->getName());
				$itemData['sale-product-sku'] = $_item->getProduct()->getSku();//$_item->getProductOptionByCode('recommend-product-view-sku');
				$itemData['sale-product-qty']  = $_item->getQtyOrdered();
				$itemData['sale-product-val']  = sprintf('%.2f', $_item->getBasePriceInclTax());
				$data['products'][] = $itemData;
			}
			$data['sale-qty'] = $_qtyOrdered;
			$data = array(
				'setEcommerceData',
				$data
			);
			;
			$ordersData[] = $this->_generateTrackingData($data);
		}
        return $ordersData;
    }

    public function processCheckoutPage()
    {
        $data = array(
            'type'              => 'checkout-view',
        );
        $data = array(
            'setEcommerceData',
            $data
        );
        return  $this->_generateTrackingData($data);
    }

    public function setTrackingData($record,$static=false)
    {
        if ($static)
            $data = $this->getStaticTrackingData();
        else
            $data = $this->_session->getTrackingData();
        if (is_array($record))
            $data[] = $record;
        else
            $data = array($record);
        if ($static)
            $this->setStaticTrackingData($data);
        else
        {
            $this->_session->setTrackingData($data);
            $this->setDataExistsCookie('1');
        }
    }

    public function getTrackingData($clear=true,$static=false)
    {
        if ($static)
            $data = $this->getStaticTrackingData();
        else
            $data = $this->_session->getTrackingData();
        if ($clear)
        {
            if ($static)
                $this->setStaticTrackingData(array());
            else
                $this->_session->setTrackingData(array());
        }
        return $data;
    }

    public function getStaticTrackingData()
    {
        if (!is_array(self::$_staticData)){
            return array();
        }
        return self::$_staticData;
    }

    public function setStaticTrackingData($data)
    {
        self::$_staticData = $data;
        return $this;
    }

    /**
     *
     * @param string $cookieValue
     * @return void
     */
    private function setDataExistsCookie($cookieValue)
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath(self::COOKIE_PATH);
        $this->cookieManager->setPublicCookie(self::COOKIE_RECOMMENDTRACKER, $cookieValue, $metadata);
    }
}
