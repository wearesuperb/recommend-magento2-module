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
 * @copyright  Copyright (c) 2019 Superb Media Limited
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
     * @var \Superb\Recommend\Helper\Tracker
     */
    protected $_trackerHelper;

    /**
     * @var \Superb\Recommend\Helper\Cache
     */
    protected $_cacheHelper;

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

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Superb\Recommend\Helper\Data $helper,
        \Superb\Recommend\Helper\Tracker $trackerHelper,
        \Superb\Recommend\Helper\Cache $cacheHelper,
        ModuleManager $moduleManager,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutCart = $checkoutCart;
        $this->_catalogLayer = $layerResolver->get();
        $this->_layout = $context->getLayout();
        $this->_helper = $helper;
        $this->_trackerHelper = $trackerHelper;
        $this->_cacheHelper = $cacheHelper;
        $this->_request = $context->getRequest();
        $this->_moduleManager = $moduleManager;
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
        $data = $this->_trackerHelper->getTrackingData();
        if ($this->_customerSession->isLoggedIn()) {
            $data = is_array($data)?$data:[];
            array_unshift($data, ["setCustomerId",$this->_customerSession->getCustomerId()]);
        }
        return $data;
    }

    public function checkLayerPage()
    {
        if ($this->_catalogLayer!==false && count($this->_catalogLayer->getState()->getFilters())) {
            $this->_trackerHelper->setTrackingData(['disableRecommendationPanels'], true);
        }
    }

    public function getTrackLoadUrl()
    {
        return $this->getUrl('superbrecommend/track/load');
    }

    public function getTrackCookieName()
    {
        return \Superb\Recommend\Helper\Tracker::COOKIE_RECOMMENDTRACKER;
    }

    /**
     * Check whether the block can be displayed
     *
     * @return bool
     */
    public function canDisplay()
    {
        return $this->_helper->isEnabled() && (!$this->_cacheHelper->isFullPageCacheEnabled() || $this->_cacheHelper->isVarnishEnabled() ||
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
