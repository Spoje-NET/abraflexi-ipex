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
\define('EASE_APPNAME', 'IPEXPostPaidInvoices');
$exitcode = 0;
/**
 * Get today's Statements list.
 */
$options = getopt('o::e::', ['output::environment::']);
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

$report = $ipexer->processIpexPostpaidInvoices();

// Display audit summary if debug mode or verbose output
if (Shared::cfg('APP_DEBUG', false) || Shared::cfg('EASE_LOGGER', '') === 'console') {
    $ipexer->displayAuditSummary($report, 'invoices');
}

// Determine output format based on destination or environment
$useMultiFlexiFormat = Shared::cfg('MULTIFLEXI_REPORT_FORMAT', false)
                      || (strpos($destination, 'multiflexi') !== false)
                      || (getenv('MULTIFLEXI') !== false);

if ($useMultiFlexiFormat) {
    // Generate MultiFlexi-compliant report
    $multiFlexiReport = $ipexer->generateMultiFlexiReport($report, 'invoices', $exitcode);
    $written = file_put_contents($destination, json_encode($multiFlexiReport, \JSON_PRETTY_PRINT));
    $ipexer->addStatusMessage(sprintf('MultiFlexi report saved to %s', $destination), $written ? 'success' : 'error');
} else {
    // Generate standard detailed audit report
    $written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
    $ipexer->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');
}

exit($exitcode);
