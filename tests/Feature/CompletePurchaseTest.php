<?php

namespace Omnipay\Yapikredi\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Yapikredi\Message\CompletePurchaseRequest;
use Omnipay\Yapikredi\Message\CompletePurchaseResponse;
use Omnipay\Yapikredi\Models\CompletePurchaseRequestModel;
use Omnipay\Yapikredi\Models\CompletePurchaseResponseModel;
use Omnipay\Yapikredi\Tests\TestCase;

class CompletePurchaseTest extends TestCase
{
	/**
	 * @throws InvalidRequestException
	 * @throws \JsonException
	 */
	public function test_complete_purchase_request_data()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$data = $request->getData();

		$this->assertInstanceOf(CompletePurchaseRequestModel::class, $data);

		$this->assertEquals('6706598320', $data->mid);
		$this->assertEquals('67005551', $data->tid);
		$this->assertEquals('1010028724', $data->posnetId);
		$this->assertEquals('10,10,10,10,10,10,10,10', $data->storeKey);
		$this->assertEquals('BANKPACKETDATA123456789', $data->bankPacket);
		$this->assertEquals('MERCHANTPACKETDATA123456789', $data->merchantPacket);
		$this->assertEquals('SIGNDATA123456789', $data->sign);
		$this->assertEquals('150', $data->amount);
		$this->assertEquals('TL', $data->currencyCode);
		$this->assertEquals('00000000YKB0000000000001', $data->xid);
	}

	public function test_complete_purchase_request_validation_error_missing_bank_packet()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest-ValidationError.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);

		$request->getData();
	}

	public function test_complete_purchase_success()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		// Two HTTP responses: first for oosResolveMerchantData, second for oosTranData
		$this->setMockHttpResponse('OosResolveMerchantDataResponseSuccess.txt');
		$this->setMockHttpResponse('OosTranDataResponseSuccess.txt');

		/** @var CompletePurchaseResponse $response */
		$response = $this->gateway->completePurchase($options)->send();

		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('021585943405', $response->getTransactionReference());

		$data = $response->getData();
		$this->assertInstanceOf(CompletePurchaseResponseModel::class, $data);
		$this->assertEquals('1', $data->approved);
		$this->assertEquals('1', $data->mdStatus);
	}

	public function test_complete_purchase_3d_verification_failed()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		// Only one HTTP response - mdStatus check fails, no second request
		$this->setMockHttpResponse('OosResolveMerchantDataResponseFailed.txt');

		/** @var CompletePurchaseResponse $response */
		$response = $this->gateway->completePurchase($options)->send();

		$this->assertFalse($response->isSuccessful());

		$data = $response->getData();
		$this->assertEquals('0', $data->mdStatus);
		$this->assertEquals('3D dogrulama basarisiz', $data->mdErrorMessage);

		// Should NOT have sent a second request
		$requests = $this->getMockedRequests();
		$this->assertCount(1, $requests);
	}

	public function test_complete_purchase_transaction_declined()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		// Two HTTP responses: resolve succeeds but transaction fails
		$this->setMockHttpResponse('OosResolveMerchantDataResponseSuccess.txt');
		$this->setMockHttpResponse('OosTranDataResponseError.txt');

		/** @var CompletePurchaseResponse $response */
		$response = $this->gateway->completePurchase($options)->send();

		$this->assertFalse($response->isSuccessful());

		$this->assertEquals('0012', $response->getCode());
		$this->assertEquals('Islem onaylanmadi', $response->getMessage());

		// Should have sent two requests
		$requests = $this->getMockedRequests();
		$this->assertCount(2, $requests);
	}

	public function test_complete_purchase_sends_correct_xml()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/CompletePurchaseRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$this->setMockHttpResponse('OosResolveMerchantDataResponseSuccess.txt');
		$this->setMockHttpResponse('OosTranDataResponseSuccess.txt');

		$this->gateway->completePurchase($options)->send();

		$requests = $this->getMockedRequests();

		// First request: oosResolveMerchantData
		$body1 = urldecode((string) $requests[0]->getBody());
		$xmlData1 = substr($body1, strlen('xmldata='));
		$xml1 = new \SimpleXMLElement($xmlData1);

		$this->assertEquals('6706598320', (string) $xml1->mid);
		$this->assertEquals('67005551', (string) $xml1->tid);
		$this->assertEquals('BANKPACKETDATA123456789', (string) $xml1->oosResolveMerchantData->bankData);
		$this->assertEquals('MERCHANTPACKETDATA123456789', (string) $xml1->oosResolveMerchantData->merchantData);
		$this->assertEquals('SIGNDATA123456789', (string) $xml1->oosResolveMerchantData->sign);
		$this->assertNotEmpty((string) $xml1->oosResolveMerchantData->mac);

		// Second request: oosTranData
		$body2 = urldecode((string) $requests[1]->getBody());
		$xmlData2 = substr($body2, strlen('xmldata='));
		$xml2 = new \SimpleXMLElement($xmlData2);

		$this->assertEquals('6706598320', (string) $xml2->mid);
		$this->assertEquals('67005551', (string) $xml2->tid);
		$this->assertEquals('RESOLVEDBANKDATA123456789', (string) $xml2->oosTranData->bankData);
		$this->assertNotEmpty((string) $xml2->oosTranData->mac);
	}

	public function test_complete_purchase_gateway_method()
	{
		$request = $this->gateway->completePurchase([
			'merchantId' => '6706598320',
			'terminalId' => '67005551',
			'posnetId'   => '1010028724',
			'storeKey'   => '10,10,10,10,10,10,10,10',
		]);

		$this->assertInstanceOf(CompletePurchaseRequest::class, $request);
	}
}
