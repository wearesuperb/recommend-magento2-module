<?php
namespace Superb\Recommend\Cron;

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
 * @copyright  Copyright (c) 2017 Superb Media Limited
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class UploadOrdersStatus
{
    public function __construct(
        \Superb\Recommend\Logger\Logger $logger,
        \Superb\Recommend\Helper\Api $apiHelper,
        \Superb\Recommend\Model\OrdersQueue $ordersQueue
    ) {
        $this->_logger = $logger;
        $this->_apiHelper = $apiHelper;
        $this->_ordersQueue = $ordersQueue;
    }

    public function execute(\Magento\Cron\Model\Schedule $schedule) {
        try {
            $ordersQueueCollection = $this->_ordersQueue->getCollection()->setOrder('id', 'ASC');

            foreach ($ordersQueueCollection->getItems() as $order) {
                $orderData = [];
                $orderData['cid']           = $order->getData('cid');
                $orderData['bid']           = $order->getData('bid');
                $orderData['email']         = $order->getData('email');
                $orderData['customer_name'] = $order->getData('customer_name');
                $orderData['order_id']      = $order->getData('order_id');
                $orderData['status']        = $order->getData('status');
                $orderData['store_id']      = $order->getData('store_id');
                $orderData['grand_total']   = $order->getData('grand_total');
                $orderData['tax']           = $order->getData('tax');
                $orderData['delivery']      = $order->getData('delivery');
                $orderData['currency']      = $order->getData('currency');
                $orderData['products']      = $order->getData('products');
                $orderData['sale_qty']      = $order->getData('sale_qty');
                $orderData['created_at']    = $order->getData('created_at');
                if ($order->getData('updated_at')) {
                    $orderData['updated_at']    = $order->getData('updated_at');
                }

                $response = $this->_apiHelper->uploadOrderData($orderData, $orderData['store_id']);
                if (isset($response['success']) && $response['success'] == false || !isset($response['success'])) {
                    $fromTime = $order->getData('updated_at') ? $order->getData('updated_at') : $orderData['created_at'];
                    if ($fromTime  + 604800 < time()) {
                        $order->delete();
                    } else {
                        $this->_logger->warning("Unable to send order (" . $order->getData('order_id') . ") via API." . json_encode($response['error_message']));
                    }
                } else {
                    $order->delete();
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}
