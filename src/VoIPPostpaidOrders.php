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

use Ease\Shared;

require_once '../vendor/autoload.php';
\define('EASE_APPNAME', 'IPEXPostPaidOrders');
$exitcode = 0;
/**
 * Get today's Statements list.
 */
$options = getopt('o::e::m::f::t::c', ['output::environment::', 'monthOffset::', 'continue']);
Shared::init(
    [
        'IPEX_URL', 'IPEX_LOGIN', 'IPEX_PASSWORD',
        'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
    ],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);
$destination = \array_key_exists('output', $options) ? $options['output'] : Shared::cfg('RESULT_FILE', 'php://stdout');

\Ease\Locale::singleton(null, '../i18n', 'abraflexi-ipex');

$ipexer = new \SpojeNet\AbraFlexiIpex\Ipex();

if (Shared::cfg('APP_DEBUG', false)) {
    $ipexer->logBanner();
}

$monthOffset = isset($options['monthOffset']) ? (int) $options['monthOffset'] : (isset($options['m']) ? (int) $options['m'] : -1);
$dateFrom = $options['dateFrom'] ?? ($options['f'] ?? null);
$dateTo = $options['dateTo'] ?? ($options['t'] ?? null);

if (\array_key_exists('continue', $options) || \array_key_exists('c', $options)) {
    // Find the last generated order and set the next period

    $lastOrderDate = $ipexer->getLastOrderDate();

    if ($lastOrderDate) {
        // If $lastOrderDate is a DateTime object, convert to string
        if ($lastOrderDate instanceof \DateTimeInterface) {
            $lastOrderDateStr = $lastOrderDate->format('Y-m-d');
        } else {
            $lastOrderDateStr = (string) $lastOrderDate;
        }

        $lastOrder = new \DateTime($lastOrderDateStr);
        $now = new \DateTime();
        $monthOffset = -1 * (($now->format('Y') - $lastOrder->format('Y')) * 12 + ($now->format('n') - $lastOrder->format('n')));

        // Ensure at least -1 month offset
        if ($monthOffset > -1) {
            $monthOffset = -1;
        }
    } else {
        $monthOffset = -1;
    }
}

$report = $ipexer->processIpexPostpaidOrders($ipexer->getIpexInvoices([
    'monthOffset' => $monthOffset,
]));

$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$ipexer->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
