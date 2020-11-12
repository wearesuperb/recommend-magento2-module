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
 * @copyright  Copyright (c) 2020 Superb Media Limited
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Superb\Recommend\Model\Config\Source;

use \Magento\Framework\Data\OptionSourceInterface;

class Trackversion implements OptionSourceInterface
{
    public function toOptionArray() : array
    {
          return [
            ['value' => '14', 'label' => __('V14')],
            ['value' => '35', 'label' => __('V35 (recommended)')]
          ];
    }
}