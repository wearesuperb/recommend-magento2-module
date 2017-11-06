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

use Magento\Framework\Event\ObserverInterface;

class OrderUpdate implements ObserverInterface
{
    /**
     * @var \Superb\Recommend\Helper\Admin
     */
    protected $_adminHelper;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Superb\Recommend\Model\OrdersQueue
     */
    protected $_ordersQueue;

    public function __construct(
        \Superb\Recommend\Helper\Admin $adminHelper,
        \Superb\Recommend\Helper\Api $apiHelper,
        \Superb\Recommend\Model\OrdersQueue $ordersQueue
    ) {
        $this->_adminHelper = $adminHelper;
        $this->_apiHelper   = $apiHelper;
        $this->_ordersQueue = $ordersQueue;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order instanceof \Magento\Framework\Model\AbstractModel) {
            $orderQueueModel = $this->_ordersQueue;
            $orderQueueModel->addData([
                'cid'       => $order->getCustomerId(),
                'email'     => $order->getCustomerEmail(),
                'order_id'  => $order->getIncrementId(),
                'status'    => $order->getStatus(),
                'store_id'  => $order->getStoreId()
            ]);
            $orderQueueModel->save();
        }
        return $this;
    }
}
