<?php

namespace Omnipay\Yapikredi\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Yapikredi\Traits\PurchaseGettersSetters;

abstract class RemoteAbstractRequest extends AbstractRequest
{
	use PurchaseGettersSetters;

	protected $test_api_endpoint = 'https://setmpos.ykb.com/PosnetWebService/XML';

	protected $prod_api_endpoint = 'https://posnet.yapikredi.com.tr/PosnetWebService/XML';

	protected $test_3d_endpoint = 'https://setmpos.ykb.com/3DSWebService/YKBPaymentService';

	protected $prod_3d_endpoint = 'https://posnet.yapikredi.com.tr/3DSWebService/YKBPaymentService';

	/**
	 * @throws InvalidRequestException
	 */
	protected function validateSettings(): void
	{
		$this->validate('merchantId', 'terminalId', 'posnetId');
	}

	protected function getApiEndpoint(): string
	{
		return $this->getTestMode() ? $this->test_api_endpoint : $this->prod_api_endpoint;
	}

	protected function get3DEndpoint(): string
	{
		return $this->getTestMode() ? $this->test_3d_endpoint : $this->prod_3d_endpoint;
	}

	protected function get_card($key)
	{
		return $this->getCard() ? $this->getCard()->$key() : null;
	}

	/**
	 * Post xmldata to Posnet API endpoint.
	 *
	 * @param string $xmlData XML string to send
	 * @return string Response body
	 */
	protected function postXmlData(string $xmlData): string
	{
		$httpResponse = $this->httpClient->request(
			'POST',
			$this->getApiEndpoint(),
			[
				'Content-Type' => 'application/x-www-form-urlencoded',
			],
			'xmldata=' . urlencode($xmlData)
		);

		return $httpResponse->getBody()->getContents();
	}

	abstract protected function createResponse($data);
}
