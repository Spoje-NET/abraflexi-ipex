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

use AbraFlexi\FakturaVydana;
use AbraFlexi\ObjednavkaPrijata;
use Ease\Mailer;

/**
 * Description of IPEX.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Ipex extends \Ease\Sand
{
    public float $invoicingLimit = 200.0;
    public FakturaVydana $invoicer;
    public \DateTime $since;
    public \DateTime $until;
    private string $counter = '';
    private ObjednavkaPrijata $order;

    public function __construct()
    {
        $this->setObjectName();
    }

    /**
     * Get IPEX invoices data for last month.
     *
     * @return array<int, array<string, string>>
     */
    public function getIpexInvoices(): array|bool
    {
        $grabber = new \IPEXB2B\ApiClient('', ['section' => 'invoices']);

        $this->since = new \DateTime('first day of last month');
        $this->until = new \DateTime('last day of last month');
        //        $grabber->setUrlParams(['dateFrom' => $this->since->format(\DateTime::ATOM),'dateTo' => $this->until->format(\DateTime::ATOM)]);

        $grabber->setUrlParams(['monthOffset' => -1]);

        $this->invoicingLimit = (float) \Ease\Shared::cfg('ABRAFLEXI_MINIMAL_INVOICING', 200);

        return $grabber->requestData('postpaid');
    }

    /**
     * @param array<int, array<string, string>> $invoicesRaw
     *
     * @return array<string, array> Description
     */
    public function processIpexInvoices(array $invoicesRaw): array
    {
        $position = 0;

        foreach ($invoicesRaw as $invoiceRaw) {
            $result[(string) $invoiceRaw['externId']] = [];
            $this->counter = '#'.++$position.'/'.\count($invoicesRaw).' ';

            $order = $this->createOrder($invoiceRaw);

            if ($order) {
                $result[(string) $invoiceRaw['externId']]['order'] = $order->getRecordCode();
                $calls = $this->getUnivoicedCalls($invoiceRaw['externId']);
                $result[(string) $invoiceRaw['externId']]['calls'] = $calls;

                if ($this->uninvoicedAmount($calls) > $this->invoicingLimit) {
                    $result[(string) $invoiceRaw['externId']]['invoice'] = $this->createInvoice($calls, (int) $invoiceRaw['customerId']);
                }
            }
        }

        return $result;
    }

    /**
     * Get IPEX orders not in "done" state.
     *
     * @return array<int, array<string, mixed>> Orders with IPEX_POSTPAID item
     */
    public function getUnivoicedCalls(string $klientExtID): array
    {
        $ipexOrders = [];

        foreach ($this->getUsersPreparedOrders($klientExtID) as $order) {
            if (!empty($order['polozkyDokladu'])) {
                foreach ($order['polozkyDokladu'] as $orderItem) {
                    if ($orderItem['kod'] === \AbraFlexi\Functions::uncode(\Ease\Shared::cfg('ABRAFLEXI_PRODUCT', 'IPEX_POSTPAID'))) {
                        $ipexOrders[$order['kod']] = $order;
                    }
                }
            }
        }

        return $ipexOrders;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUsersPreparedOrders(string $klientExtID)
    {
        $result = [];
        $this->getOrderer()->defaultUrlParams['order'] = 'datVyst@A';
        $this->getOrderer()->defaultUrlParams['limit'] = 0;
        $orders = $this->getOrderer()->getColumnsFromAbraFlexi(
            [
                'kod',
                'mena',
                'firma',
                'zamekK',
                'smerKod',
                'datVyst',
                'specSym',
                'sumCelkem',
                'sumCelkemMen',
                'polozkyDokladu(kod,nazev,cenaMj)',
            ],
            [
                'firma' => $klientExtID, 
                'storno' => false,
                'stavUzivK' => 'stavDoklObch.pripraveno', 
                'typDokl' => \Ease\Shared::cfg('ABRAFLEXI_ORDERTYPE', 'code:OBP_VOIP')],
            'id',
        );

        if ($this->getOrderer()->lastResponseCode === 200) {
            if (empty($orders)) {
                $result = [];
            } else {
                $result = $orders;
            }
        }

        return $result;
    }

    /**
     * @param string $klientExtID
     *
     * @return string
     */
    public function foundLastInvoice($klientExtID)
    {
        return $this->getInvoicer()->getPrevRecordID(['firma' => $klientExtID]);
    }

    /**
     * Count Given Orders Total.
     *
     * @param array $ordersData
     *
     * @return float
     */
    public function uninvoicedAmount($ordersData)
    {
        $amount = 0;

        foreach ($ordersData as $orderCode => $orderData) {
            $amount += (float) $orderData['sumCelkem'];
        }

        return $amount;
    }

    /**
     * Create Order from IPEX.
     *
     * @param array<string, string> $invoiceRaw
     *
     * @return \SpojeNet\AbraFlexiIpex\FakturaVydana
     */
    public function createOrder($invoiceRaw)
    {
        $adresar = new \AbraFlexi\Adresar();
        $startDate = new \DateTime($invoiceRaw['dateStart']);
        $endDate = new \DateTime($invoiceRaw['dateEnd']);

        $order = $this->getOrderer([
            'typDokl' => \Ease\Shared::cfg('ABRAFLEXI_ORDERTYPE', 'code:OBP_VOIP'),
            'stavUzivK' => 'stavDoklObch.pripraveno',
            'zaokrNaSumK' => 'zaokrNa.zadne',
            'zaokrNaDphK' => 'zaokrNa.zadne',
            //            'datVyst' =>
            'popis' => _('IPEX Postpaid'),
        ]);

        if ($adresar->recordExists(\AbraFlexi\Functions::code($invoiceRaw['externId']))) {
            $order->setDataValue('firma', \AbraFlexi\Functions::code($invoiceRaw['externId']));
        } else {
            $order->setDataValue('nazFirmy', $invoiceRaw['customerName']);
            $order->setDataValue('ulice', $invoiceRaw['street']);
            $order->setDataValue('psc', $invoiceRaw['zipCode']);
            $order->setDataValue('mesto', $invoiceRaw['city']);
            $order->setDataValue('poznam', 'Ipex: '.$invoiceRaw['customerId'].' '.$invoiceRaw['note']);
        }

        $pricelistItem = [
            'cenaMj' => $invoiceRaw['price'],
            'nazev' => 'Telefonní služby od '.self::formatDate($startDate).' do '.self::formatDate($endDate),
            'cenik' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_PRODUCT', 'IPEX_POSTPAID')),
            'stitky' => 'API_IPEX',
        ];
        $order->setDataValue('popis', $pricelistItem['nazev']);

        if (strtolower(\Ease\Shared::cfg('ABRAFLEXI_CREATE_EMPTY_ORDERS', 'true')) === 'true' || (float) $invoiceRaw['price']) {
            $order->addArrayToBranch($pricelistItem, 'polozkyDokladu');

            $order->addStatusMessage(
                $this->counter.$invoiceRaw['customerName'].' '.$invoiceRaw['price'].' CZK ',
                $order->sync() ? 'success' : 'error',
            );

            $pdfCallLog = $this->pdfCallLog((int) $invoiceRaw['customerId'], $order->getDataValue('nazFirmy'));

            $callLogFilename = sys_get_temp_dir().'/'.str_replace(
                [' ', ':'],
                ['_', '-'],
                \Ease\Functions::rip($order->getDataValue('popis')),
            ).'.pdf';

            file_put_contents($callLogFilename, $pdfCallLog);
            \AbraFlexi\Priloha::addAttachmentFromFile($order, $callLogFilename);

            $this->sendCallListByMail($order, $callLogFilename);

            unlink($callLogFilename);

            return $order;
        }

        $order->addStatusMessage($this->counter.$invoiceRaw['customerName'].' 0,-', 'debug');
    }

    public static function formatDate($dateTime)
    {
        return $dateTime->format('y m. d. H:i:s T');
    }

    /**
     * Gives you call log as PDF.
     *
     * @param int    $ipexCustomerID internal IPEX id
     * @param string $customerName   to use in filename
     * @param int    $offset         months back to include in list
     *
     * @return string binary PDF
     */
    public function pdfCallLog(int $ipexCustomerID, $customerName, $offset = 1)
    {
        $caller = new \IPEXB2B\Calls();

        $startDate = new \DateTime();
        $startDate->modify(' -'.$offset.' month');
        $now = new \DateTime();
        $calls = $caller->getCallsForCustomer($startDate, $ipexCustomerID);

        $report = new \Ease\Container(new \Ease\Html\H2Tag(_('Calls listing')));
        $report->addItem(new \Ease\Html\PTag(
            $startDate->format('m/d/Y').' - '.$now->format('m/d/Y'),
        ));
        $report->addItem(new CallsListing(
            $calls,
            ['style' => 'font-size: small'],
        ));

        $tmpdir = sys_get_temp_dir().'/mpdf/';

        if (!file_exists($tmpdir)) {
            mkdir($tmpdir);
        }

        $html2pdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'dejavusans',
            'tempDir' => sys_get_temp_dir().'/mpdf/',
        ]);
        $html2pdf->setDefaultFont('Helvetica');
        $html2pdf->writeHTML((string) $report);

        return $html2pdf->Output('', 'S');
    }

    /**
     * Save PDF Call log to file in system temp dir.
     *
     * @param int    $ipexCustomerID
     * @param string $customerName
     * @param int    $offset         months
     *
     * @return string saved callLog filename
     */
    public function savePdfCallLog($ipexCustomerID, $customerName, $offset = 1)
    {
        $startDate = new \DateTime();
        $startDate->modify(' -'.$offset.' month');
        $now = new \DateTime();

        $pdfFilename = sys_get_temp_dir().'/'.urlencode(str_replace(
            ' ',
            '_',
            \Ease\Functions::rip($customerName),
        )).'_'._('Calls').'_'.$startDate->format('Y-m-d').'_'.$now->format('Y-m-d').'.pdf';

        if (
            file_put_contents(
                $pdfFilename,
                $this->pdfCallLog($ipexCustomerID, $pdfFilename, $offset),
            )
        ) {
            return $pdfFilename;
        }
    }

    /**
     * @param \AbraFlexi\FakturaVydana $order
     */
    public function addCallLogAsItems($order): void
    {
        $caller = new \IPEXB2B\Calls();

        $calls = $caller->getCallsForCustomer(
            $startDate,
            $invoiceRaw['customerId'],
        );

        $callsByNumber = [];

        if ($calls) {
            foreach ($calls as $callsData) {
                $callsByNumber[$callsData['cislo_int']][] = $callsData;
            }

            foreach ($callsByNumber as $internalNumber => $numberCalls) {
                $order->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => '======================== '.$internalNumber.' ========================']);

                foreach ($numberCalls as $callsData) {
                    $pricelistItem = [
                        'typPolozkyK' => 'typPolozky.text',
                        'nazev' => trim($callsData['cislo_ext'].' , '.$callsData['destinace'].' , '.$callsData['stav'].' , '.str_replace(
                            '00:00:00',
                            '',
                            $callsData['datetime'].' , '.$callsData['cena'].' Kč',
                        )),
                    ];
                    $order->addArrayToBranch($pricelistItem);
                }
            }
        }
    }

    /**
     * Send CallList by mail.
     *
     * @param ObjednavkaVydana $order
     * @param string           $callLogFilename PDF File path
     *
     * @return bool send status
     */
    public function sendCallListByMail(
        $order,
        $callLogFilename
    ): bool {
        $sent = false;
        $recipient = $order->getEmail();

        if ($recipient) {
            $mailer = new Mailer(
                $recipient,
                _('Listing').' '.$order->getDataValue('popis').' '._('for').' '.$order->getDataValue('firma'),
            );

            //        $mailer->addFile($order->downloadInFormat('pdf', '/tmp/'), //Přiložit objednávku
            //            \AbraFlexi\Formats::$formats['PDF']['content-type']);

            $mailer->addFile(
                $callLogFilename, // Zaslat pouze výpis hovorů
                \AbraFlexi\Formats::$formats['PDF']['content-type'],
            );
            $sent = $mailer->send();
        } else {
            $this->addStatusMessage(sprintf(_('Customer %s without email address ?!?'), $order->getDataValue('firma')), 'warning');
        }

        return $sent;
    }

    /**
     * Create new IPEX Invoice.
     *
     * @param int $ipexCustomerID Customer's IPEX ID
     */
    public function createInvoice(array $callsOrders, int $ipexCustomerID): \AbraFlexi\FakturaVydana
    {
        $invoice = new FakturaVydana();
        $invoice->setDataValue('typDokl', \Ease\Shared::cfg('ABRAFLEXI_DOCTYPE', \AbraFlexi\RO::code('FAKTURA')));

        $invoice->setDataValue('stavMailK', 'stavMail.neodesilat');
        $invoice->setDataValue('firma', \AbraFlexi\RO::code((string) current($callsOrders)['firma']));
        $invoice->setDataValue('typUcOp', \AbraFlexi\RO::code('TRŽBA SLUŽBY INT'));

        foreach ($callsOrders as $orderCode => $orderData) {
            if (!empty($orderData['polozkyDokladu'])) {
                foreach ($orderData['polozkyDokladu'] as $orderItem) {
                    if ($orderItem['kod'] === \AbraFlexi\Functions::uncode(\Ease\Shared::cfg('ABRAFLEXI_PRODUCT', 'IPEX_POSTPAID'))) {
                        unset($orderItem['id'], $orderItem['kod']);

                        $orderItem['cenik'] = \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_PRODUCT', 'IPEX_POSTPAID'));
                        $invoice->addArrayToBranch($orderItem);
                    } else {
                        $invoice->addStatusMessage(
                            $this->counter.$orderData['firma']->showAs.' '.$orderCode.': NO IPEX ITEM '.$orderItem['kod'].':'.$orderItem['nazev'],
                            'warning',
                        );
                    }
                }
            }
        }

        $startDate = clone $this->since;
        $startDate->modify(' -'.\count($callsOrders).' month')->modify('first day of next month');

        $invoice->setDataValue(
            'popis',
            'Telefonní služby ' /* . _('from') . ' ' . self::formatDate($startDate) . ' ' */._('to').' '.self::formatDate($this->until),
        );

        $invoice->setDataValue('duzpPuv', \AbraFlexi\Functions::dateToFlexiDate($this->until));

        if ($invoice->sync()) {
            $invoice->addStatusMessage(
                $this->counter.$invoice->getDataValue('firma')->showAs.' '.$invoice->getDataValue('sumCelkem').' CZK ',
                'success',
            );

            \AbraFlexi\Priloha::addAttachmentFromFile($invoice, $this->savePdfCallLog($ipexCustomerID, $invoice->getDataValue('firma')->showAs, \count($callsOrders)));

            $invoice->insertToAbraFlexi(['id' => $invoice, 'stavMailK' => 'stavMail.odeslat']);

            $orderHelper = $this->getOrderer();

            foreach ($callsOrders as $orderCode => $orderData) {
                $orderHelper->setData($orderData);
                //                $orderHelper->deleteFromAbraFlexi();

                // https://podpora.flexibee.eu/cs/articles/5917010-zamykani-obdobi-pres-rest-api

                $lockState = $orderHelper->locked();
                if($lockState){
                    $orderHelper->unlock();
                }
                
                if ($orderHelper->sync(['id' => \AbraFlexi\RO::code($orderCode), 'typDokl' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_ORDERTYPE', 'OBP_VOIP')), 'stavUzivK' => 'stavDoklObch.hotovo'])) {
                    $orderHelper->addStatusMessage(sprintf(_('%s Order %s marked as done'), $orderData['firma']->showAs, $orderCode), 'success');
                } else {
                    $orderHelper->addStatusMessage(sprintf(_('%s Order %s marked as done'), $orderData['firma']->showAs, $orderCode), 'error');
                }
                
                if($lockState){
                    $orderHelper->lock();
                }
                
            }
        }

        return $invoice;
    }

    /**
     * @param array<string, string> $forceData initial data
     */
    public function getInvoicer(array $forceData = []): \AbraFlexi\FakturaVydana
    {
        if (isset($this->invoicer) === false) {
            $this->invoicer = new \AbraFlexi\FakturaVydana($forceData);
        }

        return $this->invoicer;
    }

    /**
     * @param array<string, string> $forceData initial data
     */
    public function getOrderer(array $forceData = []): \AbraFlexi\ObjednavkaPrijata
    {
        if (isset($this->order) === false) {
            $this->order = new \AbraFlexi\ObjednavkaPrijata($forceData);
        } else {
            $this->order->dataReset();
            $this->order->setData($forceData);
        }

        return $this->order;
    }
}
