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
 * IPEX API Integration with AbraFlexi.
 *
 * Handles integration between IPEX VoIP service and AbraFlexi ERP system.
 * Processes postpaid calls, generates orders and invoices, and manages
 * customer communications with call lists.
 *
 * Features:
 * - IPEX API data retrieval with monthOffset support
 * - AbraFlexi order and invoice generation
 * - Timezone-aware date processing (UTC to Europe/Prague)
 * - Duplicate prevention for orders and invoices
 * - Email notifications with call lists
 * - PHP 8.4+ compatible with typed properties
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Ipex extends \Ease\Sand
{
    public float $invoicingLimit = 50.0;
    public FakturaVydana $invoicer;

    /**
     * Start date of the target period (nullable for flexible usage).
     */
    public ?\DateTime $since = null;

    /**
     * End date of the target period (nullable for flexible usage).
     */
    public ?\DateTime $until = null;
    private string $counter = '';
    private ObjednavkaPrijata $order;

    /**
     * List of IPEX users indexed by external code.
     *
     * @var array<int|string, mixed>
     */
    private array $ipexUsers = [];

    public function __construct()
    {
        $this->setObjectName();
    }

    /**
     * Get the date of the last generated order.
     *
     * Used in continue mode to determine the next period to process.
     * Scans all prepared orders to find the latest order date.
     *
     * @return null|\DateTime The date of the last generated order, or null if no orders exist
     */
    public function getLastOrderDate(): ?\DateTime
    {
        $orders = $this->getUsersPreparedOrders();
        $lastDate = null;

        foreach ($orders as $order) {
            if (!empty($order['datVyst'])) {
                $dateValue = $order['datVyst'];

                if ($dateValue instanceof \AbraFlexi\Date) {
                    $dateValue = method_exists($dateValue, 'format') ? $dateValue->format('Y-m-d H:i:s') : (string) $dateValue;
                }

                $orderDate = new \DateTime((string) $dateValue);

                if ($lastDate === null || $orderDate > $lastDate) {
                    $lastDate = $orderDate;
                }
            }
        }

        return $lastDate;
    }

    /**
     * Check if order already exists for given customer and date range.
     *
     * @param string    $customerExtId External customer ID
     * @param \DateTime $startDate     Start date of the period
     * @param \DateTime $endDate       End date of the period
     *
     * @return bool True if order exists, false otherwise
     */
    public function orderExistsForPeriod(string $customerExtId, \DateTime $startDate, \DateTime $endDate): bool
    {
        $orderer = $this->getOrderer();
        $orderer->defaultUrlParams['order'] = 'datVyst@A';
        $orderer->defaultUrlParams['limit'] = 0;

        $conds = [
            'storno' => false,
            'firma' => $customerExtId,
            'typDokl' => \Ease\Shared::cfg('ABRAFLEXI_ORDERTYPE', 'code:OBP_VOIP'),
            // Check for orders that overlap with the period
            'datVyst' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
        ];

        $existingOrders = $orderer->getColumnsFromAbraFlexi(
            ['kod', 'datVyst', 'popis'],
            $conds,
            'id',
        );

        if ($orderer->lastResponseCode === 200 && !empty($existingOrders)) {
            // Check if any existing order covers the same period by examining the description
            foreach ($existingOrders as $order) {
                $popis = $order['popis'] ?? '';

                // Check if description contains dates that match our target period
                if (strpos($popis, self::formatDate($startDate)) !== false
                    && strpos($popis, self::formatDate($endDate)) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if invoice already exists for given customer and period.
     *
     * @param string    $customerExtId External customer ID
     * @param \DateTime $startDate     Start date of the period
     * @param \DateTime $endDate       End date of the period
     *
     * @return bool True if invoice exists, false otherwise
     */
    public function invoiceExistsForPeriod(string $customerExtId, \DateTime $startDate, \DateTime $endDate): bool
    {
        $invoicer = $this->getInvoicer();
        $invoicer->defaultUrlParams['order'] = 'datVyst@A';
        $invoicer->defaultUrlParams['limit'] = 0;

        $conds = [
            'storno' => false,
            'firma' => $customerExtId,
            'typDokl' => \Ease\Shared::cfg('ABRAFLEXI_DOCTYPE', \AbraFlexi\Code::ensure('FAKTURA')),
            // Check for invoices in the target month
            'datVyst' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
        ];

        $existingInvoices = $invoicer->getColumnsFromAbraFlexi(
            ['kod', 'datVyst', 'popis'],
            $conds,
            'id',
        );

        if ($invoicer->lastResponseCode === 200 && !empty($existingInvoices)) {
            // Check if any existing invoice covers the same period by examining the description
            foreach ($existingInvoices as $invoice) {
                $popis = $invoice['popis'] ?? '';

                // Check if description contains dates that match our target period
                if (strpos($popis, self::formatDate($startDate)) !== false
                    && strpos($popis, self::formatDate($endDate)) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get IPEX invoices data for a specific period.
     *
     * Retrieves invoice data from IPEX API for a given month offset and filters
     * the results to include only invoices from the target month. Handles timezone
     * conversion from UTC to Europe/Prague for accurate month matching.
     *
     * Features:
     * - Sets $this->since and $this->until properties for the target period
     * - Automatically converts monthOffset (negative values for past months)
     * - Filters API results to specific target month only
     * - Handles timezone conversion for accurate date comparisons
     *
     * @param array $periodOptions Options array with 'monthOffset' key (default: -1)
     *                             monthOffset should be negative for past months
     *
     * @return array<int, array<string, string>>|bool Array of filtered invoice data or false on error
     */
    public function getIpexInvoices($periodOptions = []): array|bool
    {
        $grabber = new \IPEXB2B\ApiClient('', ['section' => 'invoices']);

        $monthOffset = $periodOptions['monthOffset'] ?? -1;

        // Set target month boundaries for filtering
        // monthOffset is always negative - we work with one specific month from the past
        $this->since = new \DateTime('first day of this month');
        $this->since->modify((string) $monthOffset.' month');
        $this->until = clone $this->since;
        $this->until->modify('last day of this month');

        // IPEX API provides records from monthOffset to now, but we need only the specific target month
        // Use monthOffset to get data that includes our target month
        $grabber->setUrlParams(['monthOffset' => $monthOffset]);

        $this->invoicingLimit = (float) \Ease\Shared::cfg('ABRAFLEXI_MINIMAL_INVOICING', 50);

        $rawInvoices = $grabber->requestData('postpaid');

        // Filter to include only invoices that belong to our specific target month
        if (\is_array($rawInvoices)) {
            $filteredInvoices = [];

            foreach ($rawInvoices as $invoice) {
                // Convert UTC dates to local timezone for proper month comparison
                $invoiceStart = new \DateTime($invoice['dateStart']);
                $invoiceStart->setTimezone(new \DateTimeZone('Europe/Prague'));

                // Check if the invoice period belongs to our target month
                // We compare the year and month of the invoice start date with our target month
                $targetYear = (int) $this->since->format('Y');
                $targetMonth = (int) $this->since->format('n');
                $invoiceYear = (int) $invoiceStart->format('Y');
                $invoiceMonth = (int) $invoiceStart->format('n');

                if ($targetYear === $invoiceYear && $targetMonth === $invoiceMonth) {
                    $filteredInvoices[] = $invoice;
                }
            }

            return $filteredInvoices;
        }

        return $rawInvoices;
    }

    /**
     * Obtain Customer List.
     *
     * @return array<int, array<string, string>>
     */
    public function getIpexCustomersByExtCode()
    {
        $customers = new \IPEXB2B\Customers();

        return \Ease\Functions::reindexArrayBy($customers->requestData(), 'externId');
    }

    /**
     * Get Postpaid Calls and save it to Orders.
     *
     * @param array<int, array<string, string>> $invoicesRaw Array of raw invoice data
     *
     * @return array<string, array<string, mixed>> Array indexed by externId with detailed order info
     */
    public function processIpexPostpaidOrders(array $invoicesRaw): array
    {
        $position = 0;
        $result = [];
        $summary = [
            'processedCount' => 0,
            'createdCount' => 0,
            'skippedCount' => 0,
            'duplicateCount' => 0,
            'totalAmount' => 0.0,
            'processedPeriod' => [
                'from' => isset($this->since) ? $this->since->format('Y-m-d') : null,
                'to' => isset($this->until) ? $this->until->format('Y-m-d') : null,
            ],
            'processedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        $createdOrders = [];
        $skippedOrders = [];
        $duplicateOrders = [];

        foreach ($invoicesRaw as $invoiceRaw) {
            ++$summary['processedCount'];
            $customerExtId = (string) $invoiceRaw['externId'];
            $customerName = $invoiceRaw['customerName'] ?? $customerExtId;
            $price = (float) ($invoiceRaw['price'] ?? 0);

            $result[$customerExtId] = [
                'customerName' => $customerName,
                'ipexCustomerId' => $invoiceRaw['customerId'] ?? null,
                'price' => $price,
                'dateStart' => $invoiceRaw['dateStart'] ?? null,
                'dateEnd' => $invoiceRaw['dateEnd'] ?? null,
                'status' => 'processing',
            ];

            $this->counter = '#'.++$position.'/'.\count($invoicesRaw).' ';

            // Check for duplicates first
            $startDate = new \DateTime($invoiceRaw['dateStart']);
            $endDate = new \DateTime($invoiceRaw['dateEnd']);

            if ($this->orderExistsForPeriod($customerExtId, $startDate, $endDate)) {
                ++$summary['duplicateCount'];
                $result[$customerExtId]['status'] = 'duplicate';
                $result[$customerExtId]['order'] = 'Already exists';
                $result[$customerExtId]['message'] = 'Order already exists for this period';

                $duplicateOrders[] = [
                    'customerExtId' => $customerExtId,
                    'customerName' => $customerName,
                    'price' => $price,
                    'period' => $startDate->format('Y-m-d').' to '.$endDate->format('Y-m-d'),
                    'reason' => 'Duplicate order for period',
                ];

                continue;
            }

            $order = $this->createOrder($invoiceRaw);

            if ($order->getRecordId()) {
                ++$summary['createdCount'];
                $orderCode = $order->getRecordCode();
                $orderAmount = (float) $order->getDataValue('sumZklZakl');
                $summary['totalAmount'] += $orderAmount;

                $result[$customerExtId]['status'] = 'created';
                $result[$customerExtId]['order'] = $orderCode;
                $result[$customerExtId]['amount'] = $orderAmount;
                $result[$customerExtId]['orderUrl'] = $order->getApiUrl();
                $result[$customerExtId]['createdAt'] = (new \DateTime())->format('Y-m-d H:i:s');

                $createdOrders[] = [
                    'orderCode' => $orderCode,
                    'customerExtId' => $customerExtId,
                    'customerName' => $customerName,
                    'amount' => $orderAmount,
                    'price' => $price,
                    'period' => $startDate->format('Y-m-d').' to '.$endDate->format('Y-m-d'),
                    'orderUrl' => $order->getApiUrl(),
                    'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                ];
            } else {
                ++$summary['skippedCount'];
                $result[$customerExtId]['status'] = 'skipped';
                $result[$customerExtId]['order'] = _('No Calls');
                $result[$customerExtId]['message'] = 'No calls or zero amount';

                $skippedOrders[] = [
                    'customerExtId' => $customerExtId,
                    'customerName' => $customerName,
                    'price' => $price,
                    'period' => $startDate->format('Y-m-d').' to '.$endDate->format('Y-m-d'),
                    'reason' => $price === 0 ? 'Zero amount' : 'No calls',
                ];
            }
        }

        // Add audit summary to result
        $result['_audit'] = [
            'summary' => $summary,
            'createdOrders' => $createdOrders,
            'skippedOrders' => $skippedOrders,
            'duplicateOrders' => $duplicateOrders,
        ];

        return $result;
    }

    /**
     * Process AbraFlexi Orders to AbraFlexi Invoices.
     *
     * @return array<string, array> Enhanced result with detailed audit information
     */
    public function processIpexPostpaidInvoices(): array
    {
        $this->ipexUsers = $this->getIpexCustomersByExtCode();
        $allUsersCalls = $this->getUnivoicedCalls();
        $callsByCustomer = [];
        $result = [];

        // Enhanced audit tracking
        $summary = [
            'processedCount' => 0,
            'createdCount' => 0,
            'skippedCount' => 0,
            'belowLimitCount' => 0,
            'skipListCount' => 0,
            'duplicateCount' => 0,
            'noCustomerCount' => 0,
            'totalInvoicedAmount' => 0.0,
            'totalBelowLimitAmount' => 0.0,
            'invoicingLimit' => $this->invoicingLimit,
            'processedPeriod' => [
                'from' => isset($this->since) ? $this->since->format('Y-m-d') : null,
                'to' => isset($this->until) ? $this->until->format('Y-m-d') : null,
            ],
            'processedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $createdInvoices = [];
        $skippedInvoices = [];
        $belowLimitInvoices = [];
        $duplicateInvoices = [];
        $noCustomerOrders = [];

        if ($this->uninvoicedAmount($allUsersCalls)) {
            foreach ($allUsersCalls as $call) {
                $callsByCustomer[(string) $call['firma']][$call['kod']] = $call;
            }

            foreach ($callsByCustomer as $customer => $calls) {
                ++$summary['processedCount'];
                $customerCode = \AbraFlexi\Functions::uncode($customer);
                $uninvoicedAmount = $this->uninvoicedAmount($calls);
                $customerName = $this->ipexUsers[$customer]['name'] ?? $customerCode ?? $customer;

                $result[$customer] = [
                    'customerCode' => $customerCode,
                    'customerName' => $customerName,
                    'orderCount' => \count($calls),
                    'uninvoicedAmount' => $uninvoicedAmount,
                    'status' => 'processing',
                ];

                if (\array_key_exists($customer, $this->ipexUsers)) {
                    if ($customerCode) {
                        // Check if customer is in skip list
                        if (!empty(\Ease\Shared::cfg('ABRAFLEXI_SKIPLIST'))
                            && (strstr(\Ease\Shared::cfg('ABRAFLEXI_SKIPLIST', ''), $customerCode) !== false)) {
                            ++$summary['skipListCount'];
                            $result[$customer]['status'] = 'skipped_skiplist';
                            $result[$customer]['invoice'] = 'in ABRAFLEXI_SKIPLIST';
                            $result[$customer]['reason'] = 'Customer in skip list';

                            $skippedInvoices[] = [
                                'customerCode' => $customerCode,
                                'customerName' => $customerName,
                                'amount' => $uninvoicedAmount,
                                'orderCount' => \count($calls),
                                'reason' => 'Customer in ABRAFLEXI_SKIPLIST',
                            ];

                            continue;
                        }

                        // Check if amount meets threshold
                        if ($uninvoicedAmount > $this->invoicingLimit) {
                            // Check for duplicate invoice first
                            if (isset($this->since, $this->until)
                                && $this->invoiceExistsForPeriod($customer, $this->since, $this->until)) {
                                ++$summary['duplicateCount'];
                                $result[$customer]['status'] = 'duplicate';
                                $result[$customer]['invoice'] = 'Already exists';
                                $result[$customer]['reason'] = 'Invoice already exists for this period';

                                $duplicateInvoices[] = [
                                    'customerCode' => $customerCode,
                                    'customerName' => $customerName,
                                    'amount' => $uninvoicedAmount,
                                    'orderCount' => \count($calls),
                                    'period' => isset($this->since) && isset($this->until) ?
                                        $this->since->format('Y-m-d').' to '.$this->until->format('Y-m-d') : 'Unknown',
                                    'reason' => 'Duplicate invoice for period',
                                ];
                            } else {
                                // Create invoice
                                $invoice = $this->createInvoice($calls);
                                $invoiceCode = $invoice->getRecordCode();

                                if ($invoiceCode) {
                                    ++$summary['createdCount'];
                                    $invoiceAmount = (float) $invoice->getDataValue('sumCelkem');
                                    $summary['totalInvoicedAmount'] += $invoiceAmount;

                                    $result[$customer]['status'] = 'created';
                                    $result[$customer]['invoice'] = $invoiceCode;
                                    $result[$customer]['invoiceAmount'] = $invoiceAmount;
                                    $result[$customer]['invoiceUrl'] = $invoice->getApiUrl();
                                    $result[$customer]['createdAt'] = (new \DateTime())->format('Y-m-d H:i:s');

                                    $createdInvoices[] = [
                                        'invoiceCode' => $invoiceCode,
                                        'customerCode' => $customerCode,
                                        'customerName' => $customerName,
                                        'amount' => $invoiceAmount,
                                        'orderCount' => \count($calls),
                                        'orderCodes' => array_keys($calls),
                                        'period' => isset($this->since) && isset($this->until) ?
                                            $this->since->format('Y-m-d').' to '.$this->until->format('Y-m-d') : 'Unknown',
                                        'invoiceUrl' => $invoice->getApiUrl(),
                                        'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                                    ];
                                } else {
                                    ++$summary['skippedCount'];
                                    $result[$customer]['status'] = 'failed';
                                    $result[$customer]['invoice'] = 'Creation failed';
                                    $result[$customer]['reason'] = 'Invoice creation failed';
                                }
                            }
                        } else {
                            ++$summary['belowLimitCount'];
                            $summary['totalBelowLimitAmount'] += $uninvoicedAmount;

                            $result[$customer]['status'] = 'below_limit';
                            $result[$customer]['invoice'] = $uninvoicedAmount.' < '.$this->invoicingLimit;
                            $result[$customer]['reason'] = 'Amount below invoicing limit';

                            $belowLimitInvoices[] = [
                                'customerCode' => $customerCode,
                                'customerName' => $customerName,
                                'amount' => $uninvoicedAmount,
                                'limit' => $this->invoicingLimit,
                                'orderCount' => \count($calls),
                                'orderCodes' => array_keys($calls),
                            ];
                        }
                    } else {
                        ++$summary['noCustomerCount'];
                        $this->addStatusMessage(_('Unknown AbraFlexi customer. No invoice created.'), 'warning');

                        $result[$customer]['status'] = 'no_customer';
                        $result[$customer]['invoice'] = 'Unknown customer';
                        $result[$customer]['reason'] = 'Customer code could not be resolved';

                        foreach ($calls as $call) {
                            $noCustomerOrders[] = [
                                'orderCode' => $call['kod'],
                                'customer' => $customer,
                                'amount' => (float) $call['sumCelkem'],
                                'reason' => 'Unknown AbraFlexi customer',
                            ];
                        }
                    }
                } else {
                    ++$summary['noCustomerCount'];
                    $this->addStatusMessage(sprintf(_('Ipex Customer Without externalId: %s'), $customer), 'warning');

                    $result[$customer]['status'] = 'not_ipex_customer';
                    $result[$customer]['invoice'] = sprintf(_('Not an Ipex customer: %s ?'), $customer);
                    $result[$customer]['reason'] = 'Customer not found in IPEX customer list';

                    foreach ($calls as $call) {
                        $noCustomerOrders[] = [
                            'orderCode' => $call['kod'],
                            'customer' => $customer,
                            'amount' => (float) $call['sumCelkem'],
                            'reason' => 'Not an IPEX customer',
                        ];
                    }
                }
            }
        } else {
            $summary['processedCount'] = 0;
        }

        // Add enhanced audit summary to result
        $result['_audit'] = [
            'summary' => $summary,
            'createdInvoices' => $createdInvoices,
            'skippedInvoices' => $skippedInvoices,
            'belowLimitInvoices' => $belowLimitInvoices,
            'duplicateInvoices' => $duplicateInvoices,
            'noCustomerOrders' => $noCustomerOrders,
        ];

        // Maintain backward compatibility
        if (!empty($noCustomerOrders)) {
            $result['nocustomer'] = array_column($noCustomerOrders, 'orderCode');
        }

        return $result;
    }

    /**
     * Get IPEX orders not in "done" state.
     *
     * @param null|string $klientExtID Description
     *
     * @return array<int, array<string, mixed>> Orders with IPEX_POSTPAID item
     */
    public function getUnivoicedCalls(?string $klientExtID = null): array
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
     * @param null|string $klientExtID Description
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUsersPreparedOrders(?string $klientExtID = null)
    {
        $result = [];
        $this->getOrderer()->defaultUrlParams['order'] = 'datVyst@A';
        $this->getOrderer()->defaultUrlParams['limit'] = 0;

        $conds = [
            'storno' => false,
            'stavUzivK' => 'stavDoklObch.pripraveno',
            'typDokl' => \Ease\Shared::cfg('ABRAFLEXI_ORDERTYPE', 'code:OBP_VOIP')];

        if ($klientExtID) {
            $conds['firma'] = $klientExtID;
        }

        // If we have a target period defined (since/until), filter by date range
        if (isset($this->since, $this->until)) {
            $conds['datVyst'] = [$this->since->format('Y-m-d'), $this->until->format('Y-m-d')];
        }

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
            $conds,
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
     */
    public function uninvoicedAmount($ordersData): float
    {
        $amount = 0.0;

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
    public function createOrder(array $invoiceRaw)
    {
        $adresar = new \AbraFlexi\Adresar();
        $startDate = new \DateTime($invoiceRaw['dateStart']);
        $endDate = new \DateTime($invoiceRaw['dateEnd']);

        // Check if order already exists for this customer and period
        if ($this->orderExistsForPeriod($invoiceRaw['externId'], $startDate, $endDate)) {
            $this->addStatusMessage(
                $this->counter.$invoiceRaw['customerName'].' - Order already exists for period '.self::formatDate($startDate).' - '.self::formatDate($endDate),
                'info',
            );

            // Return empty order object
            return $this->getOrderer();
        }

        $order = $this->getOrderer([
            'typDokl' => \Ease\Shared::cfg('ABRAFLEXI_ORDERTYPE', 'code:OBP_VOIP'),
            'stavUzivK' => 'stavDoklObch.pripraveno',
            'zaokrNaSumK' => 'zaokrNa.zadne',
            'zaokrNaDphK' => 'zaokrNa.zadne',
            'datVyst' => isset($this->since) ? $this->since->format('Y-m-d') : $startDate->format('Y-m-d'), // Set order date to beginning of target month
            'popis' => _('IPEX Postpaid'),
        ]);

        if ($adresar->recordExists(\AbraFlexi\Code::ensure($invoiceRaw['externId']))) {
            $order->setDataValue('firma', \AbraFlexi\Code::ensure($invoiceRaw['externId']));
        } else {
            $order->setDataValue('nazFirmy', $invoiceRaw['customerName']);
            $order->setDataValue('ulice', $invoiceRaw['street']);
            $order->setDataValue('psc', $invoiceRaw['zipCode']);
            $order->setDataValue('mesto', $invoiceRaw['city']);
            $order->setDataValue('datObj', $invoiceRaw['datetime']);
            $order->setDataValue(
                'poznam',
                'Ipex: '.$invoiceRaw['customerId'].' '.$invoiceRaw['note']
            .(\Ease\Shared::cfg('MULTIFLEXI_JOB_ID', '') ? "\nJob ID: ".\Ease\Shared::cfg('MULTIFLEXI_JOB_ID', '') : ''),
            );
        }

        $pricelistItem = [
            'cenaMj' => $invoiceRaw['price'],
            'nazev' => 'Telefonní služby od '.self::formatDate($startDate).' do '.self::formatDate($endDate),
            'cenik' => \AbraFlexi\Code::ensure(\Ease\Shared::cfg('ABRAFLEXI_PRODUCT', 'IPEX_POSTPAID')),
            'stitky' => 'API_IPEX',
        ];
        $order->setDataValue('popis', $pricelistItem['nazev']);

        if (strtolower(\Ease\Shared::cfg('ABRAFLEXI_CREATE_EMPTY_ORDERS', 'true')) === 'true' || (float) $invoiceRaw['price']) {
            $order->addArrayToBranch($pricelistItem, 'polozkyDokladu');

            $order->addStatusMessage(
                $this->counter.$invoiceRaw['customerName'].' '.$invoiceRaw['price'].' CZK ',
                $order->sync() ? 'success' : 'error',
            );

            // Check what actions are enabled for call list processing
            $attachPdfToOrder = strtolower(\Ease\Shared::cfg('ATTACH_CALL_LIST_PDF', 'true')) === 'true';
            $sendByEmail = strtolower(\Ease\Shared::cfg('SEND_CALL_LIST_EMAIL', 'true')) === 'true';

            // Generate PDF only if it will be used (attached to order OR sent by email)
            $shouldGeneratePdf = $attachPdfToOrder || $sendByEmail;

            if ($shouldGeneratePdf) {
                $pdfCallLog = $this->pdfCallLog((int) $invoiceRaw['customerId'], $order->getDataValue('nazFirmy'));

                $callLogFilename = sys_get_temp_dir().'/'.str_replace(
                    [' ', ':'],
                    ['_', '-'],
                    \Ease\Functions::rip($order->getDataValue('popis')),
                ).'.pdf';

                file_put_contents($callLogFilename, $pdfCallLog);

                // Attach to order only if enabled
                if ($attachPdfToOrder) {
                    \AbraFlexi\Priloha::addAttachmentFromFile($order, $callLogFilename);
                }

                // Send by email only if enabled
                if ($sendByEmail) {
                    $this->sendCallListByMail($order, $callLogFilename);
                }

                unlink($callLogFilename);
            } else {
                $this->addStatusMessage('PDF call list generation skipped (not needed for attachment or email)', 'info');
            }
        } else {
            $order->addStatusMessage($this->counter.$invoiceRaw['customerName'].' 0,-', 'debug');
        }

        return $order;
    }

    public static function formatDate($dateTime)
    {
        return $dateTime->format('m. d. Y');
    }

    /**
     * Gives you call log as PDF.
     *
     * @param int    $ipexCustomerID internal IPEX id
     * @param string $customerName   to use in filename
     * @param int    $offset         months back to include in list (deprecated, uses $this->since/$this->until if available)
     *
     * @return string binary PDF
     */
    public function pdfCallLog(int $ipexCustomerID, string $customerName, int $offset = 1)
    {
        $caller = new \IPEXB2B\Calls();

        // Use the target period if available, otherwise fall back to offset calculation
        if (isset($this->since, $this->until)) {
            $startDate = clone $this->since;
            $endDate = clone $this->until;
        } else {
            $startDate = new \DateTime('first day of this month');
            $startDate->modify(' -'.$offset.' month');
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');
        }

        $calls = $caller->getCallsForCustomer($startDate, $ipexCustomerID);

        $report = new \Ease\Container(new \Ease\Html\H2Tag(_('Calls listing')));
        $report->addItem(new \Ease\Html\PTag(
            self::formatDate($startDate).' - '.self::formatDate($endDate),
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
     * @param int    $offset         months (deprecated, uses $this->since/$this->until if available)
     *
     * @return string saved callLog filename
     */
    public function savePdfCallLog($ipexCustomerID, $customerName, $offset = 1)
    {
        // Use the target period if available, otherwise fall back to offset calculation
        if (isset($this->since, $this->until)) {
            $startDate = clone $this->since;
            $endDate = clone $this->until;
        } else {
            $startDate = new \DateTime();
            $startDate->modify(' -'.$offset.' month');
            $endDate = new \DateTime();
        }

        $pdfFilename = sys_get_temp_dir().'/'.urlencode(str_replace(
            ' ',
            '_',
            \Ease\Functions::rip($customerName),
        )).'_'._('Calls').'_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.pdf';

        return file_put_contents(
            $pdfFilename,
            $this->pdfCallLog($ipexCustomerID, $pdfFilename, $offset),
        ) ? $pdfFilename : '';
    }

    /**
     * Add call log as items to the given order.
     *
     * @param FakturaVydana $order      the order to add call log items to
     * @param int           $customerId the raw invoice data containing customerId
     */
    public function addCallLogAsItems($order, array $customerId): void
    {
        $caller = new \IPEXB2B\Calls();

        // Use the target period for call retrieval
        $calls = $caller->getCallsForCustomer(
            $this->since ?? new \DateTime('first day of last month'),
            $customerId,
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
     * @param \AbraFlexi\ObjednavkaVydana $order
     * @param string                      $callLogFilename PDF File path
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
     * @param array<string, array<string, mixed>> $callsOrders Array of order data indexed by order code
     */
    public function createInvoice(array $callsOrders): FakturaVydana
    {
        $firstOrder = current($callsOrders);
        $customerExtId = (string) $firstOrder['firma'];

        // Check if invoice already exists for this customer and period
        if (isset($this->since, $this->until) && $this->invoiceExistsForPeriod($customerExtId, $this->since, $this->until)) {
            $this->addStatusMessage(
                $this->counter.$customerExtId.' - Invoice already exists for period '.self::formatDate($this->since).' - '.self::formatDate($this->until),
                'info',
            );

            // Return empty invoice object
            return new FakturaVydana();
        }

        $invoice = new FakturaVydana();
        $invoice->setDataValue('typDokl', \Ease\Shared::cfg('ABRAFLEXI_DOCTYPE', \AbraFlexi\Code::ensure('FAKTURA')));

        $invoice->setDataValue('stavMailK', strtolower(\Ease\Shared::cfg('ABRAFLEXI_SEND', 'false')) === 'true' ? 'stavMail.odeslat' : 'stavMail.neodesilat');
        $invoice->setDataValue('firma', \AbraFlexi\Code::ensure($customerExtId));
        $invoice->setDataValue('typUcOp', \AbraFlexi\Code::ensure('TRŽBA SLUŽBY INT'));

        foreach ($callsOrders as $orderCode => $orderData) {
            if (isset($this->since) === false || $this->since > $orderData['datVyst']) {
                $this->since = $orderData['datVyst'];
            }

            if (isset($this->until) === false || $this->until < $orderData['datVyst']) {
                $this->until = $orderData['datVyst'];
            }

            if (!empty($orderData['polozkyDokladu'])) {
                foreach ($orderData['polozkyDokladu'] as $orderItem) {
                    if ($orderItem['kod'] === \AbraFlexi\Code::strip(\Ease\Shared::cfg('ABRAFLEXI_PRODUCT', 'IPEX_POSTPAID'))) {
                        unset($orderItem['id'], $orderItem['kod']);

                        $orderItem['cenik'] = \AbraFlexi\Code::ensure(\Ease\Shared::cfg('ABRAFLEXI_PRODUCT', 'IPEX_POSTPAID'));
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

        if (isset($this->since)) {
            $startDate = clone $this->since;
            $startDate->modify(' -'.\count($callsOrders).' month')->modify('first day of next month');
        }

        $invoice->setDataValue(
            'popis',
            isset($this->since, $this->until) ?
                'Telefonní služby '._('from').' '.self::formatDate($this->since).' '._('to').' '.self::formatDate($this->until) :
                'Telefonní služby',
        );

        $invoice->setDataValue('uvodTxt', 'Fakturujeme Vám hlasové služby');

        if (isset($this->until)) {
            $invoice->setDataValue('duzpPuv', \AbraFlexi\Date::fromDateTime($this->until));
        }

        if ($invoice->sync()) {
            $invoice->addStatusMessage(
                $this->counter.$invoice->getDataValue('firma')->showAs.' '.$invoice->getDataValue('sumCelkem').' CZK ',
                'success',
            );

            $ipexCustomerID = (int) $this->ipexUsers[$this->getDataValue('firma')]['id'];

            \AbraFlexi\Priloha::addAttachmentFromFile($invoice, $this->savePdfCallLog($ipexCustomerID, $invoice->getDataValue('firma')->showAs, \count($callsOrders)));

            $invoice->insertToAbraFlexi(['id' => $invoice, 'stavMailK' => 'stavMail.odeslat']);

            $orderHelper = $this->getOrderer();

            foreach ($callsOrders as $orderCode => $orderData) {
                $orderHelper->setData($orderData);
                //                $orderHelper->deleteFromAbraFlexi();
                // https://podpora.flexibee.eu/cs/articles/5917010-zamykani-obdobi-pres-rest-api

                $lockState = $orderHelper->locked();

                if ($lockState) {
                    $orderHelper->unlock();
                }

                if ($orderHelper->sync(['id' => \AbraFlexi\Code::ensure($orderCode), 'typDokl' => \AbraFlexi\Code::ensure(\Ease\Shared::cfg('ABRAFLEXI_ORDERTYPE', 'OBP_VOIP')), 'stavUzivK' => 'stavDoklObch.hotovo'])) {
                    $orderHelper->addStatusMessage(sprintf(_('%s Order %s marked as done'), $orderData['firma']->showAs, $orderCode), 'success');
                } else {
                    $orderHelper->addStatusMessage(sprintf(_('%s Order %s marked as done'), $orderData['firma']->showAs, $orderCode), 'error');
                }

                if ($lockState) {
                    $orderHelper->lock();
                }
            }
        }

        return $invoice;
    }

    /**
     * @param array<string, string> $forceData initial data
     */
    public function getInvoicer(array $forceData = []): FakturaVydana
    {
        if (isset($this->invoicer) === false) {
            $this->invoicer = new FakturaVydana($forceData);
        }

        return $this->invoicer;
    }

    /**
     * @param array<string, string> $forceData initial data
     */
    public function getOrderer(array $forceData = []): ObjednavkaPrijata
    {
        if (isset($this->order) === false) {
            $this->order = new ObjednavkaPrijata($forceData);
        } else {
            $this->order->dataReset();
            $this->order->setData($forceData);
        }

        return $this->order;
    }

    /**
     * Display audit summary for orders or invoices.
     *
     * @param array  $report The report array with _audit section
     * @param string $type   Type of report ('orders' or 'invoices')
     */
    public function displayAuditSummary(array $report, string $type = 'orders'): void
    {
        if (!isset($report['_audit'])) {
            $this->addStatusMessage('No audit information found in report', 'warning');

            return;
        }

        $audit = $report['_audit'];
        $summary = $audit['summary'];

        $this->addStatusMessage('=== AUDIT SUMMARY FOR '.strtoupper($type).' ===', 'info');
        $this->addStatusMessage(sprintf('Processed: %d items', $summary['processedCount']), 'info');

        if ($type === 'orders') {
            $this->addStatusMessage(sprintf(
                'Created: %d orders (%.2f CZK)',
                $summary['createdCount'],
                $summary['totalAmount'],
            ), 'success');
            $this->addStatusMessage(sprintf('Skipped: %d orders', $summary['skippedCount']), 'info');
            $this->addStatusMessage(sprintf('Duplicates: %d orders', $summary['duplicateCount']), 'warning');

            if (!empty($audit['createdOrders'])) {
                $this->addStatusMessage('--- CREATED ORDERS ---', 'info');

                foreach ($audit['createdOrders'] as $order) {
                    $this->addStatusMessage(sprintf(
                        '%s: %s (%.2f CZK) - Period: %s',
                        $order['orderCode'],
                        $order['customerName'],
                        $order['amount'],
                        $order['period'],
                    ), 'success');
                }
            }
        } else {
            $this->addStatusMessage(sprintf(
                'Created: %d invoices (%.2f CZK)',
                $summary['createdCount'],
                $summary['totalInvoicedAmount'],
            ), 'success');
            $this->addStatusMessage(sprintf(
                'Below limit: %d (%.2f CZK < %.2f)',
                $summary['belowLimitCount'],
                $summary['totalBelowLimitAmount'],
                $summary['invoicingLimit'],
            ), 'info');
            $this->addStatusMessage(sprintf('Skip list: %d customers', $summary['skipListCount']), 'info');
            $this->addStatusMessage(sprintf('Duplicates: %d invoices', $summary['duplicateCount']), 'warning');
            $this->addStatusMessage(sprintf('No customer: %d orders', $summary['noCustomerCount']), 'warning');

            if (!empty($audit['createdInvoices'])) {
                $this->addStatusMessage('--- CREATED INVOICES ---', 'info');

                foreach ($audit['createdInvoices'] as $invoice) {
                    $this->addStatusMessage(sprintf(
                        '%s: %s (%.2f CZK) - %d orders - Period: %s',
                        $invoice['invoiceCode'],
                        $invoice['customerName'],
                        $invoice['amount'],
                        $invoice['orderCount'],
                        $invoice['period'],
                    ), 'success');
                }
            }
        }

        if (isset($summary['processedPeriod']) && $summary['processedPeriod']['from']) {
            $this->addStatusMessage(sprintf(
                'Period: %s to %s',
                $summary['processedPeriod']['from'],
                $summary['processedPeriod']['to'],
            ), 'info');
        }

        $this->addStatusMessage(sprintf('Processed at: %s', $summary['processedAt']), 'info');
        $this->addStatusMessage('=== END AUDIT SUMMARY ===', 'info');
    }

    /**
     * Generate MultiFlexi-compliant report from audit data.
     *
     * @param array  $report   The detailed audit report
     * @param string $type     Type of operation ('orders' or 'invoices')
     * @param int    $exitCode Exit code from the operation
     *
     * @return array MultiFlexi-compliant report structure
     */
    public function generateMultiFlexiReport(array $report, string $type = 'orders', int $exitCode = 0): array
    {
        if (!isset($report['_audit'])) {
            return [
                'status' => 'error',
                'timestamp' => (new \DateTime())->format('c'),
                'message' => 'No audit data available in report',
                'metrics' => [
                    'exit_code' => $exitCode,
                ],
            ];
        }

        $audit = $report['_audit'];
        $summary = $audit['summary'];

        // Determine overall status
        $status = 'success';

        if ($exitCode !== 0) {
            $status = 'error';
        } elseif (($summary['duplicateCount'] ?? 0) > 0 || ($summary['skippedCount'] ?? 0) > 0) {
            $status = 'warning';
        }

        // Generate human-readable message
        if ($type === 'orders') {
            $message = sprintf(
                'Processed %d IPEX invoices for period %s to %s. Created %d orders (%.2f CZK), skipped %d, found %d duplicates.',
                $summary['processedCount'],
                $summary['processedPeriod']['from'] ?? 'unknown',
                $summary['processedPeriod']['to'] ?? 'unknown',
                $summary['createdCount'],
                $summary['totalAmount'] ?? 0,
                $summary['skippedCount'] ?? 0,
                $summary['duplicateCount'] ?? 0,
            );
        } else {
            $message = sprintf(
                'Processed %d customers for invoicing. Created %d invoices (%.2f CZK), %d below limit (%.2f CZK), %d skipped, %d duplicates.',
                $summary['processedCount'],
                $summary['createdCount'],
                $summary['totalInvoicedAmount'] ?? 0,
                $summary['belowLimitCount'] ?? 0,
                $summary['totalBelowLimitAmount'] ?? 0,
                $summary['skipListCount'] ?? 0,
                $summary['duplicateCount'] ?? 0,
            );
        }

        // Prepare artifacts - URLs of created documents
        $artifacts = [];

        if ($type === 'orders' && !empty($audit['createdOrders'])) {
            $artifacts['orders'] = array_column($audit['createdOrders'], 'orderUrl');
            $artifacts['orders'] = array_filter($artifacts['orders']); // Remove empty URLs
        } elseif ($type === 'invoices' && !empty($audit['createdInvoices'])) {
            $artifacts['invoices'] = array_column($audit['createdInvoices'], 'invoiceUrl');
            $artifacts['invoices'] = array_filter($artifacts['invoices']); // Remove empty URLs
        }

        // Prepare metrics
        $metrics = [
            'exit_code' => $exitCode,
            'processed_count' => $summary['processedCount'],
            'created_count' => $summary['createdCount'],
            'skipped_count' => $summary['skippedCount'] ?? 0,
            'duplicate_count' => $summary['duplicateCount'] ?? 0,
        ];

        if ($type === 'orders') {
            $metrics['total_amount'] = $summary['totalAmount'] ?? 0;
        } else {
            $metrics['total_invoiced_amount'] = $summary['totalInvoicedAmount'] ?? 0;
            $metrics['total_below_limit_amount'] = $summary['totalBelowLimitAmount'] ?? 0;
            $metrics['below_limit_count'] = $summary['belowLimitCount'] ?? 0;
            $metrics['skip_list_count'] = $summary['skipListCount'] ?? 0;
            $metrics['no_customer_count'] = $summary['noCustomerCount'] ?? 0;
            $metrics['invoicing_limit'] = $summary['invoicingLimit'] ?? 0;
        }

        // Add period information if available
        if (isset($summary['processedPeriod'])) {
            $metrics['period_from'] = $summary['processedPeriod']['from'];
            $metrics['period_to'] = $summary['processedPeriod']['to'];
        }

        return [
            'status' => $status,
            'timestamp' => (new \DateTime())->format('c'),
            'message' => $message,
            'artifacts' => $artifacts,
            'metrics' => $metrics,
        ];
    }
}
