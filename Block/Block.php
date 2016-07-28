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
use Magento\Framework\Module\Manager as ModuleManager;

class Block extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_checkoutCart;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * Is varnish enabled flag
     *
     * @var bool
     */
    protected $isVarnishEnabled;

    /**
     * Is full page cache enabled flag
     *
     * @var bool
     */
    protected $isFullPageCacheEnabled;

    /**
     * Application config object
     *
     * @var \Magento\PageCache\Model\Config
     */
    protected $_pageCacheConfig;

    protected $_isScopePrivate = true;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Superb\Recommend\Helper\Data $helper,
        ModuleManager $moduleManager,
        \Magento\PageCache\Model\Config $pageCacheConfig,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutCart = $checkoutCart;
        $this->_catalogLayer = $layerResolver->get();
        $this->_layout = $context->getLayout();
        $this->_helper = $helper;
        $this->_request = $context->getRequest();
        $this->_moduleManager = $moduleManager;
        $this->_pageCacheConfig = $pageCacheConfig;
        $this->_isScopePrivate = $this->isFullPageCacheEnabled() && !$this->isVarnishEnabled();
        parent::__construct(
            $context,
            $data
        );
    }
    /**
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('recommend_tracker/block.phtml');
    }

    public function getTrackingData()
    {
        $data = $this->_helper->getTrackingData();
        if ($this->_customerSession->isLoggedIn()) {
            $data = is_array($data)?$data:[];
            array_unshift($data, ["setCustomerId",$this->_customerSession->getCustomerId()]);
        }
        return $data;
    }

    public function checkLayerPage()
    {
        if ($this->_catalogLayer!==false && count($this->_catalogLayer->getState()->getFilters())) {
            $this->_helper->setTrackingData(['disableRecommendationPanels'], true);
        }
    }

    /**
     * Is full page cache enabled
     *
     * @return bool
     */
    protected function isFullPageCacheEnabled()
    {
        if ($this->isFullPageCacheEnabled === null) {
            $this->isFullPageCacheEnabled = $this->_pageCacheConfig->isEnabled();
        }
        return $this->isFullPageCacheEnabled;
    }

    /**
     * Is varnish cache engine enabled
     *
     * @return bool
     */
    protected function isVarnishEnabled()
    {
        if ($this->isVarnishEnabled === null) {
            $this->isVarnishEnabled = ($this->_pageCacheConfig->getType() == \Magento\PageCache\Model\Config::VARNISH);
        }
        return $this->isVarnishEnabled;
    }

    /**
     * Check whether the block can be displayed
     *
     * @return bool
     */
    public function canDisplay()
    {
        return $this->_helper->isEnabled() && (!$this->isFullPageCacheEnabled() || $this->isVarnishEnabled() ||
            !(
                $this->_moduleManager->isEnabled('Magento_PageCache')
                && !$this->_request->isAjax()
                && $this->_layout->isCacheable()
            )
        );
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
