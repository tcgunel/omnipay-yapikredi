<?php

namespace Omnipay\Yapikredi\Message;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Yapikredi\Constants\Currency;
use Omnipay\Yapikredi\Helpers\Helper;
use Omnipay\Yapikredi\Models\PurchaseRequestModel;
use Omnipay\Yapikredi\Models\PurchaseResponseModel;

/**
 * Yapikredi Non-3D Purchase Request (Posnet sale).
 *
 * Sends a direct sale XML request to the Posnet API.
 */
class PurchaseRequest extends RemoteAbstractRequest
{
	protected $endpoint;

	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 */
	public function getData()
	{
		$this->validateAll();

		return new PurchaseRequestModel([
			'mid'          => $this->getMerchantId(),
			'tid'          => $this->getTerminalId(),
			'amount'       => Helper::formatAmount($this->getAmountInteger()),
			'ccno'         => $this->get_card('getNumber'),
			'currencyCode' => Currency::mapCurrency($this->getCurrency()),
			'cvc'          => $this->get_card('getCvv'),
			'expDate'      => Helper::formatExpiryDate(
				$this->get_card('getExpiryYear'),
				$this->get_card('getExpiryMonth')
			),
			'orderID'      => Helper::padOrderId($this->getTransactionId()),
			'installment'  => Helper::formatInstallment($this->getInstallment()),
		]);
	}

	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 */
	protected function validateAll(): void
	{
		$this->validateSettings();

		$this->getCard()->validate();

		$this->validate(
			'amount',
			'transactionId',
		);
	}

	/**
	 * @param PurchaseRequestModel $data
	 * @return PurchaseResponse
	 */
	public function sendData($data)
	{
		$xmlData = Helper::buildXml($data->mid, $data->tid, $data->toXmlArray());

		$responseBody = $this->postXmlData($xmlData);

		return $this->createResponse($responseBody);
	}

	protected function createResponse($data): PurchaseResponse
	{
		$xml = Helper::parseXml($data);

		$model = new PurchaseResponseModel([
			'approved'   => (string) ($xml->approved ?? '0'),
			'respCode'   => (string) ($xml->respCode ?? null),
			'respText'   => (string) ($xml->respText ?? null),
			'hostlogkey' => (string) ($xml->hostlogkey ?? null),
			'authCode'   => (string) ($xml->authCode ?? null),
			'tranDate'   => (string) ($xml->tranDate ?? null),
		]);

		$model->originalResponse = $data;

		return $this->response = new PurchaseResponse($this, $model);
	}
}
