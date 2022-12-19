<?php
namespace Superb\Recommend\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AfterCategory
 * @package Superb\Recommend\Observer
 */
class AfterCategory implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_apiHelper;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Superb\Recommend\Helper\Api $apiHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_apiHelper = $apiHelper;
    }
    
    /**
     * @param Observer $observer
     *
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $item = $observer->getDataObject();
        
        $store = $this->_storeManager->getStore($item->getStoreId());
        $website = $this->_storeManager->getWebsite($store->getWebsiteId());

        $data = [];
        $data[] = [
            'action' => 'upsert_update',
            'data' => [
                'id'=>$item->getId(),
                'status'=>$item->getIsActive()==1?true:false,
                'name'=>$item->getName(),
                'url'=>$store->getBaseUrl().$item->getUrlPath(),
                'path'=>$item->getPath()
            ]
        ];
        
        $this->_apiHelper->uploadCategories($data,$website->getCode());

        return $this;
    }
}
