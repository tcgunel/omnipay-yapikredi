<?php

namespace Omnipay\Yapikredi\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Yapikredi\Message\EnrolmentRequest;
use Omnipay\Yapikredi\Message\EnrolmentResponse;
use Omnipay\Yapikredi\Models\EnrolmentRequestModel;
use Omnipay\Yapikredi\Models\EnrolmentResponseModel;
use Omnipay\Yapikredi\Tests\TestCase;

class EnrolmentTest extends TestCase
{
	/**
	 * @throws InvalidRequestException
	 * @throws \JsonException
	 */
	public function test_enrolment_request_data()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$data = $request->getData();

		$this->assertInstanceOf(EnrolmentRequestModel::class, $data);

		$this->assertEquals('6706598320', $data->mid);
		$this->assertEquals('67005551', $data->tid);
		$this->assertEquals('1010028724', $data->posnetId);
		$this->assertEquals('150', $data->amount);
		$this->assertEquals('TL', $data->currencyCode);
		$this->assertEquals('00', $data->installment);
		$this->assertEquals('4506349116608409', $data->ccno);
		$this->assertEquals('3003', $data->expDate);
		$this->assertEquals('000', $data->cvc);
		$this->assertEquals('00000000YKB0000000000001', $data->orderID);
		$this->assertEquals('https://example.com/payment/callback', $data->merchantReturnURL);
		$this->assertEquals('10,10,10,10,10,10,10,10', $data->storeKey);
	}

	public function test_enrolment_request_validation_error_missing_store_key()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest-ValidationError.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);

		$request->getData();
	}

	public function test_enrolment_oos_request_data_array()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$data = $request->getData();

		$oosData = $data->toOosRequestDataArray();

		$this->assertEquals('1010028724', $oosData['posnetid']);
		$this->assertEquals('4506349116608409', $oosData['ccno']);
		$this->assertEquals('3003', $oosData['expDate']);
		$this->assertEquals('000', $oosData['cvc']);
		$this->assertEquals('150', $oosData['amount']);
		$this->assertEquals('TL', $oosData['currencyCode']);
		$this->assertEquals('00', $oosData['installment']);
		$this->assertEquals('00000000YKB0000000000001', $oosData['XID']);
		$this->assertEquals('Sale', $oosData['tranType']);
	}

	public function test_enrolment_sends_http_request()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$this->setMockHttpResponse('EnrolmentResponseSuccess.txt');

		$response = $this->gateway->enrolment($options)->send();

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
	}

	public function test_enrolment_response_success_is_redirect()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$this->setMockHttpResponse('EnrolmentResponseSuccess.txt');

		/** @var EnrolmentResponse $response */
		$response = $this->gateway->enrolment($options)->send();

		$this->assertFalse($response->isSuccessful());
		$this->assertTrue($response->isRedirect());

		$this->assertEquals('POST', $response->getRedirectMethod());

		$this->assertStringContainsString(
			'setmpos.ykb.com/3DSWebService/YKBPaymentService',
			$response->getRedirectUrl()
		);

		$redirectData = $response->getRedirectData();

		$this->assertEquals('6706598320', $redirectData['mid']);
		$this->assertEquals('1010028724', $redirectData['posnetID']);
		$this->assertEquals('AABBCCDD1122334455667788990011AABBCCDD', $redirectData['posnetData']);
		$this->assertEquals('EEFF00112233445566778899AABBCCDDEEFF00', $redirectData['posnetData2']);
		$this->assertEquals('9998AAA5678901234567890123456789', $redirectData['digest']);
		$this->assertEquals('https://example.com/payment/callback', $redirectData['merchantReturnURL']);
	}

	public function test_enrolment_response_error()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$this->setMockHttpResponse('EnrolmentResponseError.txt');

		/** @var EnrolmentResponse $response */
		$response = $this->gateway->enrolment($options)->send();

		$this->assertFalse($response->isSuccessful());
		$this->assertFalse($response->isRedirect());

		$this->assertEquals('0012', $response->getCode());
		$this->assertEquals('Gecersiz kart numarasi', $response->getMessage());
	}

	public function test_enrolment_gateway_method()
	{
		$request = $this->gateway->enrolment([
			'merchantId' => '6706598320',
			'terminalId' => '67005551',
			'posnetId'   => '1010028724',
			'storeKey'   => '10,10,10,10,10,10,10,10',
		]);

		$this->assertInstanceOf(EnrolmentRequest::class, $request);
	}

	public function test_enrolment_prod_3d_endpoint()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$options['testMode'] = false;

		$this->setMockHttpResponse('EnrolmentResponseSuccess.txt');

		/** @var EnrolmentResponse $response */
		$response = $this->gateway->enrolment($options)->send();

		$this->assertStringContainsString(
			'posnet.yapikredi.com.tr/3DSWebService/YKBPaymentService',
			$response->getRedirectUrl()
		);
	}
}
