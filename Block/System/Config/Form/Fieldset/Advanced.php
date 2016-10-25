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

namespace Superb\Recommend\Block\System\Config\Form\Fieldset;

class Advanced extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Superb\Recommend\Helper\Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        parent::__construct(
            $context,
            $authSession,
            $jsHelper,
            $data
        );
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_helper->getIsAdvancedModeEnabled()?parent::render($element):'';
    }
}
