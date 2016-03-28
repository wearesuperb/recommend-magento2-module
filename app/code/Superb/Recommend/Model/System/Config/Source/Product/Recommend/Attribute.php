<?php
namespace Superb\Recommend\Model\System\Config\Source\Product\Recommend;

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

class Attribute extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected $_options;

    /**
     * @var \Superb\Recommend\Helper\Admin
     */
    protected $_adminHelper;

    public function __construct(
        \Superb\Recommend\Helper\Admin $adminHelper
    ) {
        $this->_adminHelper = $adminHelper;
    }

    public function getAllOptions(){
        if (is_null($this->_options)){
            $storeId = $this->_adminHelper->getSystemConfigStoreId();
            $productAttributesData = $this->_adminHelper->getProductAttributesListData($storeId);
            $this->_options[] = array('value'=>'','label'=>'');
            if (is_array($productAttributesData))
            {
                foreach($productAttributesData as $productAttributeData)
                {
                    $this->_options[] = array('value'=>$productAttributeData['code'],'label'=>$productAttributeData['title']);
                }
            }
        }
        return $this->_options;
    }
}
