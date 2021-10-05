<?php
namespace Superb\Recommend\Block;

use Magento\Customer\CustomerData\SectionSourceInterface;

class AddToCart implements SectionSourceInterface
{
    /**
     * @var \Superb\Recommend\Model\SessionFactory
     */
    protected $recommendSession;

    /**
     * AddToCart constructor.
     * @param \Superb\Recommend\Model\SessionFactory $recommendSession
     */
    public function __construct(
        \Superb\Recommend\Model\SessionFactory $recommendSession
    ) {
        $this->recommendSession = $recommendSession;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData()
    {
        $data = [
            'events' => []
        ];

        if ($this->recommendSession->create()->hasAddToCart()) {
            $data['events'][] = [
                'eventName' => 'AddToCart',
                'eventAdditional' => $this->recommendSession->create()->getAddToCart()
            ];
        }
        
        return $data;
    }
}
