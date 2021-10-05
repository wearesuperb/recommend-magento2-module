<?php
/*
 * Superb_Recommend
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Superb
 * @package    Superb_Recommend
 * @author     Superb <hello@wearesuperb.com>
 * @copyright  Copyright (c) 2015 Superb Media Limited
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Superb\Recommend\Model\System\Config\Backend;

class Cron extends \Magento\Framework\App\Config\Value
{
    protected $_pathToPath = [
        'superbrecommend/status_cron/frequency' =>'crontab/superbrecommend/jobs/superbrecommend_update_products_status/schedule/cron_expr',
        'superbrecommend/data_cron/frequency' => 'crontab/superbrecommend/jobs/superbrecommend_update_products_data/schedule/cron_expr'
    ];

    protected $_cronExprToFrequncy = [
        \Superb\Recommend\Model\System\Config\Source\Cron\Frequency::CRON_EVERY_5_MINUTES=>'*/5 * * * *',
        \Superb\Recommend\Model\System\Config\Source\Cron\Frequency::CRON_HOURLY=>'1 * * * *',
        \Superb\Recommend\Model\System\Config\Source\Cron\Frequency::CRON_EVERY_3_HOURS=>'1 */3 * * *',
        \Superb\Recommend\Model\System\Config\Source\Cron\Frequency::CRON_DAILY=>'1 5 * * *'
    ];

    /** @var \Magento\Framework\App\Config\ValueFactory */
    protected $_configValueFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Cron settings after save
     *
     */
    public function afterSave()
    {
        try {
            if (isset($this->_cronExprToFrequncy[$this->getValue()]) && isset($this->_pathToPath[$this->getPath()])) {
                $this->_configValueFactory->create()
                    ->load($this->_pathToPath[$this->getPath()], 'path')
                    ->setValue($this->_cronExprToFrequncy[$this->getValue()])
                    ->setPath($this->_pathToPath[$this->getPath()])
                    ->save();
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save the Cron expression.'));
        }
        return parent::afterSave();
    }
}
