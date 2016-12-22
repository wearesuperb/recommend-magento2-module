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
namespace Superb\Recommend\Controller\Track;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Escaper;

class Load extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var  Escaper
     */
    private $escaper;

    /**
     * @var \Superb\Recommend\Helper\Tracker
     */
    protected $trackerHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SectionPoolInterface $sectionPool
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Superb\Recommend\Helper\Tracker $trackerHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->trackerHelper = $trackerHelper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        try {
            $response = $this->trackerHelper->getTrackingData();
        } catch (\Exception $e) {
            $resultJson->setStatusHeader(
                \Zend\Http\Response::STATUS_CODE_400,
                \Zend\Http\AbstractMessage::VERSION_11,
                'Bad Request'
            );
            $response = ['message' => $this->getEscaper()->escapeHtml($e->getMessage())];
        }

        return $resultJson->setData($response);
    }

    /**
     * @deprecated
     * @return Escaper
     */
    private function getEscaper()
    {
        if ($this->escaper == null) {
            $this->escaper = $this->_objectManager->get(Escaper::class);
        }
        return $this->escaper;
    }
}
