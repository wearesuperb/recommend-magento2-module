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

class Tracker extends Tracker\Data
{
    /**
     * Cookie key
     */
    const COOKIE_RECOMMENDTRACKER = 'RECOMMENDTRACKER';

    /**
     * Cookie path
     */
    const COOKIE_PATH = '/';

    static protected $_staticData;

    protected $_childProductLoaded;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $subscription;

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

    /**
     * @var \Superb\Recommend\Helper\Rebuild
     */
    protected $rebuildHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
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
        \Superb\Recommend\Helper\Rebuild $rebuildHelper,
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
        $this->scopeConfig = $context->getScopeConfig();
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
        $this->rebuildHelper = $rebuildHelper;
        parent::__construct(
            $context,
            $this->storeManager
        );
    }

    protected function _generateTrackingData($data)
    {
        return $data;
    }

    public function normalizeName($name)
    {
        return trim(preg_replace('/\s+/', ' ', $name));
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

    public function getCustomerRegistrationConfirmData($customer = null)
    {
        if ($customer === null) {
            $customer = $this->getCustomer();
        }
        $data = [
            'type'              => 'customer-registration',
            'title'             => $customer->getPrefix(),
            'firstname'         => $customer->getFirstname(),
            'lastname'          => $customer->getLastname(),
            'email'             => $customer->getEmail(),
            'email_subscribed'  => $this->getSubscriptionObject()->isSubscribed() ? 'yes' : 'no'
        ];
        $data = [
            'setEcommerceData',
            $data
        ];
        return $this->_generateTrackingData($data);
    }

    public function getPrimaryBillingAddressTelephone()
    {
        $customerId = $this->getCustomer()->getId();

        if ($defaultBilling = $this->_customerAccountManagement->getDefaultBillingAddress($customerId)) {
            return $defaultBilling->getTelephone();
        }
    }

    public function getCustomerUpdateDetailsData($customer = null)
    {
        if ($customer === null) {
            $customer = $this->getCustomer();
        }
        $data = [
            'type'              => 'customer-update',
            'title'             => $customer->getPrefix(),
            'firstname'         => $customer->getFirstname(),
            'lastname'          => $customer->getLastname(),
            'email'             => $customer->getEmail(),
            'email_subscribed'  => $this->getSubscriptionObject()->isSubscribed() ? 'yes' : 'no',
            'mobile'            => $this->getPrimaryBillingAddressTelephone(),
        ];
        $data = [
            'setEcommerceData',
            $data
        ];
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerLoginData($customer = null)
    {
        if ($customer === null) {
            $customer = $this->getCustomer();
        }
        $data = [
            'type'              => 'login',
            'email'             => $customer->getEmail(),
            'customerId'        => $customer->getId(),
            'title'             => $customer->getPrefix(),
            'firstname'         => $customer->getFirstname(),
            'lastname'          => $customer->getLastname(),
            'email'             => $customer->getEmail(),
            'email_subscribed'  => $this->getSubscriptionObject()->isSubscribed() ? 'yes' : 'no',
            'mobile'            => $this->getPrimaryBillingAddressTelephone(),
        ];
        $data = [
            'setEcommerceData',
            $data
        ];
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerCustomData($customer = null)
    {
        if ($customer === null) {
            $customer = $this->getCustomer();
        }

        $data = [];
        $customerData = $customer->__toArray();
        $eavConfig = $this->eavConfig;
        foreach ($this->getCustomerUpdateAttributes() as $row) {
            $attribute = $eavConfig->getAttribute('customer', $row['magento_attribute']);
            if ($attribute && $attribute->getId() &&
                isset($customerData[$attribute->getAttributeCode()]) &&
                isset($row['recommend_attribute'])
            ) {
                $_attributeText = $attribute->getSource()->getOptionText(
                    $customerData[$attribute->getAttributeCode()]
                );
                $data[] = $this->_generateTrackingData([
                    'setCustomerCustomVar',
                    $row['recommend_attribute'],
                    empty($_attributeText)?$customerData[$attribute->getAttributeCode()]:$_attributeText
                ]);
            }
        }
        return  $data;
    }

    protected function getCategoryPathName($_category)
    {
        if ($_category === null) {
            $_category = $this->_registry->registry('current_category');
        }

        $categoriesPath = [];
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
        return implode('/', $categoriesPath);
    }

    public function getProductViewData($_product = null, $_currentCategory = null)
    {
        if ($_currentCategory === null) {
            $_currentCategory = $this->_registry->registry('current_category');
        }

        if ($_product === null) {
            $_product = $this->_registry->registry('current_product');
        }

        $categories = [];
        foreach ($_product->getCategoryCollection() as $_category) {
            $categoryPathName = $this->getCategoryPathName($_category);
            if (!empty($categoryPathName)) {
                $categories[] = $this->normalizeName($categoryPathName);
            }
        }

        $additionalAttributes = [];
        $eavConfig = $this->eavConfig;
        foreach ($this->getProductUpdateAttributes() as $row) {
            $attribute = $eavConfig->getAttribute('catalog_product', $row['magento_attribute']);
            if ($attribute && $attribute->getId() && isset($row['recommend_attribute'])) {
                $_attributeText = $_product->getAttributeText($attribute->getAttributeCode());

                $additionalAttributes[$row['recommend_attribute']] =
                    empty($_attributeText) ? $_product->getData($attribute->getAttributeCode()):$_attributeText;

                if (is_array($additionalAttributes[$row['recommend_attribute']])) {
                    $additionalAttributes[$row['recommend_attribute']] = implode(
                        ', ',
                        $additionalAttributes[$row['recommend_attribute']]
                    );
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
        $secureImageUrl = str_replace(
            $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false),
            $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true),
            $imageUrl
        );
        $data = [
            'setEcommerceData',
            [
                'type'                  => 'product-view',
                'name'                  => $this->normalizeName($_product->getName()),
                'sku'                   => $_product->getSku(),
                'image'                 => $imageUrl,
                'secure_image'          => $secureImageUrl,
                'url'                   => $_product->getUrlModel()->getUrl($_product, ['_ignore_category'=>true]),
                'categories'            => $categories,
                'price'                 => $this->_pricingHelper->currency($_finalPrice, false, false),
                'original_price'        => $this->_pricingHelper->currency($_price, false, false),
                'additional_attributes' => $additionalAttributes
            ]
        ];
        if (is_object($_currentCategory)) {
            $data[1]['current_category'] = $this->normalizeName($this->getCategoryPathName($_currentCategory));
        }
        return  $this->_generateTrackingData($data);
    }

    public function getCategoryViewData($_category = null)
    {
        if ($_category === null) {
            $_category = $this->_registry->registry('current_category');
        }

        $data = [
            'setEcommerceData',
            [
                'type'          => 'category-view',
                'name'          => $this->normalizeName($this->getCategoryPathName($_category)),
                'url'           => $this->_urlBuilder->getCurrentUrl()
            ]
        ];
        return  $this->_generateTrackingData($data);
    }

    public function getCartStatusData($_cart = null)
    {
        if ($_cart === null) {
            $_cart = $this->checkoutCart;
        }
        if (!(int)$_cart->getQuote()->getId()) {
            return [];
        }

        $_items = $this->itemRepository->getList($_cart->getQuote()->getId());
        $data = [
            'type'          => 'cart-update',
            'grand-total'   => sprintf('%01.2f', $_cart->getQuote()->getGrandTotal()),
            'total-qty'     => (int)$_cart->getSummaryQty(),
            'products'      => [],
            'rebuild'       => array('url'=>$this->storeManager->getStore()->getUrl('superbrecommend/cart/rebuild'),'data'=>array())
        ];
        foreach ($_items as $_item) {
            $allData = $this->itemPool->getItemData($_item);
            $itemData = [];
            $itemData['product-name']  = $this->normalizeName($allData['product_name']);
            $itemData['product-sku']  = $_item->getProduct()->getData('sku');
            $itemData['product-image'] = $allData['product_image']['src'];
            $itemData['product-url']  = $allData['product_url'];
            $itemData['product-qty']  = $allData['qty'];
            $itemData['product-price']  = sprintf('%01.2f', $this->checkoutHelper->getPriceInclTax($_item));
            $itemData['product-total-val']  = sprintf('%01.2f', $this->checkoutHelper->getSubtotalInclTax($_item));
            $data['products'][] = $itemData;
            $data['rebuild']['data'][] = $_item->getBuyRequest();
        }
        $data['rebuild']['data'] = $this->rebuildHelper->base64UrlEncode(serialize($data['rebuild']['data']));
        $data = [
            'setEcommerceData',
            $data
        ];
        return  $this->_generateTrackingData($data);
    }

    public function getWishlistUpdatedData()
    {
        $_wishlist = $this->wishlistHelper->getWishlist();
        $_items = $_wishlist->getItemCollection();
        $data = [
            'type'      => 'wishlist-update',
            'products'  => []
        ];
        foreach ($_items as $_item) {
            $itemData = [];
            $itemData['product-sku']  = $_item->getProduct()->getData('sku');
            $data['products'][] = $itemData;
        }
        $data = [
            'setEcommerceData',
            $data
        ];
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerUnsubscribeData($email)
    {
        $customer = $this->customerCustomerFactory->create()
            ->setWebsiteId($this->storeManager->getWebsite()->getId())
            ->loadByEmail($email);
        $data = [
            'type'      => 'unsubscribe',
            'email'     => $email,
            'customerId'=> $customer && $customer->getId()?$customer->getId():''
        ];
        $data = [
            'setEcommerceData',
            $data
        ];
        return  $this->_generateTrackingData($data);
    }

    public function getCustomerSubscribeData($email)
    {
        $customer = $this->customerCustomerFactory->create()
            ->setWebsiteId($this->storeManager->getWebsite()->getId())
            ->loadByEmail($email);
        $data = [
            'type'          => 'subscribe',
            'email'         => $email
        ];
        $data = [
            'setEcommerceData',
            $data
        ];
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
            $data = [
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
                'products'          => []
            ];
            $_qtyOrdered = 0;
            foreach ($_items as $_item) {
                $_qtyOrdered += $_item->getQtyOrdered();
                $itemData = [];
                $itemData['sale-product-name'] = $this->normalizeName($_item->getName());
                $itemData['sale-product-sku'] = $_item->getProduct()->getSku();
                $itemData['sale-product-qty']  = $_item->getQtyOrdered();
                $itemData['sale-product-val']  = sprintf('%.2f', $_item->getBasePriceInclTax());
                $data['products'][] = $itemData;
            }
            $data['sale-qty'] = $_qtyOrdered;
            $data = [
                'setEcommerceData',
                $data
            ];
            $ordersData[] = $this->_generateTrackingData($data);
        }
        return $ordersData;
    }

    public function processCheckoutPage()
    {
        $data = [
            'type'              => 'checkout-view',
        ];
        $data = [
            'setEcommerceData',
            $data
        ];
        return  $this->_generateTrackingData($data);
    }

    public function setTrackingData($record, $static = false)
    {
        if ($static) {
            $data = $this->getStaticTrackingData();
        } else {
            $data = $this->_session->getTrackingData();
        }
        if (!is_array($data)) {
            $data = [];
        }
        $size = strlen((string)PHP_INT_MAX);
        list($usec, $sec) = explode(" ", microtime());
        $index = sprintf('t%0'.$size.'s%0'.($size+1).'s%05s', $sec, $usec, (count($data)+1));
        $data[$index] = $record;
        if ($static) {
            $this->setStaticTrackingData($data);
        } else {
            $this->_session->setTrackingData($data);
            $this->setDataCookieFlag('read-data');
        }
    }

    public function getTrackingData($clear = true, $static = false)
    {
        if ($static) {
            $data = $this->getStaticTrackingData();
        } else {
            $data = $this->_session->getTrackingData();
        }
        if ($clear) {
            if ($static) {
                $this->setStaticTrackingData([]);
            } else {
                $this->_session->setTrackingData([]);
                $this->setDataCookieFlag('data-empty');
            }
        }
        if ($this->_customerSession->isLoggedIn()) {
            $data = is_array($data)?$data:[];
            array_unshift($data, ["setCustomerId",$this->_customerSession->getCustomerId()]);
            $this->setDataCookieFlag('read-data');
        }
        return $data;
    }

    public function getStaticTrackingData()
    {
        if (!is_array(self::$_staticData)) {
            return [];
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
    private function setDataCookieFlag($cookieValue)
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath(self::COOKIE_PATH);
        $this->cookieManager->setPublicCookie(self::COOKIE_RECOMMENDTRACKER, $cookieValue, $metadata);
    }
}
