<?php
namespace Superb\Recommend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Superb\Recommend\Helper\Api;

class AddToCart implements ObserverInterface
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

        
        $typeConfi = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
        $quoteId = (int)$this->checkoutSession->getQuote()->getId();
        $this->api->uploadCart($this->checkoutSession->getQuote());
        $product = [
            'content_ids' => [],
            'value' => 0,
	    'contents' => [],
            'currency' => ""
        ];
	$items = $this->checkoutSession->getQuote()->getAllItems();
	$sessionItem = 0;
	$quoteItem = 0;
	if($this->checkoutSession->getLastAddedProductId()){
	    $sessionItem = $this->checkoutSession->getLastAddedProductId();
	}
	if($this->checkoutSession->getQuote()->getLastAddedItem()){
	    $quoteItem = $this->checkoutSession->getQuote()->getLastAddedItem()->getId();
	}
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($items as $item) {

if ($item->getParentItem()&&$item->getParentItem()->getProduct()->getId()==$sessionItem) {
			$product['contents'][] = [
                    	    'id' => $item->getParentItem()->getProduct()->getData('sku'),
                    	    'name' => $item->getName(),
                    	    'quantity' => $item->getData('qty'),
                    	    'variation' => $item->getSku()
                	];

}
	    if($item->getProduct()->getId()){
        	if ($item->getProduct()->getTypeId() == $typeConfi) {
            	    continue;
        	}

        	if ($item->getParentItem()) {
            	    if ($item->getParentItem()->getProductType() == $typeConfi) {
                	$product['contents'][] = [
                    	    'id' => $item->getParentItem()->getProduct()->getData('sku'),
                    	    'name' => $item->getName(),
                    	    'quantity' => $item->getParentItem()->getQtyToAdd(),
                    	    'variation' => $item->getSku()
                	];
                	$product['value'] += $item->getProduct()->getFinalPrice() * $item->getParentItem()->getQtyToAdd();
            	    } else {
                	$product['contents'][] = [
                    	    'id' => $item->getParentItem()->getProduct()->getData('sku'),
                    	    'name' => $item->getName(),
                    	    'quantity' => $item->getData('qty'),
                    	    'variation' => $item->getSku()
                	];
            	    }
        	} else {
            	    $product['contents'][] = [
                	'id' => $this->checkBundleSku($item),
                	'name' => $item->getName(),
                	'quantity' => $item->getQtyToAdd()
            	    ];
            	    $product['value'] += $item->getProduct()->getFinalPrice() * $item->getQtyToAdd();
        	}
        	$product['content_ids'][] = $this->checkBundleSku($item);
	    }
        }

        $data = [
            'quote_id' => hash_hmac('sha256', $quoteId, $this->helper->getHashSecretKey($this->helper->getCurrentStore()->getId())),
	    'quote' => $quoteId,
            'content_ids' => $product['content_ids'],
            'contents' => $product['contents'],
            'currency' => $this->helper->getCurrencyCode(),
            'value' => $product['value']
        ];

        $this->recommendSession->create()->setAddToCart($data);

        return true;
    }

    /**
     * @param mixed $item
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function checkBundleSku($item)
    {
        $typeBundle = \Magento\Bundle\Model\Product\Type::TYPE_CODE;
        if ($item->getProductType() == $typeBundle) {
            $skuBundleProduct= $this->productRepository->getById($item->getProductId())->getSku();
            return $skuBundleProduct;
        }
        return $item->getProduct()->getSku();
    }
}
