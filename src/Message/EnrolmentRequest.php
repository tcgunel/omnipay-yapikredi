<?php

namespace Omnipay\Yapikredi\Message;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Yapikredi\Constants\Currency;
use Omnipay\Yapikredi\Helpers\Helper;
use Omnipay\Yapikredi\Models\EnrolmentRequestModel;
use Omnipay\Yapikredi\Models\EnrolmentResponseModel;

/**
 * Yapikredi 3D Secure Enrolment Request (Posnet oosRequestData).
 *
 * Step 1: Posts oosRequestData to API to get posnetData, posnetData2, digest.
 * Step 2: Returns redirect form fields to POST to 3D URL.
 */
class EnrolmentRequest extends RemoteAbstractRequest
{
	protected $endpoint;

	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 */
	public function getData()
	{
		$this->validateAll();

		return new EnrolmentRequestModel([
			'mid'               => $this->getMerchantId(),
			'tid'               => $this->getTerminalId(),
			'posnetId'          => $this->getPosnetId(),
			'amount'            => Helper::formatAmount($this->getAmountInteger()),
			'currencyCode'      => Currency::mapCurrency($this->getCurrency()),
			'installment'       => Helper::formatInstallment($this->getInstallment()),
			'ccno'              => $this->get_card('getNumber'),
			'expDate'           => Helper::formatExpiryDate(
				$this->get_card('getExpiryYear'),
				$this->get_card('getExpiryMonth')
			),
			'cvc'               => $this->get_card('getCvv'),
			'orderID'           => Helper::padOrderId($this->getTransactionId()),
			'merchantReturnURL' => $this->getReturnUrl(),
			'storeKey'          => $this->getStoreKey(),
		]);
	}

	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 */
	protected function validateAll(): void
	{
		$this->validateSettings();

		$this->validate('storeKey');

		$this->getCard()->validate();

		$this->validate(
			'amount',
			'transactionId',
			'returnUrl',
		);
	}

	/**
	 * @param EnrolmentRequestModel $data
	 * @return EnrolmentResponse
	 */
	public function sendData($data)
	{
		// Step 1: Post oosRequestData to API
		$xmlData = Helper::buildOosRequestDataXml(
			$data->mid,
			$data->tid,
			$data->posnetId,
			$data->toOosRequestDataArray()
		);

		$responseBody = $this->postXmlData($xmlData);

		return $this->createResponse([
			'apiResponse' => $responseBody,
			'requestData' => $data,
		]);
	}

	/**
	 * @param array $data
	 * @return EnrolmentResponse
	 */
	protected function createResponse($data): EnrolmentResponse
	{
		$xml = Helper::parseXml($data['apiResponse']);

		/** @var EnrolmentRequestModel $requestData */
		$requestData = $data['requestData'];

		$model = new EnrolmentResponseModel([
			'approved' => (string) ($xml->approved ?? '0'),
			'respCode' => (string) ($xml->respCode ?? null),
			'respText' => (string) ($xml->respText ?? null),
			'data1'    => (string) ($xml->oosRequestDataResponse->data1 ?? null),
			'data2'    => (string) ($xml->oosRequestDataResponse->data2 ?? null),
			'sign'     => (string) ($xml->oosRequestDataResponse->sign ?? null),
		]);

		$model->originalResponse = $data['apiResponse'];

		// Generate MAC hash for the redirect form
		$firstHash = Helper::hashFirst($requestData->storeKey, $requestData->tid);
		$mac = Helper::hashMac(
			$requestData->orderID,
			$requestData->amount,
			$requestData->currencyCode,
			$requestData->mid,
			$firstHash
		);

		return $this->response = new EnrolmentResponse($this, [
			'model'       => $model,
			'requestData' => $requestData,
			'mac'         => $mac,
		]);
	}

	public function getApiEndpoint(): string
	{
		return $this->getTestMode() ? $this->test_api_endpoint : $this->prod_api_endpoint;
	}

	public function get3DEndpoint(): string
	{
		return $this->getTestMode() ? $this->test_3d_endpoint : $this->prod_3d_endpoint;
	}
}
