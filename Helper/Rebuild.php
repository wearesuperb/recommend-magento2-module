<?php
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
namespace Superb\Recommend\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Rebuild extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $checkoutCart;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $productRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Url
     */
    protected $_catalogUrl;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Checkout\Model\Cart $checkoutCart,
        ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->encryptor = $encryptor;
        $this->checkoutCart = $checkoutCart;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->_catalogUrl = $catalogUrl;
        parent::__construct($context);
    }

    /**
     * Base64 url encode
     *
     * @param string $data
     * @return string
     */
    public function base64UrlEncode($data)
    {
        return strtr(base64_encode($this->encryptor->encrypt(base64_encode($data))), '+/=', '-_,');
    }

    /**
     * Base64 url dencode
     *
     * @param string $data
     * @return string
     */
    public function base64UrlDecode($data)
    {
        return base64_decode($this->encryptor->decrypt(base64_decode(strtr($data, '-_,', '+/='))));
    }
    
    public function getTrackingMessageParamName()
    {
        return 'recommend-message';
    }

    public function getProduct($buyRequest)
    {
        if ($buyRequest instanceof \Magento\Framework\DataObject) {
            if (!$buyRequest->getProduct()) {
                return false;
            }

            $storeId = $this->storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($buyRequest->getProduct(), false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    public function rebuildCart($data)
    {
        $cart = $this->checkoutCart;

        if ((int)$cart->getItemsCount())
            return ;
        $cartUpdated = false;
        foreach($data as $buyRequest) {
            try {
                $product = $this->getProduct($buyRequest);
                $storeId = $this->storeManager->getStore()->getId();

                if ($product->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    continue;
                }

                if (!$product->isVisibleInSiteVisibility()) {
                    if ($product->getStoreId() == $storeId) {
                        continue;
                    }
                    $urlData = $this->_catalogUrl->getRewriteByProductStore([$product->getId() => $storeId]);
                    if (!isset($urlData[$product->getId()])) {
                        continue;
                    }
                    $product->setUrlDataObject(new \Magento\Framework\DataObject($urlData));
                    $visibility = $product->getUrlDataObject()->getVisibility();
                    if (!in_array($visibility, $product->getVisibleInSiteVisibilities())) {
                        continue;
                    }
                }

                if ($product->isSalable()) {
                    $cart->addProduct($product, $buyRequest);
                    if (!$product->isVisibleInSiteVisibility()) {
                        $cart->getQuote()->getItemByProduct($product)->setStoreId($storeId);
                    }
                    $cartUpdated = true;
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
        if ($cartUpdated) {
            try {
                $cart->save()->getQuote()->collectTotals();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
    }
}
