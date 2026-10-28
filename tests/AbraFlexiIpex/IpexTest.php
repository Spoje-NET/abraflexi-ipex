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

namespace Test\SpojeNet\AbraFlexiIpex;

use PHPUnit\Framework\TestCase;
use SpojeNet\AbraFlexiIpex\Ipex;

/**
 * Class IPEX.
 *
 * @covers \SpojeNet\AbraFlexiIpex\IPEX
 */
class IpexTest extends TestCase
{
    /**
     * @var IPEX an instance of "IPEX" to test
     */
    private Ipex $object;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        /** @todo Maybe add some arguments to this constructor */
        $this->object = new \SpojeNet\AbraFlexiIpex\Ipex();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::getIpexInvoices
     */
    public function testGetIpexInvoices(): void
    {
        $this->assertArrayHasKey('0', $this->object->getIpexInvoices());
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::processIpexInvoices
     */
    public function testProcessIpexInvoices(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::getUnivoicedCalls
     */
    public function testGetUnivoicedCalls(): void
    {
        $allCalls = $this->object->getUnivoicedCalls();
        $this->assertIsArray($allCalls);

        $onlyOneUserCalls = $this->object->getUnivoicedCalls('code:666');
        $this->assertIsArray($onlyOneUserCalls);
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::getUsersPreparedOrders
     */
    public function testGetUsersPreparedOrders(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::foundLastInvoice
     */
    public function testFoundLastInvoice(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::uninvoicedAmount
     */
    public function testUninvoicedAmount(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::createOrder
     */
    public function testCreateOrder(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\Ipex::normalizeServicePeriod
     */
    public function testNormalizeServicePeriod(): void
    {
        [$since, $until] = Ipex::normalizeServicePeriod(
            new \DateTime('2025-08-31 00:00:00'),
            new \DateTime('2025-09-30 23:59:59'),
        );

        $this->assertSame('2025-09-01', $since->format('Y-m-d'));
        $this->assertSame('2025-09-30', $until->format('Y-m-d'));
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\Ipex::periodCoversRequired
     */
    public function testPeriodCoversRequired(): void
    {
        $this->assertTrue(Ipex::periodCoversRequired(
            new \DateTime('2025-09-01'),
            new \DateTime('2025-09-30'),
            new \DateTime('2025-09-01'),
            new \DateTime('2025-09-30'),
        ));

        $this->assertTrue(Ipex::periodCoversRequired(
            new \DateTime('2025-09-01'),
            new \DateTime('2025-09-30'),
            new \DateTime('2025-09-05'),
            new \DateTime('2025-09-20'),
        ));

        $this->assertFalse(Ipex::periodCoversRequired(
            new \DateTime('2025-09-02'),
            new \DateTime('2025-09-30'),
            new \DateTime('2025-09-01'),
            new \DateTime('2025-09-30'),
        ));
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\Ipex::periodDescriptionMatches
     */
    public function testPeriodDescriptionMatches(): void
    {
        $start = new \DateTime('2025-09-01');
        $end = new \DateTime('2025-09-30');

        $this->assertTrue(Ipex::periodDescriptionMatches(
            'Telefonní služby od 01.09.2025 do 30.09.2025',
            $start,
            $end,
        ));

        $this->assertTrue(Ipex::periodDescriptionMatches(
            'Telefonní služby od 09. 01. 2025 do 09. 30. 2025',
            $start,
            $end,
        ));

        $this->assertFalse(Ipex::periodDescriptionMatches(
            'Telefonní služby od 01.08.2025 do 31.08.2025',
            $start,
            $end,
        ));
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\Ipex::uniqueEmailRecipients
     */
    public function testUniqueEmailRecipients(): void
    {
        $this->assertSame(
            'a@example.com,b@example.com',
            Ipex::uniqueEmailRecipients('a@example.com, a@example.com; b@example.com a@example.com'),
        );

        $this->assertSame('', Ipex::uniqueEmailRecipients(' , ; '));
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\Ipex::resolveRequiredPeriod
     */
    public function testResolveRequiredPeriodPrefersConfiguredWindow(): void
    {
        $this->object->since = new \DateTime('2025-09-01');
        $this->object->until = new \DateTime('2025-09-30');

        [$since, $until] = $this->object->resolveRequiredPeriod(
            new \DateTime('2025-08-31'),
            new \DateTime('2025-09-30'),
        );

        $this->assertSame('2025-09-01', $since->format('Y-m-d'));
        $this->assertSame('2025-09-30', $until->format('Y-m-d'));
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::pdfCallLog
     */
    public function testPdfCallLog(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::addCallLogAsItems
     */
    public function testAddCallLogAsItems(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::sendOrderByMail
     */
    public function testSendOrderByMail(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::noIpexExtID
     */
    public function testNoIpexExtID(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::createInvoice
     */
    public function testCreateInvoice(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\AbraFlexiIpex\IPEX::getInvoicer
     */
    public function testGetInvoicer(): void
    {
        $this->assertIsObject($this->object->getInvoicer());
    }

    /**
     * Hidden duplicate IPEX customer must not shadow the visible one.
     */
    public function testIndexCustomersByExtIdPrefersVisible(): void
    {
        $indexed = Ipex::indexCustomersByExtId([
            ['id' => 34207, 'externId' => 'code:01284', 'hidden' => false, 'numberCount' => 8],
            ['id' => 32569, 'externId' => 'code:01284', 'hidden' => true, 'numberCount' => 0],
            ['id' => 1, 'externId' => 'code:00001', 'hidden' => false, 'numberCount' => 1],
        ]);

        $this->assertSame(34207, $indexed['code:01284']['id']);
        $this->assertSame(1, $indexed['code:00001']['id']);
    }

    public function testFilterCallsByPeriod(): void
    {
        $calls = [
            ['datetime' => '2026-04-30 23:59:59'],
            ['datetime' => '2026-05-01 00:00:00'],
            ['datetime' => '2026-05-31 12:00:00'],
            ['datetime' => '2026-06-01 00:00:00'],
        ];

        $filtered = Ipex::filterCallsByPeriod($calls, new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        $this->assertCount(2, $filtered);
        $this->assertSame([], Ipex::filterCallsByPeriod(false, new \DateTime('2026-05-01'), new \DateTime('2026-05-31')));
    }
}
