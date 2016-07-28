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

namespace Superb\Recommend\Model\System\Config\Source\Cron;

class Frequency extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected static $_staticOptions = null;

    const CRON_EVERY_5_MINUTES  = 'every5minutes';
    const CRON_HOURLY           = 'hourly';
    const CRON_EVERY_3_HOURS    = 'every3hours';
    const CRON_DAILY            = 'daily';

    public function getAllOptions()
    {
        if (self::$_staticOptions === null) {
            self::$_staticOptions = [
                [
                    'label' => __('Every 5 minutes'),
                    'value' => self::CRON_EVERY_5_MINUTES,
                ],
                [
                    'label' => __('Hourly'),
                    'value' => self::CRON_HOURLY,
                ],
                [
                    'label' => __('Every 3 hours'),
                    'value' => self::CRON_EVERY_3_HOURS,
                ],
                [
                    'label' => __('Daily'),
                    'value' => self::CRON_DAILY,
                ],
            ];
        }
        return self::$_staticOptions;
    }
}
