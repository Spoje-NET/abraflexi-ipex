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

// Set monthOffset from command line options, environment variable, or default to -1
$monthOffset = isset($options['monthOffset']) ? (int) $options['monthOffset'] :
               (isset($options['m']) ? (int) $options['m'] : 
               (int) Shared::cfg('MONTH_OFFSET', -1));
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

// Display audit summary if debug mode or verbose output
if (Shared::cfg('APP_DEBUG', false) || Shared::cfg('EASE_LOGGER', '') === 'console') {
    $ipexer->displayAuditSummary($report, 'orders');
}

// Determine output format based on destination or environment
$useMultiFlexiFormat = Shared::cfg('MULTIFLEXI_REPORT_FORMAT', false)
                      || (strpos($destination, 'multiflexi') !== false)
                      || (getenv('MULTIFLEXI') !== false);

if ($useMultiFlexiFormat) {
    // Generate MultiFlexi-compliant report
    $multiFlexiReport = $ipexer->generateMultiFlexiReport($report, 'orders', $exitcode);
    $written = file_put_contents($destination, json_encode($multiFlexiReport, \JSON_PRETTY_PRINT));
    $ipexer->addStatusMessage(sprintf('MultiFlexi report saved to %s', $destination), $written ? 'success' : 'error');
} else {
    // Add summary information to the top level of the report for better visibility
    if (isset($report['_audit']['summary'])) {
        $summary = $report['_audit']['summary'];
        $report['period'] = [
            'from' => $summary['processedPeriod']['from'] ?? null,
            'to' => $summary['processedPeriod']['to'] ?? null,
            'range' => isset($summary['processedPeriod']['from'], $summary['processedPeriod']['to']) 
                ? $summary['processedPeriod']['from'] . ' - ' . $summary['processedPeriod']['to'] 
                : null
        ];
        $report['totalAmount'] = $summary['totalAmount'] ?? 0.0;
        $report['processedCount'] = $summary['processedCount'] ?? 0;
        $report['createdCount'] = $summary['createdCount'] ?? 0;
        $report['skippedCount'] = $summary['skippedCount'] ?? 0;
        $report['duplicateCount'] = $summary['duplicateCount'] ?? 0;
        $report['processedAt'] = $summary['processedAt'] ?? null;
    }
    
    // Generate standard detailed audit report
    $written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
    $ipexer->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');
}

exit($exitcode);
