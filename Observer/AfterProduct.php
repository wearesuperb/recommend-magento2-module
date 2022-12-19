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

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Superb\Recommend\Helper\Api $apiHelper,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Store\Api\StoreWebsiteRelationInterface $storeWebsiteRelation,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_apiHelper = $apiHelper;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->_productFactory = $productFactory;
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

        foreach($websites as $websiteId){
            $_website = $this->_storeManager->getWebsite($websiteId);
            $stores = $this->storeWebsiteRelation->getStoreByWebsiteId($_website->getId());
            $productAttributes = $this->_apiHelper->getAttributes($_website->getCode());

            $env = [];

            foreach($stores as $_storeId){
                $store = $this->_apiHelper->getStore($_storeId);
                $this->_apiHelper->getEnviroment($_website->getCode(),$store->getCode());

                //Products array
                $data = $this->getProductByStore($store,$productAttributes,$item->getId());
                $product[$_website->getCode()][$store->getCode()]=$data;

                if (isset($data['products'])) {
                    foreach($data['products'] as $enviroment) {
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
                    foreach($data['childs'] as $enviroment) {
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
                foreach($product[$_website->getCode()][$_website->getDefaultStore()->getCode()]['products'] as $_product){
                    $batch[] = [
                        'action' => 'upsert_update',
                        'data' => [
                            'id' =>$_product['id'],
                            'status' => $_product['status'],
                            'sku'=> $_product['sku'],
                            'name'=> $_product['name'],
                            'lists'=> $_product['lists'],
                            'url'=> $_product['url'],
                            'image'=> $_product['image'],
                            'description'=> $_product['description'],
			    'stock_quantity'=> $_product['stock_quantity'],
                            'attributes'=> $_product['attributes'],
                            'environment'=> $env,
                            'price'=> $_product['price'],
                            'original_price'=> $_product['original_price']
                        ]
                    ];

                    $this->_apiHelper->uploadProducts($batch,$_website->getCode());
                }
            }
            if (isset($product[$_website->getCode()][$_website->getDefaultStore()->getCode()]['childs'])) {
                foreach($product[$_website->getCode()][$_website->getDefaultStore()->getCode()]['childs'] as $_variant){
                    $batch[] = [
                        'action' => 'upsert_update',
                        'data' => [
                            'id' =>$_variant['id'],
                            'master_sku' =>$_variant['parent'],
                            'status' => $_variant['status'],
                            'sku'=> $_variant['sku'],
                            'name'=> $_variant['name'],
                            'lists'=> $_variant['lists'],
                            'url'=> $_variant['url'],
                            'image'=> $_variant['image'],
                            'description'=> $_variant['description'],
                            'attributes'=> $_variant['attributes'],
                            'environment'=> $env,
                            'price'=> $_variant['price'],
                            'original_price'=> $_variant['original_price']
                        ]
                    ];

                    $this->_apiHelper->uploadVariants($batch,$_website->getCode());
                }
            }
        }
        return $this;
    }

    protected function getProducts($storeId,$id)
    {
        $productCollection = $this->_productFactory->create()
                                    ->setStoreId($storeId)
                                    ->getCollection()
                                    ->addAttributeToSelect('*')
                                    ->addAttributeToFilter('entity_id',$id);
        return $productCollection;
    }

    protected function getProductByStore($store,$productAttributes,$id)
    {
        $productsCollection = $this->getProducts($store->getId(),$id);
	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$stockItem = $objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');

        $data = [];
        foreach($productsCollection as $_product){
//var_dump($_product->getExtensionAttributes()->getStockItem());die;
	    $productStock = $stockItem->getStockItem($_product->getEntityId());
            $attributes=[];
            foreach($productAttributes as $productAttribute){
                $attributes[] = [
                    'code'=>$productAttribute['magento_attribute'],
                    'value'=>$_product->getData($productAttribute['magento_attribute'])
                ];
            }
            $original_price = [];
            $price = [];

            $original_price[] = [
                'code'=>'default',
                'prices'=>[0=>[
                    'currency'=>$store->getCurrentCurrency()->getCode(),
                    'value'=>(float)$_product->getPrice()
                ]]
            ];


            $price[] = [
                'code'=>'default',
                'prices'=>[0=>[
                    'currency'=>$store->getCurrentCurrency()->getCode(),
                    'value'=>(float)$_product->getFinalPrice()
                ]]
            ];

            $parents = $this->_catalogProductTypeConfigurable->getParentIdsByChild($_product->getId());

            $array = [
                'id'=>$_product->getId(),
                'status'=> $this->isItEnabled($_product, $productStock),
                'sku'=>$_product->getSku(),
                'name'=>$_product->getName(),
                'lists'=>$_product->getCategoryIds(),
		'stock_quantity'=>$productStock->getQty(),
                'url'=>$_product->getProductUrl(),
                'image'=>$store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' .$_product->getImage(),
                'description'=>$_product->getShortDescription()?$_product->getShortDescription():'',
                'attributes'=>$attributes,
                'price'=>$price,
                'original_price'=>$original_price
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
