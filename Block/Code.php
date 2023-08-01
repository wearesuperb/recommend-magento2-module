<?php
namespace Superb\Recommend\Block;

/**
 * Class Code
 * @package Superb\Recommend\Block
 */
class Code extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Checkout\Model\SessionFactory
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var array
     */
    protected $skusInCondition = [];

    /**
     * Code constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Superb\Recommend\Helper\Data $helper,
     * @param \Magento\Framework\Registry $coreRegistry,
     * @param \Magento\Checkout\Model\SessionFactory $checkoutSession,
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Superb\Recommend\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Checkout\Model\SessionFactory $checkoutSession,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        array $data = []
    ) {
        $this->storeManager  = $context->getStoreManager();
        $this->helper        = $helper;
        $this->coreRegistry = $coreRegistry;
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function accountId()
    {
        return $this->helper->getAccountId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function actionName()
    {
        return $this->getRequest()->getFullActionName();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function currentURL()
    {
        return $this->storeManager->getStore()->getCurrentUrl(false);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function productSku()
    {
        $currentProduct = $this->coreRegistry->registry('current_product');

        if($currentProduct){
            return $currentProduct->getSku();
        }
        return '';
    }

    public function categoryId()
    {
        $currentCategory = $this->coreRegistry->registry('current_category');

        if($currentCategory){
            return $currentCategory->getId();
        }
        return '';
    }

    public function getWebsiteCode()
    {
        return $this->helper->getWebsiteCode();
    }

    public function getCurrencyCode()
    {
        return $this->helper->getCurrencyCode();
    }

    public function getCurrencySymbol()
    {
        return $this->helper->getCurrencySymbol();
    }

    public function getEnvironment()
    {
        return $this->storeManager->getStore()->getCode();
    }

    public function getOrderId()
    {
        $orderId = '00100';
        $action = $this->actionName();
        if ($action == 'checkout_onepage_success'
            || $action == 'onepagecheckout_index_success'
            || $action == 'multishipping_checkout_success') {
            $order = $this->checkoutSession->create()->getLastRealOrder();
            $orderId = hash_hmac('sha256', $order->getIncrementId(), $this->helper->getHashSecretKey($this->helper->getCurrentStore()->getId()));
        }
        return $orderId;
    }

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getDescription()
    {
        return $this->getData('description');
    }

    public function getPanelType()
    {
        return $this->getData('paneltype');
    }

    public function getPriceList()
    {
        return 'default';
    }

    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }

    public function getSkusFromBasketPage()
    {
        $data = [];
        $checkout = $this->checkoutSession->create();
        if (empty($checkout->getQuote()->getAllVisibleItems())) {
            return json_encode($data);
        }

        $items = $checkout->getQuote()->getAllVisibleItems();

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $item) {
            $parentItem = $item->getParentItem();
            if (count($data) >= 5) {
                continue;
            }
            $data[] = $parentItem ? $parentItem->getSku() : $item->getSku();
        }
        return json_encode($data);
    }

    /**
     * @return string
     */
    public function getPanelid(): string
    {
        if (!$this->isSetCondition()) {
            return $this->getData('panelid');
        }

        $skusFromCart = json_decode($this->getSkusFromBasketPage());
        foreach ($this->skusInCondition as $sku) {
            if (in_array($sku, $skusFromCart)) {
                if ($this->getData('special_panelid') !== null) {
                    return $this->getData('special_panelid');
                }
            }
        }
        return $this->getData('panelid');
    }

    public function isSetCondition(): bool
    {
        if ($this->getData('condition_products_skus') !== null && trim($this->getData('condition_products_skus')) === "") {
            return false;
        }

        $skusString = $this->getData('condition_products_skus') ?? '';
        $skusArray = explode(',', $skusString);
        $existSku = [];
        foreach ($skusArray as $sku) {
            $sku = trim($sku);
            if ($sku === "") {
                continue;
            }
            try {
                $product = $this->productRepository->get($sku, false, $this->_storeManager->getStore()->getId());
                if ($product->getId()) {
                    $existSku[] = $sku;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            }
        }

        if (!count($existSku)) {
            return false;
        }

        $this->skusInCondition = $existSku;
        return true;
    }

    public function getSpecialProductsSkus(): string
    {
        $specialSkus = [];
        if ($this->getData('special_products_skus') !== null && trim($this->getData('special_products_skus')) === "") {
            return json_encode($specialSkus);
        }
        $skusArray = [];
        $tempSkusArray = explode(',', $this->getData('special_products_skus') ?? '');
        foreach ($tempSkusArray as $sku) {
            $sku = trim($sku);
            if ($sku === "") {
                continue;
            }
            $skusArray[] = $sku;
        }
        return json_encode($skusArray);
    }

    public function getConditionProductsSkus(): string
    {
        if ($this->isSetCondition()) {
            return json_encode($this->skusInCondition);
        }
        return json_encode([]);
    }
}
