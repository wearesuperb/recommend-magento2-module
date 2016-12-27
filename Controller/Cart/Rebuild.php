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
namespace Superb\Recommend\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;

class Rebuild extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Superb\Recommend\Helper\Rebuild
     */
    protected $rebuildHelper;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $cartHelper;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        \Superb\Recommend\Helper\Rebuild $rebuildHelper,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Superb\Recommend\Helper\Api $apiHelper,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        parent::__construct($context);
        $this->rebuildHelper = $rebuildHelper;
        $this->cartHelper = $cartHelper;
        $this->apiHelper = $apiHelper;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = null;
        $messageId = $this->getRequest()->getParam($this->rebuildHelper->getTrackingMessageParamName(),false);
        if ($data = $this->getRequest()->getParam('data',false)) {
            $data = unserialize($this->rebuildHelper->base64UrlDecode($data));
        } elseif (strlen($messageId)) {
            if (is_string($data = $this->apiHelper->getCartRebuildData($messageId))) {
                $data = unserialize($this->rebuildHelper->base64UrlDecode($data));
            }
        }

        if (is_array($data)) {
            $this->rebuildHelper->rebuildCart($data);
        }

        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(100)
            ->setPath('/')
            ->setSecure($this->getRequest()->isSecure())
            ->setHttpOnly(false);
        $this->cookieManager->setPublicCookie('section_data_ids', json_encode(['cart'=>time()]), $metadata);

        $cartUrl = $this->cartHelper->getCartUrl();
        $cartUrlDelimiter = (strpos($cartUrl,'?')!==false?'&':'?');
        $resultRedirect->setUrl($cartUrl.(strlen($messageId)?$cartUrlDelimiter.$this->rebuildHelper->getTrackingMessageParamName().'='.$messageId:''));
        return $resultRedirect;
    }
}
