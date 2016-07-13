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

namespace Superb\Recommend\Model\System\Config\Source\Customer\Recommend;

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
            $customerAttributesData = $this->_adminHelper->getCustomerAttributesListData($storeId);
            $this->_options[] = array('value'=>'','label'=>'');
            if (is_array($customerAttributesData))
            {
                foreach($customerAttributesData as $customerAttributeData)
                {
                    $this->_options[] = array('value'=>$customerAttributeData['code'],'label'=>$customerAttributeData['title']);
                }
            }
        }
        return $this->_options;
    }
}