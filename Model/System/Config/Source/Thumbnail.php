<?php
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

namespace Superb\Recommend\Model\System\Config\Source;

class Thumbnail extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected $_options;

    /**
     * @var \Magento\Eav\Model\Entity\TypeFactory
     */
    protected $eavEntityTypeFactory;

    public function __construct(
        \Magento\Eav\Model\Entity\TypeFactory $eavEntityTypeFactory
    ) {
        $this->eavEntityTypeFactory = $eavEntityTypeFactory;
    }

    public function getAllOptions(){
        if (is_null($this->_options)){
            $type = $this->eavEntityTypeFactory->create()->loadByCode('catalog_product');

            $attributes = $type->getAttributeCollection()->addStoreLabel(0);

            $this->_options = array(array(
                'value' => '',
                'label' => '',
            ));
            foreach ($attributes as $attribute){
                if ($attribute->getStoreLabel() && $attribute->getFrontend()->getInputType()=='media_image'){
                    $this->_options[] = array(
                        'value' => $attribute->getAttributeCode(),
                        'label' => $attribute->getStoreLabel()
                    );
                }
            }
            sort($this->_options);
        }
        return $this->_options;
    }
}