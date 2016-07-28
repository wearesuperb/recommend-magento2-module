<?php
namespace Superb\Recommend\Observer;

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

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SetOnOrderSuccessPageViewObserver implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Superb\Recommend\Helper\Data $helper
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_helper->isEnabled()) {
            return $this;
        }
        $orderIds = $observer->getEvent()->getOrderIds();
        try {
            $ordersData = $this->_helper->getOrdersData($orderIds);
            foreach ($ordersData as $orderData) {
                $this->_helper->setTrackingData($orderData);
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        return $this;
    }
}
