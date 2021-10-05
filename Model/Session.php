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
namespace Superb\Recommend\Model;

/**
 * Recommend session model
 */
class Session extends \Magento\Framework\Session\SessionManager
{
    /**
     * @param array $data
     * @return \Superb\Recommend\Model\Session $this
     */
    public function setAddToCart($data)
    {
        $this->setData('add_to_cart', $data);
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getAddToCart()
    {
        if ($this->hasAddToCart()) {
            $data = $this->getData('add_to_cart');
            $this->unsetData('add_to_cart');
            return $data;
        }
        return null;
    }

    /**
     * @return bool
     */
    public function hasAddToCart()
    {
        return $this->hasData('add_to_cart');
    }
}
