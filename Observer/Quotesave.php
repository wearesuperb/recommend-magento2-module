<?php
namespace Superb\Recommend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Superb\Recommend\Helper\Api;

class Quotesave implements ObserverInterface
{
    /**
     * @var \Superb\Recommend\Model\SessionFactory
     */
    protected $recommendSession;

    /**
     * @var \Bss\FacebookPixel\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $api;

    /**
     * AddToCart constructor.
     * @param \Superb\Recommend\Model\SessionFactory $recommendSession
     * @param \Superb\Recommend\Helper\Data $helper
     * @param \Superb\Recommend\Helper\Api $api
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Superb\Recommend\Model\SessionFactory $recommendSession,
        \Superb\Recommend\Helper\Data $helper,
        \Superb\Recommend\Helper\Api $api,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->recommendSession = $recommendSession;
        $this->helper        = $helper;
        $this->api           = $api;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->encryptor = $encryptor;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $quote = $observer->getEvent()->getQuote();
        $this->api->uploadCart($quote);


        return true;
    }
}
