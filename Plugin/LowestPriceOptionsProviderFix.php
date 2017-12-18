<?php
namespace Superb\Recommend\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\LinkedProductSelectBuilderInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadataInterface;

class LowestPriceOptionsProviderFix
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LinkedProductSelectBuilderInterface
     */
    private $linkedProductSelectBuilder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * Key is product id. Value is prepared product collection
     *
     * @var array
     */
    private $productsMap;


    /**
     * @param ResourceConnection $resourceConnection
     * @param LinkedProductSelectBuilderInterface $linkedProductSelectBuilder
     * @param CollectionFactory $collectionFactory
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LinkedProductSelectBuilderInterface $linkedProductSelectBuilder,
        CollectionFactory $collectionFactory,
        ProductMetadataInterface $productMetadata
    ) {
        $this->resource = $resourceConnection;
        $this->linkedProductSelectBuilder = $linkedProductSelectBuilder;
        $this->collectionFactory = $collectionFactory;
        $this->productMetadata = $productMetadata;
    }


    public function aroundGetProducts(\Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider $subject, callable $proceed, ProductInterface $product)
    {
        $version = substr($this->productMetadata->getVersion(), 0, 3);
        $productIds = $this->resource->getConnection()->fetchCol(
            '(' . implode(') UNION (', $this->linkedProductSelectBuilder->build($product->getId())) . ')'
        );

        if ($version == '2.0') {
            $lowestPriceChildProducts = $this->collectionFactory->create()
                ->addIdFilter($productIds)
                ->addAttributeToSelect('*')
                ->addPriceData()
                ->addTierPriceData()
                ->getItems();

            return $lowestPriceChildProducts;
        } else {
            if (!isset($this->productsMap[$product->getStoreId()][$product->getId()])) {
                $attributes = $version == '2.1' ?
                                          ['price', 'special_price'] :
                                          ['price', 'special_price', 'special_from_date', 'special_to_date', 'tax_class_id'];
                $this->productsMap[$product->getStoreId()][$product->getId()] = $this->collectionFactory->create()
                    ->addAttributeToSelect($attributes)
                    ->addIdFilter($productIds)
                    ->getItems();
            }

            return $this->productsMap[$product->getStoreId()][$product->getId()];
        }
    }
}
