<?php

namespace Superb\Recommend\Model\Config\Source;

class Rules extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var \Superb\Recommend\Helper\Api
     */
    protected $helperApi;

    public function __construct(
        \Superb\Recommend\Helper\Api $helperApi
    ) {
        $this->helperApi = $helperApi;
    }

    public function getAllOptions()
    {
        $rules = $this->helperApi->getRuleset('base');
        $options = [];
        if($rules) {
            foreach ($rules as $ruleset) {
                if ($ruleset['status'] == 'active') {
                    $options[] = [
                        'value' => $ruleset['id'],
                        'label' => $ruleset['name']
                    ];
                }
            }
        }
        return $options;
    }
}
