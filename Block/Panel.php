<?php
namespace Superb\Recommend\Block;

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

class Panel extends \Magento\Framework\View\Element\Template
{
    protected $_panelId = null;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    public function __construct(
        Template\Context $context,
        \Superb\Recommend\Helper\Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        parent::__construct(
            $context,
            $data
        );
        $this->setTemplate('recommend_tracker/panel.phtml');
    }

    public function setRecommendPanelId($panelId)
    {
        $this->_panelId = $panelId;
        return $this;
    }

    public function getRecommendPanelId()
    {
        return $this->_panelId;
    }

    /**
     * Check whether the block can be displayed
     *
     * @return bool
     */
    public function canDisplay()
    {
        return $this->_helper->isEnabled();
    }

    /**
     * Output content, if allowed
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->canDisplay()) {
            return '';
        }
        return parent::_toHtml();
    }
}
