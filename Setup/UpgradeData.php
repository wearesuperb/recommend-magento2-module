<?php
namespace Superb\Recommend\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Superb\Recommend\Model\Product\Link;
use Superb\Recommend\Ui\DataProvider\Product\Form\Modifier\Recommend;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * InstallData constructor.
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.2', '<'))
        {
            $data = [
                [
                    'link_type_id' => Link::LINK_TYPE_RECOMMEND,
                    'code' => Recommend::DATA_SCOPE_RECOMMEND
                ],
            ];

            foreach ($data as $bind) {
                $setup->getConnection()->insertForce($setup->getTable('catalog_product_link_type'), $bind);
            }

            $data = [
                [
                    'link_type_id' => Link::LINK_TYPE_RECOMMEND,
                    'product_link_attribute_code' => 'position',
                    'data_type' => 'int',
                ]
            ];

            $setup->getConnection()->insertMultiple($setup->getTable('catalog_product_link_attribute'), $data);
        }

        if (version_compare($context->getVersion(), '2.0.3', '<'))
        {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'recommend_enable',
                [
                    'type' => 'int',
                    'label' => 'Enable Recommend',
                    'input' => 'boolean',
                    'source' => '',
                    'required' => true,
                    'default' => 0,
                    'visible' => true,
                    'system' => false,
                    'backend' => ''
                ]
            );
        }
    }
}
