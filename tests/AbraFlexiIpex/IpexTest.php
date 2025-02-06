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
}
