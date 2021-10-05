<?php

namespace Superb\Recommend\Model\Product;

class Link extends \Magento\Catalog\Model\Product\Link
{
    const LINK_TYPE_RECOMMEND = 10;

    /**
     * @return $this
     */
    public function useRecommendLinks()
    {
        $this->setLinkTypeId(self::LINK_TYPE_RECOMMEND);
        return $this;
    }

}
