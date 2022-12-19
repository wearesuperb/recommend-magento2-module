<?php

namespace Superb\Recommend\Console\Command;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\CacheContext;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Superb\Recommend\Helper\Api;
use Superb\Recommend\Helper\Data;
use Superb\Recommend\Model\Merchandising\Category\ProductPosition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;


class Position extends Command
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductPosition
     */
    protected $modelProductPosition;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Api
     */
    protected $helperApi;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var Data
     */
    protected $dataHelper;

    protected CacheContext $cacheContext;

    protected IndexerFactory $indexFactory;

    protected ManagerInterface $eventManager;

    private array $indexIds = ['catalog_category_product', 'catalog_product_category'];

    /**
     * @param ManagerInterface $eventManager
     * @param CacheContext $cacheContext
     * @param IndexerFactory $indexFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductPosition $modelProductPosition
     * @param State $state
     * @param Api $helperApi
     * @param Data $dataHelper
     */
    public function __construct(
        ManagerInterface $eventManager,
        CacheContext $cacheContext,
        IndexerFactory $indexFactory,
        StoreManagerInterface $storeManager,
        ProductPosition $modelProductPosition,
        State $state,
        Api $helperApi,
        Data $dataHelper
    ) {
        $this->eventManager = $eventManager;
        $this->cacheContext = $cacheContext;
        $this->indexFactory = $indexFactory;
        $this->storeManager = $storeManager;
        $this->modelProductPosition = $modelProductPosition;
        $this->state = $state;
        $this->helperApi = $helperApi;
        $this->dataHelper = $dataHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('recommend:position');
        $this->setDescription('Get product position');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->dataHelper->isEnabled()) {
            return;
        }

        $this->modelProductPosition->resetCategoryForReindex();
        $websites = $this->dataHelper->getWebsitesList();
        try {
            /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
            foreach ($websites as $website) {
                $storeId = $this->storeManager->getWebsite($website->getId())->getDefaultStore()->getId();
                if (!$this->dataHelper->isEnabled($storeId)) {
                    continue;
                }
                $output->writeln("WebSite: " . $website->getCode() . " start sync product position.");
                $rootCatId = $this->helperApi->getWebsite($website->getCode())->getDefaultStore()->getRootCategoryId();
                $categories = $this->dataHelper->getCategoryCollection($rootCatId);

                /** @var Category $category */
                foreach ($categories as $category) {
                    $this->state->emulateAreaCode(
                        Area::AREA_ADMINHTML,
                        [$this->modelProductPosition, 'loadDataPositions'],
                        [$website, $category]
                    );
                }
                $output->writeln("WebSite: " . $website->getCode() . " end sync product position.");
            }

            if ($categoriesIds = $this->modelProductPosition->getCategoryForReindex()) {
                $categoriesIds = array_unique($categoriesIds);
                $output->writeln("Start reindex for categories : " . implode(',', $categoriesIds));
                $this->reindexCategories($categoriesIds);
                $this->clearCacheForCategories($categoriesIds);
                $output->writeln("Finish reindex categories");
            }

        } catch (\Exception | LocalizedException $e) {
            $output->writeln($e->getMessage());
        }
    }

    private function reindexCategories($categoriesIds): void
    {
        foreach ($this->indexIds as $indexId) {
            $indexIdArray = $this->indexFactory->create()->load($indexId);
            $indexIdArray->reindexList($categoriesIds, true);
        }
    }

    protected function clearCacheForCategories($categoriesIds): void
    {
        $this->cacheContext->registerEntities(Category::CACHE_TAG, $categoriesIds);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
