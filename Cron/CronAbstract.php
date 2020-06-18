<?php
namespace Superb\Recommend\Cron;

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

abstract class CronAbstract
{
    /**
     * @var \Superb\Recommend\Logger\Logger
     */
    protected $_logger;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\MutableScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $catalogProductVisibility;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $_catalogInventoryStockHelper;

    /**
     * Product collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var string
     */
    protected $_isCronTypeEnabledXmlPath;

    public function getStoresByAccounts()
    {
        $storesByAccounts = [];
        foreach ($this->storeManager->getStores() as $store) {
            if (!$this->_helper->isEnabled($store->getId())) {
                continue;
            }
            $accountId = $this->_helper->getAccountId($store->getId());
            if (!isset($storesByAccounts[$accountId])) {
                $storesByAccounts[$accountId] = [];
            }
            $storesByAccounts[$accountId][] = $store->getId();
        }
        return $storesByAccounts;
    }

    public function execute(\Magento\Cron\Model\Schedule $schedule)
    {
        if (!$this->_helper->isEnabled()) {
            return $this;
        }
        if (!$this->scopeConfig->getValue(
            $this->_isCronTypeEnabledXmlPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return $this;
        }
        $products = [];
        try {
            $showOutOfStock = $this->scopeConfig->getValue(
                \Magento\CatalogInventory\Model\Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if ($showOutOfStock == "1") {
                $this->scopeConfig->setValue(
                    \Magento\CatalogInventory\Model\Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
                    "0",
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            }
            $this->storeManager->setCurrentStore('admin');
            $storesByAccounts = $this->getStoresByAccounts();
            foreach ($this->storeManager->getStores() as $store) {
                if (!$this->_helper->isEnabled($store->getId())) {
                    continue;
                }
                $accountId = $this->_helper->getAccountId($store->getId());
                $this->storeManager->setCurrentStore($store);
                $this->_resetCurrentStoreData();
                $collection = $this->productCollectionFactory->create();
                $collection->setStore($store)->setStoreId($store->getStoreId());
                $this->_addAttributesToCollection($collection);
                $collection
                    ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
                    ->addMinimalPrice()
                    ->addFinalPrice()
                    ->addTaxPercents()
                    ->addStoreFilter($store)
                    ->addUrlRewrite();

                $collection->setVisibility($this->catalogProductVisibility->getVisibleInSiteIds());
                if (!$this->_apiHelper->getShowOutOfStockProduct($store->getStoreId())) {
                    $collection->joinField(
                        'stock_status', 'cataloginventory_stock_status', 'stock_status', 'product_id=entity_id', '{{table}}.stock_id=1', 'left'
                    )->addFieldToFilter('stock_status', array('eq' => \Magento\CatalogInventory\Model\Stock\Status::STATUS_IN_STOCK));
                }

                $isEmpty = false;
                $offset = 0;
                while (!$isEmpty) {
                    $productCol = clone $collection;
                    $productCol->getSelect()->limit(\Superb\Recommend\Helper\Data::LIMIT_STEP, $offset);

                    $isEmpty = true;
                    if ($productCol->count()) {
                        foreach ($productCol as $product) {
                            $isEmpty = false;
                            $offset++;
                            if (!isset($products[$accountId])) {
                                $products[$accountId] = [];
                            }

                            $products[$accountId][$product->getSku()] = $this->_getProductData($products, $accountId, $product);
                        }
                    }
                }
            }
            if ($showOutOfStock == "1") {
                $this->scopeConfig->setValue(
                    \Magento\CatalogInventory\Model\Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
                    "1",
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            }
            $this->storeManager->setCurrentStore('admin');
            $this->_logger->info(json_encode($products));
            foreach ($products as $accountId => $productsData) {
                $this->_apiHelper->uploadProductsData(array_values($productsData), $storesByAccounts[$accountId][0]);
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        return $this;
    }

    protected function _resetCurrentStoreData()
    {
    }

    protected function _addAttributesToCollection($collection)
    {
        return $collection;
    }

    protected function _getProductData(&$products, $accountId, $product)
    {
        return ['sku'=>$product->getSku(),'status'=>'online'];
    }
}
