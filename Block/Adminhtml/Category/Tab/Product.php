<?php
namespace Superb\Recommend\Block\Adminhtml\Category\Tab;

class Product extends \Magento\Catalog\Block\Adminhtml\Category\Tab\Product
{
    /**
     * Set collection object
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @return void
     */
    public function setCollection($collection)
    {
        $collection->addAttributeToSelect('created_at');
        parent::setCollection($collection);
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->addColumnAfter('recommend', array(
            'header' => __('Recommend Position'),
            'index' => 'created_at',
        ), 'position');

        $this->sortColumnsByOrder();
        return $this;
    }
}