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
namespace Superb\Recommend\Block;

use Magento\Customer\CustomerData\SectionSourceInterface;

class Subscribe implements SectionSourceInterface
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
     * Subscribe constructor.
     * @param \Superb\Recommend\Model\SessionFactory $recommendSession
     * @param \Superb\Recommend\Helper\Data $helperData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Superb\Recommend\Model\SessionFactory $recommendSession,
        \Superb\Recommend\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->recommendSession = $recommendSession;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData(): array
    {
        if (!$this->helperData->isEnabled($this->storeManager->getStore()->getId())) {
            return [];
        }

        $data = [
            'events' => []
        ];

        if ($this->recommendSession->create()->hasAddSubscribe()) {
            $data['events'][] = [
                'eventName' => 'Subscribe',
                'eventAdditional' => $this->recommendSession->create()->getAddSubscribe()['email_hash']
            ];
        }
        return $data;
    }
}
