<?php

namespace Superb\Recommend\Model\ProductLink\CollectionProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductLink\CollectionProviderInterface;

class Recommend implements CollectionProviderInterface
{
    /** @var \Superb\Recommend\Model\Recommend */
    protected $recommendModel;

    /**
     * Recommend constructor.
     * @param \Superb\Recommend\Model\Recommend $recommendModel
     */
    public function __construct(
        \Superb\Recommend\Model\Recommend $recommendModel
    )
    {
        $this->recommendModel = $recommendModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkedProducts(Product $product)
    {
        return (array)$this->recommendModel->getRecommendProducts($product);
    }
}
