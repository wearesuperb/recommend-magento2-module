<?php
namespace Superb\Recommend\Block\Adminhtml\System\Config\Form\FieldArray\Product;

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

class Attributes extends \Superb\Recommend\Block\Adminhtml\System\Config\Form\FieldArray\AbstractAttributes
{

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @var \Superb\Recommend\Model\System\Config\Source\Product\AttributeFactory
     */
    protected $_recommendAttributeFactory;

    /**
     * @var \Superb\Recommend\Model\System\Config\Source\Product\Recommend\AttributeFactory
     */
    protected $_magentoAttributeFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Superb\Recommend\Model\System\Config\Source\Product\AttributeFactory $magentoAttributeFactory
     * @param \Superb\Recommend\Model\System\Config\Source\Product\Recommend\AttributeFactory $recommendAttributeFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Superb\Recommend\Model\System\Config\Source\Product\AttributeFactory $magentoProductAttributeFactory,
        \Superb\Recommend\Model\System\Config\Source\Product\Recommend\AttributeFactory $recommendProductAttributeFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        $this->_magentoAttributeFactory = $magentoProductAttributeFactory;
        $this->_recommendAttributeFactory = $recommendProductAttributeFactory;
        parent::__construct($context, $data);
    }
}
