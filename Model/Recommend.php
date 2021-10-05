<?php

namespace Superb\Recommend\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Link\Collection;
use Magento\Framework\DataObject;
use Superb\Recommend\Model\Product\Link;

class Recommend extends DataObject
{
    /**
     * Product link instance
     *
     * @var Product\Link
     */
    protected $linkInstance;

    /**
     * Accessory constructor.
     * @param Link $productLink
     */
    public function __construct(
        Link $productLink
    ) {
        $this->linkInstance = $productLink;
    }

    /**
     * Retrieve link instance
     *
     * @return  Product\Link
     */
    public function getLinkInstance()
    {
        return $this->linkInstance;
    }

    /**
     * Retrieve array of Accessory products
     *
     * @param Product $currentProduct
     * @return array
     */
    public function getRecommendProducts(Product $currentProduct)
    {
        if (!$this->hasRecommendProducts()) {
            $products = [];
            $collection = $this->getRecommendProductCollection($currentProduct);
            foreach ($collection as $product) {
                $products[] = $product;
            }
            $this->setRecommendProducts($products);
        }
        return $this->getData('recommend_products');
    }

    /**
     * Retrieve accessory products identifiers
     *
     * @param Product $currentProduct
     * @return array
     */
    public function getRecommendProductIds(Product $currentProduct)
    {
        if (!$this->hasRecommendProductIds()) {
            $ids = [];
            foreach ($this->getRecommendProducts($currentProduct) as $product) {
                $ids[] = $product->getId();
            }
            $this->setRecommendProductIds($ids);
        }
        return $this->getData('recommend_product_ids');
    }

    public function getRecommendProductSkus(Product $currentProduct)
    {
        if (!$this->hasRecommendProductSkus()) {
            $skus = [];
            foreach ($this->getRecommendProducts($currentProduct) as $product) {
                $skus[] = $product->getSku();
            }
            $this->setRecommendProductSkus($skus);
        }
        return $this->getData('recommend_product_skus');
    }

    /**
     * Retrieve collection recommend product
     *
     * @param Product $currentProduct
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection
     */
    public function getRecommendProductCollection(Product $currentProduct)
    {
        $collection = $this->getLinkInstance()->useRecommendLinks()->getProductCollection()->setIsStrongMode();
        $collection->setProduct($currentProduct);
        return $collection;
    }

    /**
     * Retrieve collection accessory link
     *
     * @param Product $currentProduct
     * @return Collection
     */
    public function getRecommendLinkCollection(Product $currentProduct)
    {
        $collection = $this->getLinkInstance()->useRecommendLinks()->getLinkCollection();
        $collection->setProduct($currentProduct);
        $collection->addLinkTypeIdFilter();
        $collection->addProductIdFilter();
        $collection->joinAttributes();
        return $collection;
    }
}
