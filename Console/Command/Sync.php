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
 * @copyright  Copyright (c) 2021 Superb Media Limited
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Superb\Recommend\Console\Command;

use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Sync
 */
class Sync extends Command
{
    const DATA = 'data';
    const WEBSITE = 'website';
    const XML_PATH_TRACKING_PRODUCT_ATTRIBUTES  = 'superbrecommend/general_settings/product_attributes';

    const OPTION_CATALOG = 'catalog';
    const OPTION_CUSTOMERS = 'customers';
    const OPTION_SUBSCRIBERS = 'subscribers';
    const OPTION_ORDERS = 'orders';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_helperApi;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollection;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $_catalogProductTypeConfigurable;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeWebsiteRelation;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $_orderCollection;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    protected $subscriberCollection;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productCollectionFactory,
        \Superb\Recommend\Helper\Api $helperApi,
        \Superb\Recommend\Helper\Data $helper,
        SerializerInterface $serializer,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Api\StoreWebsiteRelationInterface $storeWebsiteRelation,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        $this->_categoryCollection = $categoryCollection;
        $this->_orderCollection = $orderCollection;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_subscriberCollection = $subscriberCollection;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_productCollectionFactory = $_productCollectionFactory;
        $this->_helperApi = $helperApi;
        $this->_helper = $helper;
        $this->serializer = $serializer;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->_eventManager = $eventManager;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('recommend:sync');
        $this->setDescription('Sync data with Recommend');
        $this->addOption(
            self::OPTION_CATALOG,
            '-c',
            InputOption::VALUE_NONE,
            'Sync catalog',
        );
        $this->addOption(
            self::OPTION_CUSTOMERS,
            null,
            InputOption::VALUE_NONE,
            'Sync customers',
        );
        $this->addOption(
            self::OPTION_ORDERS,
            '-o',
            InputOption::VALUE_NONE,
            'Sync orders',
        );
        $this->addOption(
            self::OPTION_SUBSCRIBERS,
            '-s',
            InputOption::VALUE_NONE,
            'Sync subscribers',
        );

        parent::configure();
    }

    protected function _getCategories($websiteCode)
    {
        $rootCatId = $this->_helperApi->getWebsite($websiteCode)->getDefaultStore()->getRootCategoryId();
        $categories = $this->_categoryCollection->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('path', array('like' => "1/{$rootCatId}/%"));

        return $categories;
    }

    /**
     * Return sites that have enabled module
     * @return array
     */
    protected function getWebsitesList()
    {
        $websites = [];
        foreach ($this->_storeManager->getWebsites() as $website){
            if ($this->_helper->isEnabledWebSiteScope($website->getId())){
                $websites[] = $website;
            }
        }
        return $websites;
    }

    /**
     * Returns names options
     *
     * @param $optionsArray
     */
    protected function getNamesOptions($optionsArray){
        $names = [];
        foreach ($optionsArray as $name => $status){
            if ($status === true){
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * Execute the sync
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);

        $websites = $this->getWebsitesList();
        $namesOptions = $this->getNamesOptions($input->getOptions());

        //code for command with options
        if($namesOptions){
            foreach ($websites as $_website){
                $output->writeln('<info>Start upload for store `' . $_website->getCode() . '`</info>');
                $stores = $this->storeWebsiteRelation->getStoreByWebsiteId($_website->getId());

                foreach ($namesOptions as $name){
                    switch ($name){
                        case 'catalog':
                            $uploadId = $this->_helperApi->initUpload($_website->getCode());var_dump($_website->getCode());
                            $output->writeln('Generated upload #' . $uploadId);
                            $output->writeln('<info>Start upload categories</info>');
                            $categories[$_website->getCode()] = $this->getCategoriesByWebsite($_website->getCode());
                            if (isset($categories[$_website->getCode()])) {
                                $this->_helperApi->syncCategories($categories[$_website->getCode()], $_website->getCode(), $uploadId);
                            }

                            $output->writeln('<info>Start upload products</info>');
                            $productAttributes = $this->_helperApi->getAttributes($_website->getCode());
                            foreach ($stores as $_storeId) {
                                $store = $this->_helperApi->getStore($_storeId);
                                $products[$_website->getCode()][$store->getCode()] = $this->getProductsByStore($store, $productAttributes);
                            }
                            $productBatch = $this->generateProducts($products);

                            if (isset($productBatch[$_website->getCode()]) && isset($productBatch[$_website->getCode()]['products'])) {
                                $this->_helperApi->syncProducts($productBatch[$_website->getCode()]['products'], $_website->getCode(), $uploadId);
                            }
                            $this->_helperApi->commitBatch($_website->getCode(), $uploadId);
                            break;

                        case 'orders':
                            $output->writeln('<info>Start upload orders</info>');
                            foreach ($stores as $_storeId) {
                                $store = $this->_helperApi->getStore($_storeId);
                                $orders[$_website->getCode()][$store->getCode()] = $this->getOrdersByStore($store);
                            }
                            $ordersBatch = $this->generateOrders($orders);

                            if (isset($ordersBatch[$_website->getCode()]) && isset($ordersBatch[$_website->getCode()]['orders'])) {
                                $this->_helperApi->syncOrders($ordersBatch[$_website->getCode()]['orders']);
                            }
                            break;

                        case 'customers':
                            $output->writeln('<info>Start upload customers</info>');
                            foreach ($stores as $_storeId) {
                                $store = $this->_helperApi->getStore($_storeId);
                                $customers[$_website->getCode()][$store->getCode()] = $this->getCustomerByStore($store);
                            }
                            $customersBatch = $this->generateCustomers($customers);

                            if (isset($customersBatch[$_website->getCode()]) && isset($customersBatch[$_website->getCode()]['customers'])) {
                                $this->_helperApi->sendCustomer($customersBatch[$_website->getCode()]['customers'], $_website->getCode());
                            }
                            break;

                        case 'subscribers':
                            $output->writeln('<info>Start upload subscribers</info>');
                            $subscribers[$_website->getCode()] = $this->generateSubscribers($_website->getId());
                            if (isset($subscribers[$_website->getCode()]) && isset($subscribers[$_website->getCode()]['customer'])) {
                                $this->_helperApi->sendCustomer($subscribers[$_website->getCode()]['customer'], $_website->getCode());
                            }
                            if (isset($subscribers[$_website->getCode()]) && isset($subscribers[$_website->getCode()]['chennel'])) {
                                $this->_helperApi->sendChennelData($subscribers[$_website->getCode()]['chennel'], $_website->getCode());
                            }
                            break;
                    }
                }

            }
            return 1;
        }

        //code for command without options
        foreach($websites as $_website){
            $stores = $this->storeWebsiteRelation->getStoreByWebsiteId($_website->getId());
            $productAttributes = $this->_helperApi->getAttributes($_website->getCode());
            //Categories array
            $categories[$_website->getCode()]=$this->getCategoriesByWebsite($_website->getCode());

            $subscribers[$_website->getCode()] = $this->generateSubscribers($_website->getId());

            foreach($stores as $_storeId){
                $store = $this->_helperApi->getStore($_storeId);
                $this->_helperApi->getEnviroment($_website->getCode(),$store->getCode());

                //Customer array
                $customers[$_website->getCode()][$store->getCode()] = $this->getCustomerByStore($store);

                //Orders array
                $orders[$_website->getCode()][$store->getCode()] = $this->getOrdersByStore($store);

                //Products array
                $products[$_website->getCode()][$store->getCode()]=$this->getProductsByStore($store,$productAttributes);
            }
        }

        $output->writeln('<info>Data synchronization start</info>');
        $categoriesBatch = $categories;
        $output->writeln('<info>Categories list generated</info>');
        $productBatch = $this->generateProducts($products);
        $output->writeln('<info>Products list generated</info>');
        $ordersBatch = $this->generateOrders($orders);
        $output->writeln('<info>Orders list generated</info>');
        $subscribersBatch = $subscribers;
        $output->writeln('<info>Subscribers list generated</info>');
        $customersBatch = $this->generateCustomers($customers);

        foreach($websites as $_website){
            $output->writeln('<info>Start upload for store `'.$_website->getCode().'`</info>');
            $uploadId = $this->_helperApi->initUpload($_website->getCode());
            //$uploadId='123';
            $output->writeln('Generated upload #'.$uploadId);

            if (isset($categoriesBatch[$_website->getCode()])) {
                $output->writeln('Start upload categories');
                $this->_helperApi->syncCategories($categoriesBatch[$_website->getCode()],$_website->getCode(),$uploadId);
            }

            if (isset($productBatch[$_website->getCode()])) {
                if (isset($productBatch[$_website->getCode()]['products'])) {
                    $output->writeln('Start upload products');
                    $this->_helperApi->syncProducts($productBatch[$_website->getCode()]['products'],$_website->getCode(),$uploadId);
                }
            }

            if (isset($ordersBatch[$_website->getCode()])) {
                if (isset($ordersBatch[$_website->getCode()]['orders'])) {
                    $output->writeln('Start upload orders');
                    $this->_helperApi->syncOrders($ordersBatch[$_website->getCode()]['orders']);
                }
            }

            if (isset($customersBatch[$_website->getCode()])) {
                if (isset($customersBatch[$_website->getCode()]['customers'])) {
                    $output->writeln('Start upload customer');
                    if (isset($subscribers[$_website->getCode()]['customer'])){
                        $customersBatch[$_website->getCode()]['customers'] = array_merge($subscribers[$_website->getCode()]['customer'], $customersBatch[$_website->getCode()]['customers']);
                    }
                    $this->_helperApi->sendCustomer($customersBatch[$_website->getCode()]['customers'], $_website->getCode());
                }
            }

            if (isset($subscribersBatch[$_website->getCode()])) {
                if (isset($subscribersBatch[$_website->getCode()]['chennel'])) {
                    $output->writeln('Start upload subscribers');
                    $this->_helperApi->sendChennelData($subscribersBatch[$_website->getCode()]['chennel'],$_website->getCode());
                }
            }

            $this->_helperApi->commitBatch($_website->getCode(),$uploadId);
        }
    }

    protected function getProductsByStore($store,$productAttributes)
    {
        $productsCollection = $this->getProducts($store->getId());
        $objectManager = ObjectManager::getInstance();
        $stockItem = $objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
        $taxHelper = $objectManager->get('\Magento\Catalog\Helper\Data');

        $data = [];
        foreach ($productsCollection as $_product) {
            if ($_product->getVisibility() != '1') {
                $productStock = $stockItem->getStockItem($_product->getEntityId());
                $attributes = [];
                foreach ($productAttributes as $productAttribute) {
                    $attributes[] = [
                        'code' => $productAttribute['magento_attribute'],
                        'value' => $_product->getData($productAttribute['magento_attribute'])
                    ];
                }
                $original_price = [];
                $price = [];

                $original_price[] = [
                    'code' => 'default',
                    'prices' => [0 => [
                        'currency' => $store->getCurrentCurrency()->getCode(),
                        'value' => floatval(number_format($taxHelper->getTaxPrice($_product, $_product->getPrice(), false), 2))
                    ]]
                ];


                $price[] = [
                    'code' => 'default',
                    'prices' => [0 => [
                        'currency' => $store->getCurrentCurrency()->getCode(),
                        'value' => floatval(number_format($taxHelper->getTaxPrice($_product, $_product->getFinalPrice(), false), 2))
                    ]]
                ];
                if ($_product->getTypeId() == 'configurable') {
                    $original_price[0] = [
                        'code' => 'default',
                        'prices' => [0 => [
                            'currency' => $store->getCurrentCurrency()->getCode(),
                            'value' => floatval(number_format($_product->getPriceInfo()->getPrice('regular_price')->getMinRegularAmount()->getValue(), 2))
                        ]]
                    ];
                }

                $this->_eventManager->dispatch('recommend_product_attributes', ['product' => $_product,'attributes'=>$attributes]);

                $array = [
                    'id' => $_product->getId(),
                    'status' => $productStock->getIsInStock(),
                    'sku' => $_product->getSku(),
                    'name' => $_product->getName(),
                    'lists' => $_product->getCategoryIds(),
                    'url' => $_product->getProductUrl(),
                    'image' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage(),
                    'description' => $_product->getShortDescription() ? $_product->getShortDescription() : '',
                    'stock_quantity' => (int)$productStock->getQty(),
                    'attributes' => $attributes,
                    'price' => $price,
                    'original_price' => $original_price
                ];

                $data[$_product->getId()] = $array;
            }
        }
        return $data;
    }

    protected function generateProducts($data)
    {
        $batch = [];
        foreach ($data as $website => $stores) {
            $defaultStore = $this->_helperApi->getWebsite($website)->getDefaultStore();
            foreach ($stores[$defaultStore->getCode()] as $product) {
                $env = [];
                foreach ($stores as $code => $enviroments) {
                    $enviroment = $enviroments[$product['id']];
                    $env[] = [
                        'code' => $code,
                        'data' => [
                            'status' => $enviroment['status'],
                            'name' => $enviroment['name'],
                            'lists' => $enviroment['lists'],
                            'url' => $enviroment['url'],
                            'image' => $enviroment['image'],
                            'description' => $enviroment['description'],
                            'stock_quantity' => $enviroment['stock_quantity'],
                            'attributes' => $enviroment['attributes'],
                            'price' => $enviroment['price'],
                            'original_price' => $enviroment['original_price']
                        ]
                    ];
                }

                $batch[$website]['products'][] = [
                    'action' => 'upsert_update',
                    'data' => [
                        'id' => $product['id'],
                        'status' => $product['status'],
                        'sku' => $product['sku'],
                        'name' => $product['name'],
                        'lists' => $product['lists'],
                        'url' => $product['url'],
                        'image' => $product['image'],
                        'description' => $product['description'],
                        'stock_quantity' => $product['stock_quantity'],
                        'attributes' => $product['attributes'],
                        'environment' => $env,
                        'price' => $product['price'],
                        'original_price' => $product['original_price']
                    ]
                ];
            }
        }

        return $batch;
    }

    /**
     * Returns orders collection by store
     * @param $store
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function getOrdersCollection($store){

        $now = new \DateTime();
        $startDate = date("Y-m-d h:i:s",strtotime('2019-12-31')); // start date
        $endDate = strtotime("Y-m-d h:i:s", strtotime('2021-1-20')); // end date
        $orders = $this->_orderCollection->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('created_at', array('from'=>$startDate, 'to'=>$now))
            ->addAttributeToFilter('store_id',$store->getId());

        return $orders;
    }

    /**
     * Returns data for orders by store ID
     * @param $store
     * @return array
     */
    protected function getOrdersByStore($store){

        $data = [];
        $orders = $this->getOrdersCollection($store);

        foreach($orders as $order){
            $items = $order->getAllVisibleItems();
            $_items = [];
            foreach ($items as $item) {
                $productOptions = $item->getProductOptions();

                $_items[] = [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'variation_sku' => isset($productOptions['simple_sku'])?$productOptions['simple_sku']:'',
                    'quantity' => (int)$item->getQtyOrdered(),
                    'cost' => (float)$item->getPrice(),
                    'base_cost' => (float)$item->getBasePrice(),
                    'discount' => (float)$item->getDiscountAmount(),
                    'base_discount' => (float)$item->getBaseDiscountAmount()
                ];
            }

            $data[$order->getIncrementId()] = [
                'order_id' => $order->getIncrementId(),
                'status' => $order->getStatus(),
                'customer_id' => $order->getCustomerId()?$order->getCustomerId():'',
                'email' => $order->getCustomerEmail(),
                'full_name' => $order->getCustomerFirstname().' '.$order->getCustomerLastname(),
                'currency' => $order->getOrderCurrencyCode(),
                'base_currency' => $order->getGlobalCurrencyCode(),
                'total' => (float)$order->getGrandTotal(),
                'base_total' => (float)$order->getBaseGrandTotal(),
                'discount' => (float)$order->getDiscountAmount(),
                'base_discount' => (float)$order->getBaseDiscountAmount(),
                'tax_cost' => (float)$order->getTaxAmount(),
                'base_tax_cost' => (float)$order->getBaseTaxAmount(),
                'delivery_cost' => (float)$order->getShippingAmount(),
                'base_delivery_cost' => (float)$order->getBaseShippingAmount(),
                'create_date' => strtotime($order->getCreatedAt()),
                'update_date' => strtotime($order->getUpdatedAt()),
                'items' => $_items
            ];
        }
        return $data;
    }

    /**
     * Returns all orders for website
     * @param $data
     * @return array
     */
    protected function generateOrders($data){

        $batch = [];
        foreach ($data as $website=>$stores){
            $defaultStore = $this->_helperApi->getWebsite($website)->getDefaultStore();
            foreach ($stores as $store=>$orders){
                foreach ($orders as $data){
                    $batch[$website]['orders'][] = [
                        'action' => 'upsert_update',
                        'data'=> [
                            'order_id' => $data['order_id'],
                            'status' => $data['status'],
                            'customer_id' => $data['customer_id'],
                            'email' => $data['email'],
                            'full_name' => $data['full_name'],
                            'currency' => $data['currency'],
                            'base_currency' => $data['base_currency'],
                            'total' => $data['total'],
                            'base_total' => $data['base_total'],
                            'discount' => $data['discount'],
                            'base_discount' => $data['base_discount'],
                            'tax_cost' => $data['tax_cost'],
                            'base_tax_cost' => $data['base_tax_cost'],
                            'delivery_cost' => $data['delivery_cost'],
                            'base_delivery_cost' => $data['base_delivery_cost'],
                            'create_date' => $data['create_date'],
                            'update_date' => $data['update_date'],
                            'items' => $data['items']
                        ]
                    ];
                }
            }
        }
        return $batch;
    }

    protected function getCategoriesByWebsite($websiteCode)
    {
        $categories = $this->_getCategories($websiteCode);
        $batchData = [];
        foreach ($categories as $category) {

            $batchData[] = [
                'action' => 'upsert_update',
                'data' => [
                    'id'=>$category->getId(),
                    'status'=>$category->getIsActive()==1?true:false,
                    'name'=>$category->getName(),
                    'url'=>$category->getUrl(),
                    'path'=>$category->getPath()
                ]
            ];
        }
        return $batchData;
    }

    protected function getProducts($storeId)
    {
        $productCollection = $this->_productCollectionFactory->create();
        return $productCollection->addStoreFilter($storeId)->addAttributeToSelect('*');
    }

    /**
     * Returns subscribers by website
     * @param $websiteId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateSubscribers($websiteId)
    {
        $stores = $this->_storeManager->getWebsite($websiteId)->getStores();
        $channelData = [];
        $customerData = [];
        foreach ($stores as $store) {
            $subscriberCollection =$this->_subscriberCollection->create()->addStoreFilter($store->getId());
            foreach($subscriberCollection as $item){
                $subscriberStatus = $item->getSubscriberStatus();
                if ($subscriberStatus=='1') {
                    $status = 'subscribed';
                } elseif ($subscriberStatus=='3') {
                    $status = 'unsubscribed';
                } else {
                    $status = 'non_subscribed';
                }

                $channelData[] = [
                    'action' => 'upsert_update',
                    'data' => [
                        'event_time' => strtotime($item->getChangeStatusAt()),
                        'identifier' => (string)$item->getSubscriberEmail(),
                        'subscription_status' => $status,
                        'subscription_status_change_date' => strtotime($item->getChangeStatusAt())
                    ]
                ];

                if ($item->getCustomerId()==0) {

                    $customerData[] = [
                        'action' => 'upsert_update',
                        'data' => [
                            'email' => (string)$item->getSubscriberEmail()
                        ]
                    ];
                }
            }
        }
        $data['chennel']=$channelData;
        $data['customer']=$customerData;
        return $data;
    }

    /**
     * Returns customers by store
     * @param $store
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerByStore($store)
    {
        $customers = $this->_customerCollectionFactory->create()
            ->addAttributeToSelect("*")
            ->addAttributeToFilter('store_id',$store->getId());
        $userData = [];
        $website = $this->_storeManager->getStore($store->getId())->getWebsite();

        foreach($customers as $item){
            $customerAttributes = $this->_helperApi->getCustomerAttributes($website->getCode());
            $attributes = [];
            foreach($customerAttributes as $attribute){
                $attributes[] = [
                    'code' => (string)$attribute['magento_attribute'],
                    'value' => (string)$item->getData($attribute['magento_attribute'])
                ];
            }

            $userData[] = [
                'customer_id' => $item->getEntityId(),
                'email' => (string)$item->getEmail(),
                'store_code' => $store->getCode(),
                'currency' => $store->getCurrentCurrency()->getCode(),
                'environment' => $store->getCode(),
                'price_list' => 'default',
                'register_date' => strtotime($item->getCreatedAt()),
                'first_name' => (string)$item->getFirstname(),
                'last_name' => (string)$item->getLastname(),
                'attributes' => $attributes,
                'event_time' => strtotime($item->getUpdatedAt())
            ];
        }
        return $userData;
    }

    /**
     * Returns all customers for website
     * @param $data
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function generateCustomers($data){

        $batch = [];
        foreach ($data as $website=>$stores){
            foreach ($stores as $store=>$customers){
                foreach ($customers as $data){
                    $batch[$website]['customers'][] = [
                        'action' => 'upsert_update',
                        'data'=> [
                            'customer_id' => $data['customer_id'],
                            'email' => $data['email'],
                            'store_code' => $data['store_code'],
                            'currency' => $data['currency'],
                            'environment' => $data['environment'],
                            'price_list' => $data['price_list'],
                            'register_date' => $data['register_date'],
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'attributes' => $data['attributes'],
                            'event_time' => $data['event_time']
                        ]
                    ];
                }
            }
        }
        return $batch;
    }
}
