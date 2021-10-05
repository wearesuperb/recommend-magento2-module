<?php
namespace Superb\Recommend\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AfterCustomer
 * @package Superb\Recommend\Observer
 */
class AfterCustomer implements ObserverInterface
{
    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Superb\Recommend\Helper\Api $apiHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_apiHelper = $apiHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     *
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $item = $observer->getDataObject();
        $website = $this->_storeManager->getWebsite($this->_storeManager->getStore()->getWebsiteId());

        $customerAttributes = $this->_apiHelper->getCustomerAttributes($website->getCode());
        $attributes = [];
        foreach($customerAttributes as $attribute){
            $attributes[] = [
                'code' => (string)$attribute['magento_attribute'],
                'value' => (string)$item->getData($attribute['magento_attribute'])
            ];
        }

        $userData = [];
        $userData[] = [
            'action' => 'upsert_update',
            'data' => [
                'customer_id' => $item->getEntityId(),
                'email' => $item->getEmail(),
                'store_code' => $website->getCode(),
                'currency' => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
                'environment' => $this->_storeManager->getStore()->getCode(),
                'price_list' => 'default',
                'register_date' => strtotime($item->getCreatedAt()),
                'first_name' => $item->getFirstname(),
                'last_name' => $item->getLastname(),
                'attributes' => $attributes,
                'event_time' => strtotime($item->getUpdatedAt())
            ]
        ];

        $this->_apiHelper->sendCustomer($userData,$website->getCode());
        return $this;
    }
}