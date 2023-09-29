<?php

namespace Superb\Recommend\Observer;

use Exception;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AfterProduct
 * @package Superb\Recommend\Observer
 */
class AfterProduct implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    protected $categoryIds = [];

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $_catalogProductTypeConfigurable;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeWebsiteRelation;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Superb\RecommendWidget\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $catalogImageHelper;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Superb\RecommendWidget\Helper\Api $apiHelper,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Store\Api\StoreWebsiteRelationInterface $storeWebsiteRelation,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Superb\RecommendWidget\Helper\Data $_helper,
        \Magento\Catalog\Helper\Image $catalogImageHelper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_apiHelper = $apiHelper;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->_productFactory = $productFactory;
        $this->_helper = $_helper;
        $this->catalogImageHelper = $catalogImageHelper;
    }

    /**
     * @param Observer $observer
     *
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $item = $observer->getDataObject();
        $websites = $item->getWebsiteIds();
        $product = [];

        foreach ($websites as $websiteId) {
            $_website = $this->_storeManager->getWebsite($websiteId);
            $stores = $this->storeWebsiteRelation->getStoreByWebsiteId($_website->getId());
            $productAttributes = $this->_apiHelper->getAttributes($_website->getCode());

            $env = [];

            foreach ($stores as $_storeId) {
                $store = $this->_apiHelper->getStore($_storeId);

                $customAttributes = array_merge(
                    $this->_helper->getIdsImagesRecommendPanels(),
                    $this->_helper->getCustomProductAttributes()
                );

                $this->_apiHelper->compareCustomAttributes($store->getCode(), $customAttributes);
                $this->_apiHelper->getEnviroment($_website->getCode(), $store->getCode());

                //Products array
                $data = $this->getProductByStore($store, $productAttributes, $item->getId());
                $product[$_website->getCode()][$store->getCode()] = $data;

                if (isset($data['products'])) {
                    foreach ($data['products'] as $enviroment) {
                        $env[] = [
                            'code' => $store->getCode(),
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
                }
                if (isset($data['childs'])) {
                    foreach ($data['childs'] as $enviroment) {
                        $env[] = [
                            'code' => $store->getCode(),
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
                }
            }

            if (isset($product[$_website->getCode()][$_website->getDefaultStore()->getCode()]['products'])) {
                foreach ($product[$_website->getCode()][$_website->getDefaultStore()->getCode()]['products'] as $_product) {
                    $batch[] = [
                        'action' => 'upsert_update',
                        'data' => [
                            'id' => $_product['id'],
                            'status' => $_product['status'],
                            'sku' => $_product['sku'],
                            'name' => $_product['name'],
                            'lists' => $_product['lists'],
                            'url' => $_product['url'],
                            'image' => $_product['image'],
                            'description' => $_product['description'],
                            'stock_quantity' => $_product['stock_quantity'],
                            'attributes' => $_product['attributes'],
                            'environment' => $env,
                            'price' => $_product['price'],
                            'original_price' => $_product['original_price']
                        ]
                    ];

                    $this->_apiHelper->uploadProducts($batch, $_website->getCode());
                }
            }
            if (isset($product[$_website->getCode()][$_website->getDefaultStore()->getCode()]['childs'])) {
                foreach ($product[$_website->getCode()][$_website->getDefaultStore()->getCode()]['childs'] as $_variant) {
                    $batch[] = [
                        'action' => 'upsert_update',
                        'data' => [
                            'id' => $_variant['id'],
                            'master_sku' => $_variant['parent'],
                            'status' => $_variant['status'],
                            'sku' => $_variant['sku'],
                            'name' => $_variant['name'],
                            'lists' => $_variant['lists'],
                            'url' => $_variant['url'],
                            'image' => $_variant['image'],
                            'description' => $_variant['description'],
                            'attributes' => $_variant['attributes'],
                            'environment' => $env,
                            'price' => $_variant['price'],
                            'original_price' => $_variant['original_price']
                        ]
                    ];

                    $this->_apiHelper->uploadVariants($batch, $_website->getCode());
                }
            }
        }
        return $this;
    }

    protected function getProducts($storeId, $id)
    {
        $productCollection = $this->_productFactory->create()
            ->setStoreId($storeId)
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', $id);
        return $productCollection;
    }

    protected function checkCategory($category)
    {
        $cat = $category->getParentCategory();
        if($cat!==null&&$cat->getPath()!='1/2'&&$cat->getIsAnchor()=='1'&&$cat->getIsActive()=='1'){
            $this->categoryIds[] = $cat->getId();
            $this->checkCategory($cat);
        }
    }

    protected function getProductByStore($store, $productAttributes, $id)
    {
        $productsCollection = $this->getProducts($store->getId(), $id);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $stockItem = $objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
        $priceCurrencyObject = $objectManager->get('Magento\Framework\Pricing\PriceCurrencyInterface');

        $data = [];
        $currencies = $this->_helper->getCurrenciesByStoreStore($store);

        foreach ($productsCollection as $_product) {
            $productStock = $stockItem->getStockItem($_product->getEntityId());
            $attributes = [];
            foreach ($productAttributes as $productAttribute) {
                $attributes[] = [
                    'code' => $productAttribute['magento_attribute'],
                    'value' => $_product->getData($productAttribute['magento_attribute'])
                ];
            }
            foreach ($this->_helper->getIdsImagesRecommendPanels() as $imagesRecommendPanel) {
                $imageUrl = $this->catalogImageHelper->init($_product, $imagesRecommendPanel['code'])->getUrl();
                $attributes[] = [
                    'code' => $imagesRecommendPanel['code'],
                    'value' => $imageUrl
                ];
            }
            foreach ($this->_helper->getCustomProductAttributes() as $customProductAttribute) {
                if ($customProductAttribute['code'] == 'created_at') {
                    $attributes[] = [
                        'code' => $customProductAttribute['code'],
                        'value' => strtotime($_product->getCreatedAt())
                    ];
                }
            }

            $original_price = [
                'code' => 'default',
                'prices' => []
            ];

            $price = [
                'code' => 'default',
                'prices' => []
            ];

            foreach ($currencies as $code => $currency) {
                $original_price[] = [
                    'currency' => $code,
                    'value' => (float) number_format($priceCurrencyObject->convert(
                            $_product->getPrice(),
                            $store,
                            $currency),
                        2)
                ];

                $price[] = [
                    'currency' => $code,
                    'value' => (float) number_format($priceCurrencyObject->convert(
                            $_product->getFinalPrice(),
                            $store,
                            $currency),
                        2)
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
                'name' => $_product->getName(),
                'lists' => $this->categoryIds,
                'stock_quantity' => $productStock->getQty() ?? 0,
                'url' => $_product->getProductUrl(),
                'image' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage(),
                'description' => $_product->getShortDescription() ? $_product->getShortDescription() : '',
                'attributes' => $attributes,
                'price' => [$price],
                'original_price' => [$original_price]
            ];

            if (isset($parents[0])) {
                $array['parent'] = $parents[0];
                $data['childs'][$_product->getId()] = $array;
            } else {
                $data['products'][$_product->getId()] = $array;
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
}
