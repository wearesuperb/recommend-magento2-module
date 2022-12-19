<?php

namespace Superb\Recommend\Cron;

use Magento\Catalog\Model\Category;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Superb\Recommend\Helper\Api;
use Superb\Recommend\Helper\Data;
use Superb\Recommend\Logger\Logger;
use Superb\Recommend\Model\Merchandising\Category\ProductPosition;


class SyncProductsPosition
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var ProductPosition
     */
    protected $modelProductPosition;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Data $dataHelper
     * @param StoreManagerInterface $storeManager
     * @param Api $apiHelper
     * @param State $state
     * @param ProductPosition $modelProductPosition
     * @param Logger $logger
     */
    public function __construct(
        Data $dataHelper,
        StoreManagerInterface $storeManager,
        Api $apiHelper,
        State $state,
        ProductPosition $modelProductPosition,
        Logger $logger
    ) {
        $this->dataHelper = $dataHelper;
        $this->storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
        $this->state = $state;
        $this->modelProductPosition = $modelProductPosition;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!$this->dataHelper->isEnabled()) {
            return;
        }

        $websites = $this->dataHelper->getWebsitesList();
        try {
            /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
            foreach ($websites as $website) {
                $storeId = $this->storeManager->getWebsite($website->getId())->getDefaultStore()->getId();
                if (!$this->dataHelper->isEnabled($storeId)) {
                    continue;
                }
                //check isEnabled by website/store code
                $rootCatId = $this->apiHelper->getWebsite($website->getCode())->getDefaultStore()->getRootCategoryId();
                $categories = $this->dataHelper->getCategoryCollection($rootCatId);

                /** @var Category $category */
                foreach ($categories as $category) {
                    $this->state->emulateAreaCode(
                        Area::AREA_CRONTAB,
                        [$this->modelProductPosition, 'loadDataPositions'],
                        [$website, $category]
                    );
                }
            }
        } catch (LocalizedException | \Exception $e) {
            $this->logger->addError($e->getMessage());
            $this->logger->addError($e->getTraceAsString());
        }
    }
}
