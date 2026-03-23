<?php

namespace Omnipay\Yapikredi\Message;

use Omnipay\Yapikredi\Models\PurchaseResponseModel;

/**
 * Yapikredi Non-3D Purchase Response.
 *
 * Response from direct sale via Posnet API.
 */
class PurchaseResponse extends RemoteAbstractResponse
{
    public function isSuccessful(): bool
    {
        return $this->getData()->approved === '1';
    }

    public function getMessage(): ?string
    {
        return $this->getData()->respText;
    }

    public function getCode(): ?string
    {
        return $this->getData()->respCode;
    }

    public function getTransactionReference(): ?string
    {
        return $this->getData()->hostlogkey;
    }

    public function getData(): PurchaseResponseModel
    {
        return $this->response;
    }
}
