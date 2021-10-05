<?php
namespace Superb\Recommend\Block\Adminhtml\System\Config\Form\FieldArray;

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

abstract class AbstractAttributes extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected $_elementFactory;
    protected $_recommendAttributeFactory;
    protected $_magentoAttributeFactory;

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        //$this->addColumn('recommend_attribute', ['label' => __('Recommend attribute')]);
        $this->addColumn('magento_attribute', ['label' => __('Magento attribute')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add attribute');
        parent::_construct();
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        if (in_array($columnName, ['recommend_attribute','magento_attribute']) && isset($this->_columns[$columnName])) {
            if ($columnName=='recommend_attribute') {
                $attribute = $this->_recommendAttributeFactory->create();
            } elseif ($columnName=='magento_attribute') {
                $attribute = $this->_magentoAttributeFactory->create();
            }
            $options = $attribute->getAllOptions();
            $element = $this->_elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $options
            );
            return str_replace("\n", '', addcslashes($element->getElementHtml(), "'"));
        }

        return parent::renderCellTemplate($columnName);
    }
}
