<?php

namespace Omnipay\Yapikredi\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Yapikredi\Message\PurchaseRequest;
use Omnipay\Yapikredi\Message\PurchaseResponse;
use Omnipay\Yapikredi\Models\PurchaseRequestModel;
use Omnipay\Yapikredi\Models\PurchaseResponseModel;
use Omnipay\Yapikredi\Tests\TestCase;

class PurchaseTest extends TestCase
{
    /**
     * @throws InvalidRequestException
     * @throws \JsonException
     */
    public function test_purchase_request_data()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertInstanceOf(PurchaseRequestModel::class, $data);

        $this->assertEquals('6706598320', $data->mid);
        $this->assertEquals('67005551', $data->tid);
        $this->assertEquals('150', $data->amount);
        $this->assertEquals('4506349116608409', $data->ccno);
        $this->assertEquals('TL', $data->currencyCode);
        $this->assertEquals('000', $data->cvc);
        $this->assertEquals('3003', $data->expDate);
        $this->assertEquals('00000000YKB0000000000001', $data->orderID);
        $this->assertEquals('00', $data->installment);
    }

    public function test_purchase_request_with_installment()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['installment'] = 3;

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertEquals('03', $data->installment);
    }

    public function test_purchase_request_usd_currency()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['currency'] = 'USD';

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertEquals('US', $data->currencyCode);
    }

    public function test_purchase_request_eur_currency()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['currency'] = 'EUR';

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertEquals('EU', $data->currencyCode);
    }

    public function test_purchase_request_validation_error_missing_merchant_id()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_purchase_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseSuccess.txt');

        $responseBody = $httpResponse->getBody()->getContents();

        $xml = new \SimpleXMLElement($responseBody);

        $model = new PurchaseResponseModel([
            'approved' => (string) ($xml->approved ?? '0'),
            'respCode' => (string) ($xml->respCode ?? null),
            'respText' => (string) ($xml->respText ?? null),
            'hostlogkey' => (string) ($xml->hostlogkey ?? null),
            'authCode' => (string) ($xml->authCode ?? null),
            'tranDate' => (string) ($xml->tranDate ?? null),
        ]);

        $model->originalResponse = $responseBody;

        $response = new PurchaseResponse($this->getMockRequest(), $model);

        $this->assertTrue($response->isSuccessful());

        $this->assertEquals('021585943405', $response->getTransactionReference());

        $this->assertInstanceOf(PurchaseResponseModel::class, $response->getData());
    }

    public function test_purchase_response_error()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseError.txt');

        $responseBody = $httpResponse->getBody()->getContents();

        $xml = new \SimpleXMLElement($responseBody);

        $model = new PurchaseResponseModel([
            'approved' => (string) ($xml->approved ?? '0'),
            'respCode' => (string) ($xml->respCode ?? null),
            'respText' => (string) ($xml->respText ?? null),
            'hostlogkey' => (string) ($xml->hostlogkey ?? null),
            'authCode' => (string) ($xml->authCode ?? null),
            'tranDate' => (string) ($xml->tranDate ?? null),
        ]);

        $model->originalResponse = $responseBody;

        $response = new PurchaseResponse($this->getMockRequest(), $model);

        $this->assertFalse($response->isSuccessful());

        $this->assertEquals('0012', $response->getCode());

        $this->assertEquals('Gecersiz kart numarasi', $response->getMessage());
    }

    public function test_purchase_sends_http_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');

        $response = $this->gateway->purchase($options)->send();

        $this->assertTrue($response->isSuccessful());

        $requests = $this->getMockedRequests();
        $this->assertCount(1, $requests);

        $httpRequest = $requests[0];
        $this->assertEquals('POST', $httpRequest->getMethod());
        $this->assertStringContainsString(
            'setmpos.ykb.com/PosnetWebService/XML',
            (string) $httpRequest->getUri()
        );

        $body = (string) $httpRequest->getBody();
        $this->assertStringContainsString('xmldata=', $body);

        $this->assertEquals(
            'application/x-www-form-urlencoded',
            $httpRequest->getHeaderLine('Content-Type')
        );
    }

    public function test_purchase_xml_contains_correct_data()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');

        $this->gateway->purchase($options)->send();

        $requests = $this->getMockedRequests();
        $body = urldecode((string) $requests[0]->getBody());

        // Remove 'xmldata=' prefix
        $xmlData = substr($body, strlen('xmldata='));

        $xml = new \SimpleXMLElement($xmlData);

        $this->assertEquals('6706598320', (string) $xml->mid);
        $this->assertEquals('67005551', (string) $xml->tid);
        $this->assertEquals('1', (string) $xml->tranDateRequired);
        $this->assertEquals('150', (string) $xml->sale->amount);
        $this->assertEquals('4506349116608409', (string) $xml->sale->ccno);
        $this->assertEquals('TL', (string) $xml->sale->currencyCode);
        $this->assertEquals('000', (string) $xml->sale->cvc);
        $this->assertEquals('3003', (string) $xml->sale->expDate);
        $this->assertEquals('00000000YKB0000000000001', (string) $xml->sale->orderID);
        $this->assertEquals('00', (string) $xml->sale->installment);
    }

    public function test_purchase_prod_endpoint()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['testMode'] = false;

        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');

        $this->gateway->purchase($options)->send();

        $requests = $this->getMockedRequests();
        $httpRequest = $requests[0];

        $this->assertStringContainsString(
            'posnet.yapikredi.com.tr/PosnetWebService/XML',
            (string) $httpRequest->getUri()
        );
    }

    public function test_purchase_gateway_method()
    {
        $request = $this->gateway->purchase([
            'merchantId' => '6706598320',
            'terminalId' => '67005551',
            'posnetId' => '1010028724',
        ]);

        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function test_purchase_order_id_padding()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['transactionId'] = '123';

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertEquals('000000000000000000000123', $data->orderID);
        $this->assertEquals(24, strlen($data->orderID));
    }
}
