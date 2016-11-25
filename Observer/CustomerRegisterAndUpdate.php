<?php
namespace Superb\Recommend\Observer;

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

use Magento\Framework\Event\ObserverInterface;

class CustomerRegisterAndUpdate implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Superb\Recommend\Helper\Tracker
     */
    protected $_helper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Superb\Recommend\Helper\Tracker $helper
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_helper->isEnabled()) {
            return $this;
        }
        $customer = $observer->getEvent()->getCustomer();
        if ($customer->getOrigData($customer->getIdFieldName())!=$customer->getData($customer->getIdFieldName())) {
            try {
                $data = $this->_helper->getCustomerRegistrationConfirmData($customer);
                $this->_helper->setTrackingData($data);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        } else {
            try {
                $data = $this->_helper->getCustomerUpdateDetailsData($customer);
                $this->_helper->setTrackingData($data);
                $data = $this->_helper->getCustomerCustomData($customer);
                if (count($data)) {
                    foreach ($data as $row) {
                        $this->_helper->setTrackingData($row);
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
        return $this;
    }
}
