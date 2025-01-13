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
use SpojeNet\AbraFlexiIpex\CallsListing;

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
    \array_key_exists('environment', $options) ? $options['environment'] : '../.env',
);
$destination = \array_key_exists('output', $options) ? $options['output'] : Shared::cfg('RESULT_FILE', 'php://stdout');

$defaultLocale = 'cs_CZ';
setlocale(\LC_ALL, $defaultLocale);
putenv("LC_ALL={$defaultLocale}");

$grabber = new \IPEXB2B\ApiClient(null, ['section' => 'invoices']);

if (Shared::cfg('APP_DEBUG', false)) {
    $grabber->logBanner();
}

$grabber->setUrlParams(['monthOffset' => -1]);
$invoicesRaw = $grabber->requestData('prepaid');
$adresar = new \AbraFlexi\Adresar();

foreach ($invoicesRaw as $invoiceRaw) {
    $sumCelkem = 0;
    $klientExtID = 'ext:ipex:'.$invoiceRaw['customerId'];

    if ($adresar->recordExists($klientExtID)) {
        $adresar->loadFromAbraFlexi($klientExtID);
        $email = $adresar->getEmail();
        $startDate = \IPEXB2B\ApiClient::ipexDateTimeToDateTime($invoiceRaw['dateStart']);
        $endDate = \IPEXB2B\ApiClient::ipexDateTimeToDateTime($invoiceRaw['dateEnd']);

        $caller = new \IPEXB2B\Calls();

        $startDate = new \DateTime();
        $startDate->modify('-1 month');
        $now = new \DateTime();
        $calls = $caller->getCallsForCustomer(
            $startDate,
            $invoiceRaw['customerId'],
        );

        if ($calls) {
            $range = strftime('%D', $startDate->getTimestamp()).' - '.strftime(
                '%D',
                $now->getTimestamp(),
            );
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

            $postman->send();
        }
    } else {
        $engine->addStatusMessage(
            $invoiceRaw['customerName'].' without extID',
            'warning',
        );
        $engine->addStatusMessage(
            \constant('SYSTEM_URL').'/ipex.php?customerId='.$invoiceRaw['customerId'].'&extid='.$invoiceRaw['externId'],
            'debug',
        );
    }
}

$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$engine->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
