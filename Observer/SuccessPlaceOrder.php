<?php

namespace Superb\Recommend\Observer;

use Magento\Framework\Event\Observer;


class SuccessPlaceOrder implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Superb\Recommend\Model\SessionFactory
     */
    protected $recommendSession;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $subscriber;

    /**
     * @var \Superb\Recommend\Logger\Logger
     */
    protected $logger;

    /**
     * @param \Superb\Recommend\Model\SessionFactory $recommendSession
     * @param \Superb\Recommend\Helper\Data $helperData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Superb\Recommend\Logger\Logger $logger
     */
    public function __construct(
        \Superb\Recommend\Model\SessionFactory $recommendSession,
        \Superb\Recommend\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Superb\Recommend\Logger\Logger $logger
    ) {
        $this->recommendSession = $recommendSession;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->subscriber = $subscriber;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        if (isset($order)) {
            try {
                if (!$this->recommendSession->create()->hasAddSubscribe()) {
                    /** @var \Magento\Sales\Model\Order $order */
                    $quoteId = $order->getQuoteId();
                    $quote = $this->quoteFactory->create()->load($quoteId);
                    $email = $order->getCustomerEmail();
                    if ($quote->getNewsletterSubscribe()) {
                        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
                        $subscriber = $this->subscriber->loadByEmail($email);
                        if ($subscriber->getId() && $subscriber->getSubscriberStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                            $this->recommendSession->create()->setAddSubscribe(
                                [
                                    'email_hash' => hash_hmac('sha256', $order->getCustomerEmail(), $this->helperData->getHashSecretKey($this->storeManager->getStore()->getId()))
                                ]
                            );
                        }
                    }
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException|\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $this->logger->critical($exception->getTraceAsString());
            }
        }
    }
}
