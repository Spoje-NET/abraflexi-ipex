<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexiIpex package
 *
 * https://github.com/Spoje-NET/abraflexi-ipex
 *
 * (c) Vítězslav Dvořák <http://spojenet.cz/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use AbraFlexi\Stitek;
use Ease\Shared;

/**
 * abraflexi-ipex.
 *
 * @copyright (c) 2025, Vítězslav Dvořák
 */
\define('APP_NAME', 'AbraFlexiIpexInit');

require_once '../vendor/autoload.php';
$options = getopt('o::e::', ['output::environment::']);
Shared::init(
    [
        'IPEX_URL', 'IPEX_LOGIN', 'IPEX_PASSWORD',
        'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
    ],
    (\array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env')),
);
new \Ease\Locale(Shared::cfg('MATCHER_LOCALIZE'), '../i18n', 'abraflexi-matcher');
$labeler = new Stitek();

if (Shared::cfg('APP_DEBUG')) {
    $labeler->logBanner();
}

// ABRAFLEXI_ORDERTYPE=code:OBP_VOIP
// ABRAFLEXI_PRODUCT=code:IPEX_POSTPAID
// ABRAFLEXI_DOCTYPE=code:FAKTURA

// $pricer = new \AbraFlexi\
