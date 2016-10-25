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
namespace Superb\Recommend\Helper;

class Cache extends \Magento\Framework\App\Helper\AbstractHelper
{

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

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\PageCache\Model\Config $pageCacheConfig
    ) {
        $this->_pageCacheConfig = $pageCacheConfig;
        parent::__construct($context);
    }

    /**
     * Is full page cache enabled
     *
     * @return bool
     */
    public function isFullPageCacheEnabled()
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
    public function isVarnishEnabled()
    {
        if ($this->isVarnishEnabled === null) {
            $this->isVarnishEnabled = ($this->_pageCacheConfig->getType() == \Magento\PageCache\Model\Config::VARNISH);
        }
        return $this->isVarnishEnabled;
    }
}
