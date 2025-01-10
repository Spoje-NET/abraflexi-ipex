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

namespace Test\SpojeNet\System;

use PHPUnit\Framework\TestCase;
use SpojeNet\System\IPEX;

/**
 * Class IPEX.
 *
 * @covers \SpojeNet\System\IPEX
 */
class IPEXTest extends TestCase
{
    /**
     * @var IPEX an instance of "IPEX" to test
     */
    private IPEX $object;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        /** @todo Maybe add some arguments to this constructor */
        $this->object = new IPEX();
    }

    /**
     * @covers \SpojeNet\System\IPEX::getIpexInvoices
     */
    public function testGetIpexInvoices(): void
    {
        $this->assertArrayHasKey('0', $this->object->getIpexInvoices());
    }

    /**
     * @covers \SpojeNet\System\IPEX::processIpexInvoices
     */
    public function testProcessIpexInvoices(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::getUnivoicedCalls
     */
    public function testGetUnivoicedCalls(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::getUsersPreparedOrders
     */
    public function testGetUsersPreparedOrders(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::foundLastInvoice
     */
    public function testFoundLastInvoice(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::uninvoicedAmount
     */
    public function testUninvoicedAmount(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::createOrder
     */
    public function testCreateOrder(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::pdfCallLog
     */
    public function testPdfCallLog(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::addCallLogAsItems
     */
    public function testAddCallLogAsItems(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::sendOrderByMail
     */
    public function testSendOrderByMail(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::noIpexExtID
     */
    public function testNoIpexExtID(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::createInvoice
     */
    public function testCreateInvoice(): void
    {
        /** @todo Complete this unit test method. */
        $this->markTestIncomplete();
    }

    /**
     * @covers \SpojeNet\System\IPEX::getInvoicer
     */
    public function testGetInvoicer(): void
    {
        $this->assertIsObject($this->object->getInvoicer());
    }
}
