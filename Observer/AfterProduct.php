<?php
namespace Superb\Recommend\Observer;

use Exception;
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

            $array = [
                'id'=>$_product->getId(),
                'status'=>$productStock->getIsInStock(),
                'sku'=>$_product->getSku(),
                'name'=>$_product->getName(),
                'lists'=>$_product->getCategoryIds(),
		        'stock_quantity'=>(int)$productStock->getQty(),
                'url'=>$_product->getProductUrl(),
                'image'=>$store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' .$_product->getImage(),
                'description'=>$_product->getShortDescription()?$_product->getShortDescription():'',
                'attributes'=>$attributes,
                'price'=>$price,
                'original_price'=>$original_price
            ];

            $data['products'][$_product->getId()] = $array;
        }
        return $data;
    }
}