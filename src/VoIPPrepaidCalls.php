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

use Ease\Mailer;
use Ease\Shared;

require_once '../vendor/autoload.php';

\define('EASE_APPNAME', 'IPEXPrepaid');

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
$exitcode = 0;
$grabber = new \IPEXB2B\ApiClient('', ['section' => 'invoices']);

if (Shared::cfg('APP_DEBUG', false)) {
    $grabber->logBanner();
}

// Get monthOffset from environment variable or default to -1
$monthOffset = (int) Shared::cfg('MONTH_OFFSET', -1);
$grabber->setUrlParams(['monthOffset' => $monthOffset]);
$invoicesRaw = $grabber->requestData('prepaid');
$adresar = new \AbraFlexi\Adresar();
$jsonReportData = [];

foreach ($invoicesRaw as $invoiceRaw) {
    $sumCelkem = 0;
    $klientExtID = $invoiceRaw['externId'];

    if ($adresar->recordExists($klientExtID)) {
        $adresar->loadFromAbraFlexi($klientExtID);
        $email = $adresar->getEmail();
        $startDate = \IPEXB2B\ApiClient::ipexDateTimeToDateTime($invoiceRaw['dateStart']);
        $endDate = \IPEXB2B\ApiClient::ipexDateTimeToDateTime($invoiceRaw['dateEnd']);

        $caller = new \IPEXB2B\Calls();

        $startDate = new \DateTime();
        $startDate->modify('-1 month');
        $now = new \DateTime();
        $calls = $caller->getCallsForCustomer($startDate, (int) $invoiceRaw['customerId']);

        $range = $startDate->format('m/d/Y').' - '.$now->format('m/d/Y');

        // Calculate total amount from calls
        $totalAmount = 0;

        foreach ($calls as $call) {
            if (isset($call['price'])) {
                $totalAmount += (float) $call['price'];
            }
        }

        $report = new \Ease\Container(new \Ease\Html\H2Tag('Calls listing'));
        $report->addItem(new \Ease\Html\PTag($range));
        $report->addItem(new CallsListing(
            $calls,
            ['style' => 'font-size: small'],
        ));

        $mpdfTmpDir = sys_get_temp_dir().'/mpdf';

        if (!file_exists($mpdfTmpDir)) {
            mkdir($mpdfTmpDir);
        }

        $html2pdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'dejavusans',
            'tempDir' => $mpdfTmpDir,
        ]);
        $html2pdf->setDefaultFont('Helvetica');
        $html2pdf->writeHTML((string) $report);
        $pdfFilename = $mpdfTmpDir.'/'.$invoiceRaw['customerId'].'_'._('Calls').'_'.$startDate->format('Y-m-d').'_'.$now->format('Y-m-d').'.pdf';

        $html2pdf->Output($pdfFilename, \Mpdf\Output\Destination::FILE);

        $postman = new Mailer(
            $email,
            _('Prepaid Calls listing').' '.$range,
            _('Prepaid Calls for last month'),
        );
        $postman->addFile($pdfFilename, 'application/pdf');

        unlink($pdfFilename);

        $jsonReportData[$adresar->getRecordCode()]['mail'] = $postman->send();
        $jsonReportData[$adresar->getRecordCode()]['period'] = $range;
        $jsonReportData[$adresar->getRecordCode()]['totalAmount'] = $totalAmount;
        $jsonReportData[$adresar->getRecordCode()]['callsCount'] = \count($calls);
    } else {
        $jsonReportData[$adresar->getRecordCode()]['mail'] = false;
        $jsonReportData[$adresar->getRecordCode()]['period'] = null;
        $jsonReportData[$adresar->getRecordCode()]['totalAmount'] = null;
        $jsonReportData[$adresar->getRecordCode()]['callsCount'] = 0;
        $grabber->addStatusMessage(
            $invoiceRaw['customerName'].' without extID',
            'warning',
        );
    }
}

$written = file_put_contents($destination, json_encode($jsonReportData, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$grabber->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
