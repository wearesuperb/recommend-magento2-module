<?php

namespace Superb\Recommend\Plugin\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar as MagentoToolbar;
use Magento\Framework\Registry;
use Superb\Recommend\Helper\Data;
use Superb\Recommend\Model\Merchandising\Category\ProductPosition;


class Toolbar
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ProductPosition
     */
    protected $productPositionModel;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @param Registry $coreRegistry
     * @param ProductPosition $productPositionModel
     * @param Data $dataHelper
     */
    public function __construct(
        Registry $coreRegistry,
        ProductPosition $productPositionModel,
        Data $dataHelper
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->productPositionModel = $productPositionModel;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Plugin
     *
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @return \Magento\Catalog\Block\Product\ProductList\Toolbar
     */
    public function afterSetCollection(MagentoToolbar $subject)
    {
        if (!$this->dataHelper->isEnabled()) {
            return $subject;
        }

        if ($this->getCurrentCategory() && $this->productPositionModel->isEnabledSortByRecommendPosition($this->getCurrentCategory())
            && $subject->getCurrentOrder() === 'position') {
            $subject->getCollection()->getSelect()->reset(\Zend_Db_Select::ORDER)->order(['is_salable DESC', 'position DESC']);
        }
        elseif ($this->getCurrentCategory() && !$this->productPositionModel->isEnabledSortByRecommendPosition($this->getCurrentCategory())
            && $subject->getCurrentOrder() === 'position'){
            $subject->getCollection()->getSelect()->reset(\Zend_Db_Select::ORDER)->order(['is_salable DESC','position ASC']);
        }
        return $subject;
    }

    /**
     * @return mixed|null
     */
    private function getCurrentCategory()
    {
        return $this->coreRegistry->registry('current_category');
    }
}
