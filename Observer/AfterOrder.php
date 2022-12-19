<?php
namespace Superb\Recommend\Observer;
use Magento\Framework\Event\ObserverInterface;
/**
 * Class AfterOrder
 * @package Superb\Recommend\Observer
 */
class AfterOrder implements ObserverInterface
{
    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_helperApi;

    /**
     * AfterOrder constructor.
     * @param \Superb\Recommend\Helper\Api $api
     */
    public function __construct(
        \Superb\Recommend\Helper\Api $api
    ) {
        $this->_helperApi           = $api;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $data = [];
        $items = $order->getAllVisibleItems();
        $_items = [];

        foreach ($items as $item) {
            $productOptions = $item->getProductOptions();

            $_items[$item->getSku()] = [
                'sku' => $item->getParentItem()?$item->getParentItem()->getProduct()->getData('sku'):$item->getSku(),
                'name' => $item->getName(),
                'variation_sku' => isset($productOptions['simple_sku'])?$productOptions['simple_sku']:'',
                'quantity' => (int)$item->getQtyOrdered(),
                'cost' => (float)$item->getPrice(),
                'base_cost' => (float)$item->getBasePrice(),
                'discount' => (float)$item->getDiscountAmount(),
                'base_discount' => (float)$item->getBaseDiscountAmount()
            ];
        }
        foreach($_items as $key=>$val){
            $products[] = [
                'sku' => $val['sku'],
                'name' => $val['name'],
                'variation_sku' => $key,
                'quantity' => $val['quantity'],
                'cost' => $val['cost'],
                'base_cost' => $val['cost'],
                'discount' => $val['discount'],
                'base_discount' => $val['base_discount']
            ];
        }

        $data[] = [
            'action' => 'upsert_update',
            'data' => [
                'order_id' => $order->getIncrementId(),
                'status' => $order->getStatus(),
                'customer_id' => $order->getCustomerId()?(string)$order->getCustomerId():null,
                'email' => $order->getCustomerEmail(),
                'full_name' => $order->getCustomerFirstname().' '.$order->getCustomerLastname(),
                'currency' => $order->getOrderCurrencyCode(),
                'base_currency' => $order->getGlobalCurrencyCode(),
                'total' => (float)$order->getGrandTotal(),
                'base_total' => (float)$order->getBaseGrandTotal(),
                'discount' => (float)$order->getDiscountAmount(),
                'base_discount' => (float)$order->getBaseDiscountAmount(),
                'tax_cost' => (float)$order->getTaxAmount(),
                'base_tax_cost' => (float)$order->getBaseTaxAmount(),
                'delivery_cost' => (float)$order->getShippingAmount(),
                'base_delivery_cost' => (float)$order->getBaseShippingAmount(),
                'items' => $products
            ]
        ];

        $this->_helperApi->syncOrders($data);

    }
}