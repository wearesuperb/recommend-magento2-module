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
                'cid'           => $order->getCustomerId(),
                'bid'           => null,
                'email'         => $order->getCustomerEmail(),
                'customer_name' => $order->getCustomerName(),
                'order_id'      => $order->getIncrementId(),
                'status'        => $order->getStatus(),
                'store_id'      => $order->getStoreId(),
                'grand_total'   => $order->getBaseGrandTotal(),
                'tax'           => $order->getBaseTaxAmount(),
                'delivery'      => $order->getBaseShippingAmount(),
                'currency'      => $order->getBaseCurrencyCode(),
            ]);
            $_qtyOrdered = 0;
            $products = [];
            $_items = $order->getAllVisibleItems();
            foreach ($_items as $_item) {
                if ($_item->hasParentItem()) {
                    continue;
                }
                $_qtyOrdered += $_item->getQtyOrdered();
                $itemData = [];
                $itemData['name']  = $this->_normalizeName($_item->getName());
                $itemData['sku']   = $_item->getProduct()->getSku();
                $itemData['qty']   = $_item->getQtyOrdered();
                $itemData['val']   = sprintf('%.2f', $_item->getBasePriceInclTax());
                $products[] = $itemData;
            }
            $orderQueueModel->addData([
                'products' => json_encode($products),
                'sale_qty' => $_qtyOrdered
            ]);
            $orderQueueModel->save();
        }
        return $this;
    }

    protected function _normalizeName($name)
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }
}
