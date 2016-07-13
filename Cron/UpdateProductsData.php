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

class UpdateProductsData
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $directoryCurrencyFactory;

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
     * @var \Magento\Framework\App\Config\ElementFactory
     */
    protected $configElementFactory;

    /**
     * Product collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Catalog\Helper\Product\Flat\Indexer
     */
    protected $_productFlatIndexerHelper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\CurrencyFactory $directoryCurrencyFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\CatalogInventory\Helper\Stock $catalogInventoryStockHelper,
        \Magento\Framework\App\Config\ElementFactory $configElementFactory,
        \Magento\Catalog\Helper\Product\Flat\Indexer $productFlatIndexerHelper,
        \Superb\Recommend\Helper\Api $apiHelper,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
        $this->configElementFactory = $configElementFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->directoryCurrencyFactory = $directoryCurrencyFactory;
        $this->catalogConfig = $catalogConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->_catalogInventoryStockHelper = $catalogInventoryStockHelper;
        $this->_logger = $logger;
        $this->_apiHelper = $apiHelper;
        $this->_productFlatIndexerHelper = $productFlatIndexerHelper;
    }

    public function execute(\Magento\Cron\Model\Schedule $schedule)
    {
        if (!$this->scopeConfig->getValue( \Superb\Recommend\Helper\Data::XML_PATH_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE )) {
            return $this;
        }
        if (!$this->scopeConfig->getValue( \Superb\Recommend\Helper\Data::XML_PATH_DATA_CRON_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE )) {
            return $this;
        }
        $products = array();
        try {
            $this->storeManager->setCurrentStore('admin');
            $productFlatAttributeCodes = $this->_productFlatIndexerHelper->getAttributeCodes();
            $storesByAccounts = array();
            foreach ($this->storeManager->getStores() as $store)
            {
                if (!$this->scopeConfig->getValue( \Superb\Recommend\Helper\Data::XML_PATH_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId()))
                    continue;
                $accountId = $this->scopeConfig->getValue(\Superb\Recommend\Helper\Api::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$store->getId());
                if (!isset($storesByAccounts[$accountId]))
                    $storesByAccounts[$accountId] = array();
                $storesByAccounts[$accountId][] = $store->getId();
            }
            foreach ($this->storeManager->getStores() as $store)
            {
                if (!$this->scopeConfig->getValue( \Superb\Recommend\Helper\Data::XML_PATH_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId()))
                    continue;
                $accountId = $this->scopeConfig->getValue(\Superb\Recommend\Helper\Api::XML_PATH_TRACKING_ACCOUNT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$store->getId());
                $this->storeManager->setCurrentStore($store);
                $currencies = array(); 
                $codes = $this->storeManager->getStore()->getAvailableCurrencyCodes(true);
                if (is_array($codes)) {
                    $rates = $this->directoryCurrencyFactory->create()->getCurrencyRates(
                        $this->storeManager->getStore()->getBaseCurrency(),
                        $codes
                    );

                    foreach ($codes as $code) {
                        if (isset($rates[$code])) {
                            $currencies[$code] = $this->directoryCurrencyFactory->create()->load($code);
                        }
                        elseif($code==$this->storeManager->getStore()->getBaseCurrency()->getCode())
                        {
                            $currencies[$code] = $this->directoryCurrencyFactory->create()->load($code);
                        }
                    }
                }
                $collection = $this->productCollectionFactory->create();
                $collection->setStore($store)->setStoreId($store->getStoreId());
                $attributes = @unserialize((string)$this->scopeConfig->getValue(\Superb\Recommend\Helper\Data::XML_PATH_TRACKING_PRODUCT_ATTRIBUTES, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
                $attributes = is_array($attributes)?$attributes:[];
                $_attributes = array();
                foreach ($attributes as $row)
                {
                   $_attributes[] = $row['magento_attribute'];
                }

                foreach($_attributes as $_attributeCode)
                {
                    $attribute = $this->eavConfig->getAttribute('catalog_product', $_attributeCode);
                    if (in_array($_attributeCode,$productFlatAttributeCodes))
                        $collection->addAttributeToSelect($_attributeCode);
                    else
                        $collection->joinAttribute(
                            $_attributeCode,
                            'catalog_product/'.$_attributeCode,
                            'entity_id',
                            null,
                            'left',
                            $store->getId()
                        );
                }
                $collection
                    ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
                    ->addMinimalPrice()
                    ->addFinalPrice()
                    ->addTaxPercents()
                    ->addStoreFilter($store)
                    ->addUrlRewrite();

                $collection->setVisibility($this->catalogProductVisibility->getVisibleInSiteIds());
                if (!$this->_apiHelper->getShowOutOfStockProduct($store->getStoreId())) {
                    $this->_catalogInventoryStockHelper->addIsInStockFilterToCollection($collection);
                }

                $isEmpty = false;
                $offset = 0;
                while (!$isEmpty) {
                    $productCol = clone $collection;
                    $productCol->getSelect()->limit(\Superb\Recommend\Helper\Data::LIMIT_STEP, $offset);

                    $isEmpty = true;
                    foreach($productCol as $product)
                    {
                        $isEmpty = false;
                        $offset++;
                        if (!isset($products[$accountId]))
                            $products[$accountId] = array();
                        if (isset($products[$accountId][$product->getSku()]))
                        {
                            $finalPrice = $products[$accountId][$product->getSku()]['price'];
                            $price = $products[$accountId][$product->getSku()]['original_price'];
                        }
                        else
                        {
                            $finalPrice = array();
                            $price = array();
                        }
                        foreach($currencies as $code => $currency)
                        {
                            if (!isset($price[$code]))
                                $price[$code] = $store->getBaseCurrency()->convert($product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), $currency);
                            if (!isset($finalPrice[$code]))
                                $finalPrice[$code] = $store->getBaseCurrency()->convert($product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(), $currency);
                        }

                        $additionalAttributes = array();
                        $eavConfig = $this->eavConfig;
                        foreach ($attributes as $row)
                        {
                            $attribute = $eavConfig->getAttribute('catalog_product', $row['magento_attribute']);
                            if ($attribute && $attribute->getId())
                            {
                                $_attributeText = $product->getAttributeText($attribute->getAttributeCode());
                                $additionalAttributes[$row['recommend_attribute']] = empty($_attributeText)?$product->getData($attribute->getAttributeCode()):$product->getAttributeText($attribute->getAttributeCode());
                                if (is_array($additionalAttributes[$row['recommend_attribute']]))
                                {
                                    $additionalAttributes[$row['recommend_attribute']] = implode(', ',$additionalAttributes[$row['recommend_attribute']]);
                                }
                            }
                        }

                        $products[$accountId][$product->getSku()] = array('sku'=>$product->getSku(),'status'=>'online','url'=>$product->getProductUrl(),'price'=>$finalPrice,'original_price'=>$price,'additional_attributes' => $additionalAttributes);
                    }
                }
            }
            $this->storeManager->setCurrentStore('admin');
            $this->_logger->info(json_encode($products));
            foreach($products as $accountId => $productsData)
            {
                $this->_apiHelper->uploadProductsData(array_values($productsData),$storesByAccounts[$accountId][0]);
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        return $this;
    }
}
