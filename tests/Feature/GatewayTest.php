<?php

namespace Omnipay\Yapikredi\Tests\Feature;

use Omnipay\Yapikredi\Message\CompletePurchaseRequest;
use Omnipay\Yapikredi\Message\EnrolmentRequest;
use Omnipay\Yapikredi\Message\PurchaseRequest;
use Omnipay\Yapikredi\Tests\TestCase;

class GatewayTest extends TestCase
{
    public function test_gateway_name()
    {
        $this->assertEquals('Yapikredi', $this->gateway->getName());
    }

    public function test_gateway_default_parameters()
    {
        $defaults = $this->gateway->getDefaultParameters();

        $this->assertArrayHasKey('merchantId', $defaults);
        $this->assertArrayHasKey('terminalId', $defaults);
        $this->assertArrayHasKey('posnetId', $defaults);
        $this->assertArrayHasKey('storeKey', $defaults);
        $this->assertArrayHasKey('installment', $defaults);
        $this->assertArrayHasKey('testMode', $defaults);

        $this->assertEquals(1, $defaults['installment']);
        $this->assertFalse($defaults['testMode']);
    }

    public function test_gateway_purchase_method()
    {
        $request = $this->gateway->purchase([]);

        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function test_gateway_enrolment_method()
    {
        $request = $this->gateway->enrolment([]);

        $this->assertInstanceOf(EnrolmentRequest::class, $request);
    }

    public function test_gateway_complete_purchase_method()
    {
        $request = $this->gateway->completePurchase([]);

        $this->assertInstanceOf(CompletePurchaseRequest::class, $request);
    }

    public function test_gateway_setters_getters()
    {
        $this->gateway->setMerchantId('TEST_MID');
        $this->assertEquals('TEST_MID', $this->gateway->getMerchantId());

        $this->gateway->setTerminalId('TEST_TID');
        $this->assertEquals('TEST_TID', $this->gateway->getTerminalId());

        $this->gateway->setPosnetId('TEST_PID');
        $this->assertEquals('TEST_PID', $this->gateway->getPosnetId());

        $this->gateway->setStoreKey('TEST_KEY');
        $this->assertEquals('TEST_KEY', $this->gateway->getStoreKey());

        $this->gateway->setInstallment(3);
        $this->assertEquals(3, $this->gateway->getInstallment());

        $this->gateway->setSecure(true);
        $this->assertTrue($this->gateway->getSecure());
    }
}
