<?php
namespace Superb\Recommend\Model\Config\Source;

class Panelid implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $_helperApi;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Superb\Recommend\Helper\Api $helperApi,
        \Superb\Recommend\Helper\Data $helper,
	\Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->helperApi = $helperApi;
        $this->helper = $helper;
	$this->_storeManager = $storeManager;
    }

    protected function getPanels()
    {
	$websiteId = (int)$this->_storeManager->getStore()->getWebsiteId();
        $websiteCode = $this->_storeManager->getWebsite($websiteId)->getCode();
        return $this->helperApi->getPanels($websiteCode);
    }

    public function toOptionArray()
    {
        $panels = $this->getPanels();
        $options = [];
        foreach ($panels as $panel) {
            $options[] = [
                'value' => $panel['panel_id'],
                'label' => $panel['title']
            ];
        }
        return $options;
    }
}