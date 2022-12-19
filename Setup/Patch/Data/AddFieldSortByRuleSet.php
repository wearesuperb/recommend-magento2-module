<?php

namespace Superb\Recommend\Setup\Patch\Data;

class AddFieldSortByRuleSet implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        \Magento\Eav\Setup\EavSetupFactory                $eavSetupFactory,
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Eav\Api\AttributeRepositoryInterface     $attributeRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->attributeRepository = $attributeRepository;
    }

    public function apply()
    {
        $attributeCode = \Superb\Recommend\Model\Merchandising\Category\ProductPosition::ATTRIBUTE_CODE_MERCHANDISING_RULE;
        try {
            $this->attributeRepository->get(\Magento\Catalog\Model\Category::ENTITY, $attributeCode);
            return;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $setup = $this->eavSetupFactory->create(['setup', $this->moduleDataSetup]);

            $setup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $attributeCode,
                [
                    'type' => 'varchar',
                    'label' => 'Set Rule',
                    'input' => 'select',
                    'sort_order' => 2,
                    'source' => \Superb\Recommend\Model\Config\Source\Rules::class,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_NO,
                    'group' => 'Products in Category'
                ]
            );
        }
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
