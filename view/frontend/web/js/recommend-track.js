/**
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

define([
    'jquery',
    'domReady',
    'mage/cookies'
], function ($, domReady) {
    'use strict';

    domReady(function () {
        var version = $.mage.cookies.get('private_content_version');

        if (!version) {
            _taq.push(["trackPageview"]);
        }
    });
});
