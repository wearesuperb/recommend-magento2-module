<?php
namespace Superb\Recommend\Model\Config\Source;

class Paneltype implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_helperApi;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Superb\Recommend\Helper\Api $helperApi,
        \Superb\Recommend\Helper\Data $helper
    ) {
        $this->helperApi = $helperApi;
        $this->helper = $helper;
    }

    public function toOptionArray()
    {
        $options = [
            [
                'value' => 'product',
                'label' => 'Product Detail'
            ],
            [
                'value' => 'category',
                'label' => 'Category'
            ],
            [
                'value' => 'cms',
                'label' => 'CMS'
            ],
            [
                'value' => 'search',
                'label' => 'Search'
            ],
            [
                'value' => 'basket',
                'label' => 'Basket'
            ]
        ];
        return $options;
    }
}
