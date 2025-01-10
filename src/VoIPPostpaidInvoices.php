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

namespace SpojeNet\AbraFlexiIpex;

require_once '../vendor/autoload.php';
\define('EASE_APPNAME', 'IPEXPostPaid');

/**
 * Get today's Statements list.
 */
$options = getopt('o::e::', ['output::environment::']);
\Ease\Shared::init(
    [
        'IPEX_URL', 'IPEX_LOGIN', 'IPEX_PASSWORD',
        'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
    ],
    \array_key_exists('environment', $options) ? $options['environment'] : '../.env',
);
$destination = \array_key_exists('output', $options) ? $options['output'] : \Ease\Shared::cfg('RESULT_FILE', 'php://stdout');

$defaultLocale = 'cs_CZ';
setlocale(\LC_ALL, $defaultLocale);
putenv("LC_ALL={$defaultLocale}");

$ipexer = new \SpojeNet\AbraFlexiIpex\Ipex();

if (\Ease\Shared::cfg('APP_DEBUG', false)) {
    $ipexer->logBanner();
}

$report = $ipexer->processIpexInvoices($ipexer->getIpexInvoices());

$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$ipexer->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
