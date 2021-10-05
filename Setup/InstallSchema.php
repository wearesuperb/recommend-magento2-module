<?php

namespace Superb\Recommend\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Superb\Recommend\Model\Product\Link;
use Superb\Recommend\Ui\DataProvider\Product\Form\Modifier\Recommend;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /**
         * Install product link types in table (catalog_product_link_type)
         */
        $catalogProductLinkTypeData = [
            'link_type_id' => Link::LINK_TYPE_RECOMMEND,
            'code' => Recommend::DATA_SCOPE_RECOMMEND
        ];

        $setup->getConnection()->insertOnDuplicate(
            $setup->getTable('catalog_product_link_type'),
            $catalogProductLinkTypeData
        );

        /**
         * install product link attributes position in table catalog_product_link_attribute
         */
        $catalogProductLinkAttributeData = [
            'link_type_id' => Link::LINK_TYPE_RECOMMEND,
            'product_link_attribute_code' => 'position',
            'data_type' => 'int',
        ];

        $setup->getConnection()->insert(
            $setup->getTable('catalog_product_link_attribute'),
            $catalogProductLinkAttributeData
        );

        $setup->endSetup();
    }
}
