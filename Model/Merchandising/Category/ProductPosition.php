<?php

namespace Superb\Recommend\Model\Merchandising\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryLinkRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryState\CouldNotSaveException;
use Magento\Store\Api\Data\WebsiteInterface;
use Superb\Recommend\Helper\Api;
use Superb\Recommend\Logger\Logger;


class ProductPosition
{
    public const ATTRIBUTE_CODE_SET_SORT_BY_RECOMMEND_POSITION = 'sort_by_recommend_position';
    public const ATTRIBUTE_CODE_MERCHANDISING_RULE = 'sort_by_recommend_ruleset';

    protected $storeManager;
    protected $helperApi;
    protected $productRepository;
    protected $categoryProductLinkInterface;
    protected $categoryLinkRepository;
    protected $categoryRepository;
    protected $categoryResource;
    protected $logger;

    protected array $categoriesForReindex = [];

    public function __construct(
        Api $helperApi,
        ProductRepository $productRepository,
        CategoryProductLinkInterface $categoryProductLinkInterface,
        CategoryLinkRepository $categoryLinkRepository,
        CategoryRepositoryInterface $categoryRepository,
        Logger $logger
    ) {
        $this->helperApi = $helperApi;
        $this->productRepository = $productRepository;
        $this->categoryProductLinkInterface = $categoryProductLinkInterface;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }


    public function loadDataPositions(WebsiteInterface $website, Category $category): void
    {
        if ($this->isEnabledSortByRecommendPosition($category)) {
            $recommendations = $this->helperApi->getPosition($category->getId(), $this->getRuleSet($category), $website->getCode());
            if ($recommendations && count($recommendations['data']) > 0) {
                $products = $recommendations['data'];
                if (count($products)) {
                    $this->applyDataScoreToProductsPosition($products, $category);
                }
            }
        }
    }

    public function applyDataScoreToProductsPosition(array $products, Category $category): void
    {
        try {
            $productPositions = $category->getProductsPosition();

            /*reset old position*/
            foreach ($productPositions as  &$pos) {
                $pos = 0;
            }

            foreach ($products as $product) {
                if (isset($productPositions[$product['product_id']])) {
                    $productPositions[$product['product_id']] = $product['score'];
                }
            }
            $this->categoriesForReindex[] = $category->getId();
            $category->setPostedProducts($productPositions);
            //TO DO
            $category->save();
        } catch (CouldNotSaveException $e) {
            $this->logger->addError($e->getMessage());
            $this->logger->addError(
                __('Could not save products with position to category %1',
                    $category->getId()
                )
            );
            $this->logger->info(
                "Products data: " . json_encode($products)
            );
        } catch (NoSuchEntityException | \Exception | \JsonException $e) {
            $this->logger->addError($e->getMessage());
            $this->logger->addError($e->getTraceAsString());
        }
    }

    public function isEnabledSortByRecommendPosition(Category $category)
    {
        return $category->getData(self::ATTRIBUTE_CODE_SET_SORT_BY_RECOMMEND_POSITION);
    }

    public function getRuleSet(Category $category)
    {
        return $category->getData(self::ATTRIBUTE_CODE_MERCHANDISING_RULE);
    }

    public function resetCategoryForReindex(): void
    {
        $this->categoriesForReindex = [];
    }

    public function getCategoryForReindex(): array
    {
        return $this->categoriesForReindex;
    }
}

