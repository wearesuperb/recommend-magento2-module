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

class UpdateProductsStatus extends CronAbstract
{
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\CatalogInventory\Helper\Stock $catalogInventoryStockHelper,
        \Superb\Recommend\Helper\Data $helper,
        \Superb\Recommend\Helper\Api $apiHelper,
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->catalogConfig = $catalogConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->_catalogInventoryStockHelper = $catalogInventoryStockHelper;
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_apiHelper = $apiHelper;
        $this->_isCronTypeEnabledXmlPath = \Superb\Recommend\Helper\Data::XML_PATH_STATUS_CRON_ENABLED;
    }
}
