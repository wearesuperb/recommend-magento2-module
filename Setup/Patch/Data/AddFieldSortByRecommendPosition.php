<?php

namespace Superb\Recommend\Setup\Patch\Data;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\PatchInterface;

class AddFieldSortByRecommendPosition implements \Magento\Framework\Setup\Patch\DataPatchInterface
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
        $attributeCode = \Superb\Recommend\Model\Merchandising\Category\ProductPosition::ATTRIBUTE_CODE_SET_SORT_BY_RECOMMEND_POSITION;
        try {
            $this->attributeRepository->get(\Magento\Catalog\Model\Category::ENTITY, $attributeCode);
            return;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $setup = $this->eavSetupFactory->create(['setup', $this->moduleDataSetup]);

            $setup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $attributeCode,
                [
                    'type' => 'int',
                    'label' => 'Set sort by Recommend position',
                    'input' => 'boolean',
                    'sort_order' => 1,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
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
