<?php

namespace Omnipay\Yapikredi\Tests\Feature;

use Omnipay\Yapikredi\Constants\Currency;
use Omnipay\Yapikredi\Helpers\Helper;
use Omnipay\Yapikredi\Tests\TestCase;

class HelperTest extends TestCase
{
    public function test_format_amount()
    {
        $this->assertEquals('150', Helper::formatAmount(150));
        $this->assertEquals('1000', Helper::formatAmount(1000));
        $this->assertEquals('0', Helper::formatAmount(0));
        $this->assertEquals('1', Helper::formatAmount(1));
    }

    public function test_format_expiry_date()
    {
        // YYMM format
        $this->assertEquals('3003', Helper::formatExpiryDate('2030', '03'));
        $this->assertEquals('3003', Helper::formatExpiryDate('2030', '3'));
        $this->assertEquals('9912', Helper::formatExpiryDate('2099', '12'));
        $this->assertEquals('2501', Helper::formatExpiryDate('25', '1'));
    }

    public function test_pad_order_id()
    {
        $this->assertEquals('00000000YKB0000000000001', Helper::padOrderId('YKB0000000000001'));
        $this->assertEquals('000000000000000000000001', Helper::padOrderId('1'));
        $this->assertEquals('123456789012345678901234', Helper::padOrderId('123456789012345678901234'));
    }

    public function test_format_installment()
    {
        $this->assertEquals('00', Helper::formatInstallment(0));
        $this->assertEquals('00', Helper::formatInstallment(1));
        $this->assertEquals('02', Helper::formatInstallment(2));
        $this->assertEquals('03', Helper::formatInstallment(3));
        $this->assertEquals('12', Helper::formatInstallment(12));
        $this->assertEquals('00', Helper::formatInstallment(null));
    }

    public function test_hash_first()
    {
        $storeKey = '10,10,10,10,10,10,10,10';
        $terminalId = '67005551';

        $hash = Helper::hashFirst($storeKey, $terminalId);

        // SHA256(storeKey + ";" + terminalId), base64 encoded
        $expected = base64_encode(hash('sha256', $storeKey . ';' . $terminalId, true));

        $this->assertEquals($expected, $hash);
    }

    public function test_hash_mac()
    {
        $xid = '00000000YKB0000000000001';
        $amount = '150';
        $currency = 'TL';
        $mid = '6706598320';
        $firstHash = Helper::hashFirst('10,10,10,10,10,10,10,10', '67005551');

        $mac = Helper::hashMac($xid, $amount, $currency, $mid, $firstHash);

        $expected = base64_encode(hash('sha256', $xid . ';' . $amount . ';' . $currency . ';' . $mid . ';' . $firstHash, true));

        $this->assertEquals($expected, $mac);
    }

    public function test_currency_map()
    {
        $this->assertEquals('TL', Currency::mapCurrency('TRY'));
        $this->assertEquals('TL', Currency::mapCurrency('TL'));
        $this->assertEquals('US', Currency::mapCurrency('USD'));
        $this->assertEquals('EU', Currency::mapCurrency('EUR'));
        $this->assertEquals('TL', Currency::mapCurrency(null));
        $this->assertEquals('TL', Currency::mapCurrency('UNKNOWN'));
    }

    public function test_build_xml_sale()
    {
        $xml = Helper::buildXml('6706598320', '67005551', [
            'sale' => [
                'amount' => '150',
                'ccno' => '4506349116608409',
                'currencyCode' => 'TL',
                'cvc' => '000',
                'expDate' => '3003',
                'orderID' => '00000000YKB0000000000001',
                'installment' => '00',
            ],
        ]);

        $parsedXml = new \SimpleXMLElement($xml);

        $this->assertEquals('6706598320', (string) $parsedXml->mid);
        $this->assertEquals('67005551', (string) $parsedXml->tid);
        $this->assertEquals('1', (string) $parsedXml->tranDateRequired);
        $this->assertEquals('150', (string) $parsedXml->sale->amount);
        $this->assertEquals('4506349116608409', (string) $parsedXml->sale->ccno);
        $this->assertEquals('TL', (string) $parsedXml->sale->currencyCode);
        $this->assertEquals('000', (string) $parsedXml->sale->cvc);
        $this->assertEquals('3003', (string) $parsedXml->sale->expDate);
        $this->assertEquals('00000000YKB0000000000001', (string) $parsedXml->sale->orderID);
        $this->assertEquals('00', (string) $parsedXml->sale->installment);
    }

    public function test_build_oos_request_data_xml()
    {
        $xml = Helper::buildOosRequestDataXml(
            '6706598320',
            '67005551',
            '1010028724',
            [
                'posnetid' => '1010028724',
                'ccno' => '4506349116608409',
                'expDate' => '2503',
                'cvc' => '000',
                'amount' => '150',
                'currencyCode' => 'TL',
                'installment' => '00',
                'XID' => '00000000YKB0000000000001',
                'cardHolderName' => '',
                'tranType' => 'Sale',
            ]
        );

        $parsedXml = new \SimpleXMLElement($xml);

        $this->assertEquals('6706598320', (string) $parsedXml->mid);
        $this->assertEquals('67005551', (string) $parsedXml->tid);
        $this->assertEquals('1010028724', (string) $parsedXml->oosRequestData->posnetid);
        $this->assertEquals('Sale', (string) $parsedXml->oosRequestData->tranType);
    }

    public function test_build_oos_resolve_merchant_data_xml()
    {
        $xml = Helper::buildOosResolveMerchantDataXml(
            '6706598320',
            '67005551',
            [
                'bankData' => 'BANKPACKETDATA',
                'merchantData' => 'MERCHANTPACKETDATA',
                'sign' => 'SIGNDATA',
                'mac' => 'MACHASH',
            ]
        );

        $parsedXml = new \SimpleXMLElement($xml);

        $this->assertEquals('6706598320', (string) $parsedXml->mid);
        $this->assertEquals('67005551', (string) $parsedXml->tid);
        $this->assertEquals('BANKPACKETDATA', (string) $parsedXml->oosResolveMerchantData->bankData);
        $this->assertEquals('MERCHANTPACKETDATA', (string) $parsedXml->oosResolveMerchantData->merchantData);
        $this->assertEquals('SIGNDATA', (string) $parsedXml->oosResolveMerchantData->sign);
        $this->assertEquals('MACHASH', (string) $parsedXml->oosResolveMerchantData->mac);
    }

    public function test_build_oos_tran_data_xml()
    {
        $xml = Helper::buildOosTranDataXml(
            '6706598320',
            '67005551',
            [
                'bankData' => 'RESOLVEDBANKDATA',
                'mac' => 'MACHASH',
            ]
        );

        $parsedXml = new \SimpleXMLElement($xml);

        $this->assertEquals('6706598320', (string) $parsedXml->mid);
        $this->assertEquals('67005551', (string) $parsedXml->tid);
        $this->assertEquals('1', (string) $parsedXml->tranDateRequired);
        $this->assertEquals('RESOLVEDBANKDATA', (string) $parsedXml->oosTranData->bankData);
        $this->assertEquals('MACHASH', (string) $parsedXml->oosTranData->mac);
    }

    public function test_parse_xml()
    {
        $xmlString = '<?xml version="1.0" encoding="ISO-8859-9"?><posnetResponse><approved>1</approved><hostlogkey>021585943405</hostlogkey></posnetResponse>';

        $xml = Helper::parseXml($xmlString);

        $this->assertEquals('1', (string) $xml->approved);
        $this->assertEquals('021585943405', (string) $xml->hostlogkey);
    }
}
