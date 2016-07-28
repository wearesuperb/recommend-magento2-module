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

class SaveSystemConfig implements ObserverInterface
{
    /**
     * @var \Superb\Recommend\Helper\Admin
     */
    protected $_adminHelper;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    public function __construct(
        \Superb\Recommend\Helper\Admin $adminHelper,
        \Superb\Recommend\Helper\Api $apiHelper
    ) {
        $this->_adminHelper = $adminHelper;
        $this->_apiHelper = $apiHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeId = $this->_adminHelper->getSystemConfigStoreId();

        $config = $observer->getEvent()->getConfig();

        $panelsBySlotsData = $this->_adminHelper->getSaveSlotSystemConfig();
        $slotsData = [];
        foreach ($panelsBySlotsData as $slotId => $panelId) {
            $slotsData[] = ['id'=>$slotId,'panel_id'=>$panelId];
        }

        $this->_apiHelper->updateAccount($storeId);
        return $this;
    }
}
