<?php

namespace Omnipay\Yapikredi\Message;

use Omnipay\Yapikredi\Models\CompletePurchaseResponseModel;

/**
 * Yapikredi 3D Secure Complete Purchase Response.
 */
class CompletePurchaseResponse extends RemoteAbstractResponse
{
	public function isSuccessful(): bool
	{
		return $this->getData()->approved === '1' && $this->getData()->mdStatus === '1';
	}

	public function getMessage(): ?string
	{
		if ($this->getData()->respText) {
			return $this->getData()->respText;
		}

		return $this->getData()->mdErrorMessage;
	}

	public function getCode(): ?string
	{
		return $this->getData()->respCode;
	}

	public function getTransactionReference(): ?string
	{
		return $this->getData()->hostlogkey;
	}

	public function getData(): CompletePurchaseResponseModel
	{
		return $this->response;
	}
}
