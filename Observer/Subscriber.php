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

    /**
     * @var \Superb\Recommend\Model\SessionFactory
     */
    protected $recommendSession;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $helper;

    protected $storeManager;

    public function __construct(
        \Superb\Recommend\Helper\Api $apiHelper,
        \Superb\Recommend\Model\SessionFactory $recommendSession,
        \Superb\Recommend\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_apiHelper = $apiHelper;
        $this->recommendSession = $recommendSession;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     *
     * @return $this|void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var  $item */
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

        if ($item->getCustomerId()==0) {
            $customerData = [];
            $customerData[] = [
                'action' => 'upsert_update',
                'data' => [
                    'email' => $item->getSubscriberEmail()
                ]
            ];
            $this->_apiHelper->sendCustomer($customerData,$this->storeManager->getWebsite()->getCode());
        }
        $this->_apiHelper->sendChennelData($channelData,$this->storeManager->getWebsite()->getCode());
        $this->recommendSession->create()->setAddSubscribe(
            [
                'email_hash' => hash_hmac('sha256', $item->getSubscriberEmail(), $this->helper->getHashSecretKey($this->storeManager->getStore()->getId()))
            ]
        );

        return $this;
    }
}
