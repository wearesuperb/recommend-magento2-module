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

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

/**
 * Class Sync
 */
class Sync extends Command
{
    const DATA = 'data';
    const WEBSITE = 'website';
    const XML_PATH_TRACKING_PRODUCT_ATTRIBUTES = 'superbrecommend/general_settings/product_attributes';

    protected $categoryIds = [];

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
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $catalogImageHelper;

    /**
     * @var State
     */
    protected $state;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface                                 $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory            $categoryCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory                 $orderCollection,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory       $subscriberCollection,
        \Magento\Customer\Model\CustomerFactory                                    $customerFactory,
        \Magento\Catalog\Model\ProductFactory                                      $productFactory,
        \Superb\RecommendWidget\Helper\Api                                         $helperApi,
        \Superb\RecommendWidget\Helper\Data                                        $helper,
        SerializerInterface                                                        $serializer,
        \Magento\Store\Api\StoreWebsiteRelationInterface                           $storeWebsiteRelation,
        State                                                                      $state,
        \Magento\Catalog\Helper\Image                                              $catalogImageHelper,
        \Magento\Framework\App\Helper\Context                                      $context
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        $this->_categoryCollection = $categoryCollection;
        $this->_orderCollection = $orderCollection;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_subscriberCollection = $subscriberCollection;
        $this->_customerFactory = $customerFactory;
        $this->_productFactory = $productFactory;
        $this->_helperApi = $helperApi;
        $this->_helper = $helper;
        $this->serializer = $serializer;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->catalogImageHelper = $catalogImageHelper;
        $this->state = $state;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('recommend:sync');
        $this->setDescription('Sync data with Recommend_2');

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

    protected function getWebsitesList()
    {
        return $this->_storeManager->getWebsites();
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
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $websites = $this->getWebsitesList();

        foreach ($websites as $_website) {
            $stores = $this->storeWebsiteRelation->getStoreByWebsiteId($_website->getId());
            $productAttributes = $this->_helperApi->getAttributes($_website->getCode());
            //Categories array
            $categories[$_website->getCode()] = $this->getCategoriesByWebsite($_website->getCode());

            foreach ($stores as $_storeId) {
                $store = $this->_helperApi->getStore($_storeId);

                $customAttributes = array_merge(
                    $this->_helper->getIdsImagesRecommendPanels(),
                    $this->_helper->getCustomProductAttributes(),
                    $this->_helper->getCustomCustomerAttributes()
                );

                $this->_helperApi->compareCustomAttributes($store->getCode(), $customAttributes);
                $this->_helperApi->getEnviroment($_website->getCode(), $store->getCode());

                //Products array
                $products[$_website->getCode()][$store->getCode()] = $this->getProductsByStore($store, $productAttributes);
            }
        }

        $output->writeln('<info>Data synchronization start</info>');

        $categoriesBatch = $categories;
        $output->writeln('<info>Categories list generated</info>');
        $productBatch = $this->generateProducts($products);
        \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->info(json_encode($productBatch));
        $output->writeln('<info>Products list generated</info>');

        $ordersBatch = $this->generateOrders();
        $output->writeln('<info>Orders list generated</info>');
        $subscribers = $this->generateSubscribers();
        $output->writeln('<info>Subscribers list generated</info>');
        $customers = $this->generateCustomers();
        $customersBatch = array_merge($subscribers['customer'], $customers);

        foreach ($websites as $_website) {
            //TODO: Edit if you need other stores
            if ($_website->getCode() !== 'base') {
                continue;
            }
            $output->writeln('<info>Start upload for store `' . $_website->getCode() . '`</info>');
            $uploadId = $this->_helperApi->initUpload($_website->getCode());
            //$uploadId='123';
            $output->writeln('Generated upload #' . $uploadId);

            if (isset($categoriesBatch[$_website->getCode()])) {
                $this->_helperApi->syncCategories($categoriesBatch[$_website->getCode()], $_website->getCode(), $uploadId);
            }

            if (isset($productBatch[$_website->getCode()])) {
                if (isset($productBatch[$_website->getCode()]['products'])) {
                    $this->_helperApi->syncProducts($productBatch[$_website->getCode()]['products'], $_website->getCode(), $uploadId);
                }
                if (isset($productBatch[$_website->getCode()]['variants'])) {
                    $this->_helperApi->syncVariants($productBatch[$_website->getCode()]['variants'], $_website->getCode(), $uploadId);
                }
            }

            $this->_helperApi->commitBatch($_website->getCode(), $uploadId);
        }

        $output->writeln('Start upload orders');
        //$this->_helperApi->syncOrders($ordersBatch);
        $output->writeln('Start upload chennels');
        //$this->_helperApi->sendChennelData($subscribers['chennel']);
        $output->writeln('Start upload customers');
        //$this->_helperApi->sendCustomer($customersBatch);


    }

    protected function checkCategory($category)
    {
        $cat = $category->getParentCategory();
        if($cat!==null&&$cat->getPath()!='1/2'&&$cat->getIsAnchor()=='1'&&$cat->getIsActive()=='1'){
            $this->categoryIds[] = $cat->getId();
            $this->checkCategory($cat);
        }
    }

    protected function getProductsByStore($store, $productAttributes)
    {
        $productsCollection = $this->getProducts($store->getId());
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $stockItem = $objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');

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

                $customAttributes = array_merge(
                    $this->_helper->getIdsImagesRecommendPanels(),
                    $this->_helper->getCustomProductAttributes()
                );

                foreach ($customAttributes as $customAttribute) {
                    if ($customAttribute['code'] == 'created_at') {
                        $attributes[] = [
                            'code' => $customAttribute['code'],
                            'value' => strtotime($_product->getCreatedAt())
                        ];
                    }

                    if (strpos($customAttribute['code'], 'recommend_img') !== false) {
                        $imageUrl = $this->catalogImageHelper->init($_product, $customAttribute['code'])->getUrl();
                        $attributes[] = [
                            'code' => $customAttribute['code'],
                            'value' => $imageUrl
                        ];
                    }
                }

                $original_price = [];
                $price = [];

                $original_price[] = [
                    'code' => 'default',
                    'prices' => [0 => [
                        'currency' => $store->getCurrentCurrency()->getCode(),
                        'value' => (float)$_product->getPrice()
                    ]]
                ];


                $price[] = [
                    'code' => 'default',
                    'prices' => [0 => [
                        'currency' => $store->getCurrentCurrency()->getCode(),
                        'value' => (float)$_product->getFinalPrice()
                    ]]
                ];
                if ($_product->getTypeId() == 'configurable') {
                    $original_price[0] = [
                        'code' => 'default',
                        'prices' => [0 => [
                            'currency' => $store->getCurrentCurrency()->getCode(),
                            'value' => (float)$_product->getPriceInfo()->getPrice('regular_price')->getMinRegularAmount()->getValue()
                        ]]
                    ];
                }
                $parents = $this->_catalogProductTypeConfigurable->getParentIdsByChild($_product->getId());

                $categories = $_product->getCategoryCollection();
                $this->categoryIds = [];
                foreach($categories as $category){
                    $this->categoryIds[] = $category->getId();
                    $this->checkCategory($category);
                }
                $this->categoryIds = array_values(array_unique($this->categoryIds));

                $array = [
                    'id' => $_product->getId(),
                    'status' => $this->isItEnabled($_product, $productStock),
                    'sku' => $_product->getSku(),
                    'name' => $_product->getName() ?? '',
                    'lists' => $this->categoryIds,
                    'url' => $_product->getProductUrl(),
                    'image' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage(),
                    'description' => $_product->getShortDescription() ? $_product->getShortDescription() : '',
                    'stock_quantity' => $productStock->getQty() ?? 0,
                    'attributes' => $attributes,
                    'price' => $price,
                    'original_price' => $original_price
                ];

                //if (isset($parents[0])) {
                //    $data[$parents[0]]['childs'][$_product->getId()] = $array;
                //} else {
                $data[$_product->getId()] = $array;
                //}
            }
        }
        return $data;
    }

    private function isItEnabled($_product, $productStock): bool
    {
        if ($_product->getStatus() == ProductStatus::STATUS_DISABLED) {
            return false;
        }

        if (!$productStock->getIsInStock()) {
            return false;
        }

        $regularPrice = $_product->getPriceInfo()->getPrice('regular_price');

        if ($regularPrice && $regularPrice->getValue() <= 0) {
            return false;
        }

        return true;
    }

    protected function generateProducts($data)
    {
        $batch = [];
        foreach ($data as $website => $stores) {
            $defaultStore = $this->_helperApi->getWebsite($website)->getDefaultStore();
            foreach ($stores[$defaultStore->getCode()] as $product) {
                $env = [];
                $child_env = [];
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

                    if (isset($enviroment['childs'])) {
                        foreach ($enviroment['childs'] as $enviromentChild) {
                            $child_env[$enviromentChild['id']][] = [
                                'code' => $code,
                                'data' => [
                                    'status' => $enviromentChild['status'],
                                    'name' => $enviromentChild['name'],
                                    'lists' => $enviromentChild['lists'],
                                    'url' => $enviromentChild['url'],
                                    'image' => $enviromentChild['image'],
                                    'description' => $enviromentChild['description'],
                                    'stock_quantity' => $enviromentChild['stock_quantity'],
                                    'attributes' => $enviromentChild['attributes'],
                                    'price' => $enviromentChild['price'],
                                    'original_price' => $enviromentChild['original_price']
                                ]
                            ];
                        }
                    }
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

                if (isset($product['childs'])) {
                    foreach ($product['childs'] as $variant) {
                        $batch[$website]['variants'][] = [
                            'action' => 'upsert_update',
                            'data' => [
                                'master_sku' => $product['sku'],
                                'id' => $variant['id'],
                                'status' => $variant['status'],
                                'sku' => $variant['sku'],
                                'name' => $variant['name'],
                                'lists' => $variant['lists'],
                                'url' => $variant['url'],
                                'image' => $variant['image'],
                                'description' => $variant['description'],
                                'stock_quantity' => $variant['stock_quantity'],
                                'attributes' => $variant['attributes'],
                                'environment' => $child_env[$variant['id']],
                                'price' => $variant['price'],
                                'original_price' => $variant['original_price']
                            ]
                        ];
                    }
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
                    'id' => $category->getId(),
                    'status' => $category->getIsActive() == 1 ? true : false,
                    'name' => $category->getName(),
                    'url' => $category->getUrl(),
                    'path' => $category->getPath()
                ]
            ];
        }
        return $batchData;
    }

    protected function getProducts($storeId)
    {
        $productCollection = $this->_productFactory->create()
            ->setStoreId($storeId)
            ->getCollection()
            ->addAttributeToSelect('*');
        return $productCollection;
    }

    protected function generateOrders()
    {
        $now = new \DateTime();
        $startDate = date("Y-m-d h:i:s", strtotime('2020-12-31')); // start date
        $endDate = date("Y-m-d h:i:s", strtotime('2021-1-20')); // end date
        $orders = $this->_orderCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('created_at', array('from' => $startDate, 'to' => $endDate));

        $data = [];

        foreach ($orders as $order) {
            $items = $order->getAllVisibleItems();
            $_items = [];
            foreach ($items as $item) {
                $productOptions = $item->getProductOptions();

                $_items[] = [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'variation_sku' => isset($productOptions['simple_sku']) ? $productOptions['simple_sku'] : '',
                    'quantity' => (int)$item->getQtyOrdered(),
                    'cost' => (float)$item->getPrice(),
                    'base_cost' => (float)$item->getBasePrice(),
                    'discount' => (float)$item->getDiscountAmount(),
                    'base_discount' => (float)$item->getBaseDiscountAmount()
                ];
            }

            $data[] = [
                'action' => 'upsert_update',
                'data' => [
                    'order_id' => $order->getIncrementId(),
                    'status' => $order->getStatus(),
                    'customer_id' => $order->getCustomerId() ? $order->getCustomerId() : '',
                    'email' => $order->getCustomerEmail(),
                    'full_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
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
                ]
            ];
        }

        return $data;
    }

    protected function generateSubscribers()
    {
        $subscriberCollection = $this->_subscriberCollection->create();
        $channelData = [];
        $customerData = [];
        foreach ($subscriberCollection as $item) {
            $subscriberStatus = $item->getSubscriberStatus();
            if ($subscriberStatus == '1') {
                $status = 'subscribed';
            } elseif ($subscriberStatus == '3') {
                $status = 'unsubscribed';
            } else {
                $status = 'non_subscribed';
            }

            $channelData[] = [
                'action' => 'upsert_update',
                'data' => [
                    'event_time' => strtotime($item->getChangeStatusAt() ?? ''),
                    'identifier' => $item->getSubscriberEmail(),
                    'subscription_status' => $status,
                    'subscription_status_change_date' => strtotime($item->getChangeStatusAt() ?? '')
                ]
            ];

            if ($item->getCustomerId() == 0) {

                $customerData[] = [
                    'action' => 'upsert_update',
                    'data' => [
                        'email' => $item->getSubscriberEmail()
                    ]
                ];
            }
        }
        $data['chennel'] = $channelData;
        $data['customer'] = $customerData;
        return $data;
    }

    protected function generateCustomers()
    {
        $customers = $this->_customerFactory->create()->getCollection()
            ->addAttributeToSelect("*")->load();
        $userData = [];
        $website = $this->_helperApi->getDefaultWebsite();
        foreach ($customers as $item) {
            $customerAttributes = $this->_helperApi->getCustomerAttributes($website->getCode());
            $attributes = [];
            foreach ($customerAttributes as $attribute) {
                $attributes[] = [
                    'code' => $attribute['magento_attribute'],
                    'value' => $item->getData($attribute['magento_attribute'])
                ];
            }


            $userData[] = [
                'action' => 'upsert_update',
                'data' => [
                    'customer_id' => $item->getEntityId(),
                    'email' => $item->getEmail(),
                    'store_code' => $website->getCode(),
                    'currency' => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
                    'environment' => $this->_storeManager->getStore()->getCode(),
                    'price_list' => 'default',
                    'register_date' => strtotime($item->getCreatedAt()),
                    'first_name' => $item->getFirstname(),
                    'last_name' => $item->getLastname(),
                    'date_of_birth' => $item->getDob(),
                    'attributes' => $attributes,
                    'event_time' => strtotime($item->getUpdatedAt())
                ]
            ];
        }
        return $userData;
    }
}
