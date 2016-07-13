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

namespace Superb\Recommend\Block\System\Config\Form\Field;

class Advanced extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        parent::__construct(
            $context,
            $data
        );
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_scopeConfig->getValue(\Superb\Recommend\Helper\Data::XML_PATH_ADVANCED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)?parent::render($element):'';
    }
}
