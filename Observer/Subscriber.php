<?php
namespace Superb\Recommend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber as SubscriberMagento;
use Magento\Store\Model\Store;

/**
 * Class Subscriber
 * @package Superb\Recommend\Observer
 */
class Subscriber implements ObserverInterface
{
    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    public function __construct(
        \Superb\Recommend\Helper\Api $apiHelper
    ) {
        $this->_apiHelper = $apiHelper;
    }

    /**
     * @param Observer $observer
     *
     * @return $this|void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $item = $observer->getEvent()->getSubscriber();
        $subscriberStatus = $item->getSubscriberStatus();

        if ($subscriberStatus=='1') {
            $status = 'subscribed';
        } elseif ($subscriberStatus=='3') {
            $status = 'unsubscribed';
        } else {
            $status = 'non_subscribed';
        }
        $channelData = [];
        $channelData[0] = [
            'action' => 'upsert_update',
            'data' => [
                'event_time' => strtotime($item->getChangeStatusAt()),
                'identifier' => $item->getSubscriberEmail(),
                'subscription_status' => $status,
                'subscription_status_change_date' => strtotime($item->getChangeStatusAt())
            ]
        ];
        $this->_apiHelper->sendChennelData($channelData);

        if ($item->getCustomerId()==0) {
            $customerData = [];
            $customerData[] = [
                'action' => 'upsert_update',
                'data' => [
                    'email' => $item->getSubscriberEmail()
                ]
            ];
            $this->_apiHelper->sendCustomer($customerData);
        }
        
        return $this;
    }
}
