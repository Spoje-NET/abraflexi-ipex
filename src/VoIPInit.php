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
Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], \array_key_exists(1, $argv) ? $argv[1] : (file_exists('../.env') ? '../.env' : null));
new \Ease\Locale(Shared::cfg('MATCHER_LOCALIZE'), '../i18n', 'abraflexi-matcher');
$labeler = new Stitek();

if (Shared::cfg('APP_DEBUG')) {
    $labeler->logBanner();
}

// ABRAFLEXI_ORDERTYPE=code:OBP_VOIP
// ABRAFLEXI_PRODUCT=code:IPEX_POSTPAID
// ABRAFLEXI_DOCTYPE=code:FAKTURA

// $pricer = new \AbraFlexi\
