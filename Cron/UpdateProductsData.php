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

class UpdateProductsData extends CronAbstract
{
    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $directoryCurrencyFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Catalog\Helper\Product\Flat\Indexer
     */
    protected $_productFlatIndexerHelper;

    /**
     * @var array|null
     */
    protected $_currentStoreCurrencies = null;

    /**
     * @var array|null
     */
    protected $_currentStoreAttributes = null;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\MutableScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\CurrencyFactory $directoryCurrencyFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\CatalogInventory\Helper\Stock $catalogInventoryStockHelper,
        \Magento\Catalog\Helper\Product\Flat\Indexer $productFlatIndexerHelper,
        \Superb\Recommend\Helper\Data $helper,
        \Superb\Recommend\Helper\Api $apiHelper,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->directoryCurrencyFactory = $directoryCurrencyFactory;
        $this->catalogConfig = $catalogConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->_catalogInventoryStockHelper = $catalogInventoryStockHelper;
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_apiHelper = $apiHelper;
        $this->_productFlatIndexerHelper = $productFlatIndexerHelper;
        $this->_isCronTypeEnabledXmlPath = \Superb\Recommend\Helper\Data::XML_PATH_DATA_CRON_ENABLED;
    }

    protected function _resetCurrentStoreData()
    {
        $this->_currentStoreCurrencies = null;
        $this->_currentStoreAttributes = null;
    }

    protected function _getCurrentStoreCurrencies()
    {
        if ($this->_currentStoreCurrencies === null) {
            $currencies = [];
            $codes = $this->storeManager->getStore()->getAvailableCurrencyCodes(true);
            if (is_array($codes)) {
                $rates = $this->directoryCurrencyFactory->create()->getCurrencyRates(
                    $this->storeManager->getStore()->getBaseCurrency(),
                    $codes
                );

                foreach ($codes as $code) {
                    if (isset($rates[$code])) {
                        $currencies[$code] = $this->directoryCurrencyFactory->create()->load($code);
                    } elseif ($code==$this->storeManager->getStore()->getBaseCurrency()->getCode()) {
                        $currencies[$code] = $this->directoryCurrencyFactory->create()->load($code);
                    }
                }
            }
            $this->_currentStoreCurrencies = $currencies;
        }
        return $this->_currentStoreCurrencies;
    }

    protected function _getCurrentStoreAttributes()
    {
        if ($this->_currentStoreAttributes === null) {
            $attributes = @unserialize((string)$this->scopeConfig->getValue(
                \Superb\Recommend\Helper\Data::XML_PATH_TRACKING_PRODUCT_ATTRIBUTES,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ));
            $this->_currentStoreAttributes = is_array($attributes)?$attributes:[];
        }
        return $this->_currentStoreAttributes;
    }
    
    protected function _addAttributesToCollection($collection)
    {
        $attributes = $this->_getCurrentStoreAttributes();
        $_attributes = [];
        foreach ($attributes as $row) {
            $_attributes[] = $row['magento_attribute'];
        }
        $productFlatAttributeCodes = $this->_productFlatIndexerHelper->getAttributeCodes();
        $store = $this->storeManager->getStore();
        foreach ($_attributes as $_attributeCode) {
            $attribute = $this->eavConfig->getAttribute('catalog_product', $_attributeCode);
            if (in_array($_attributeCode, $productFlatAttributeCodes)) {
                $collection->addAttributeToSelect($_attributeCode);
            } else {
                $collection->joinAttribute(
                    $_attributeCode,
                    'catalog_product/'.$_attributeCode,
                    'entity_id',
                    null,
                    'left',
                    $store->getId()
                );
            }
        }
        return $collection;
    }

    protected function _getProductData(&$products, $accountId, $product)
    {
        if (isset($products[$accountId][$product->getSku()])) {
            $finalPrice = $products[$accountId][$product->getSku()]['price'];
            $price = $products[$accountId][$product->getSku()]['original_price'];
        } else {
            $finalPrice = [];
            $price = [];
        }
        $currencies = $this->_getCurrentStoreCurrencies();
        $store = $this->storeManager->getStore();
        foreach ($currencies as $code => $currency) {
            if (!isset($price[$code])) {
                $price[$code] = $store->getBaseCurrency()->convert(
                    $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(),
                    $currency
                );
            }
            if (!isset($finalPrice[$code])) {
                $finalPrice[$code] = $store->getBaseCurrency()->convert(
                    $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(),
                    $currency
                );
            }
        }

        $additionalAttributes = [];
        $eavConfig = $this->eavConfig;
        $attributes = $this->_getCurrentStoreAttributes() ?: [];
        foreach ($attributes as $row) {
            $attribute = $eavConfig->getAttribute('catalog_product', $row['magento_attribute']);
            if ($attribute && $attribute->getId()) {
                $_attributeText = $product->getAttributeText($attribute->getAttributeCode());
                $additionalAttributes[$row['recommend_attribute']] = empty($_attributeText)?
                    $product->getData($attribute->getAttributeCode()):
                    $product->getAttributeText($attribute->getAttributeCode())
                ;
                if (is_array($additionalAttributes[$row['recommend_attribute']])) {
                    $additionalAttributes[$row['recommend_attribute']] = implode(
                        ', ',
                        $additionalAttributes[$row['recommend_attribute']]
                    );
                }
            }
        }

        return [
            'sku'=>$product->getSku(),
            'status'=>'online',
            'url'=>$product->getProductUrl(),
            'price'=>$finalPrice,
            'original_price'=>$price,
            'additional_attributes' => $additionalAttributes
        ];
    }
}
