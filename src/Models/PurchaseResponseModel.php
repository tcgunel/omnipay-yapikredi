<?php

namespace Omnipay\Yapikredi\Models;

class PurchaseResponseModel extends BaseModel
{
    /** @var string "1" for approved, "0" or other for declined */
    public string $approved = '0';

    /** @var string|null Response code */
    public ?string $respCode = null;

    /** @var string|null Response text / error message */
    public ?string $respText = null;

    /** @var string|null The hostlogkey serves as the transaction reference */
    public ?string $hostlogkey = null;

    /** @var string|null Auth code */
    public ?string $authCode = null;

    /** @var string|null Transaction date */
    public ?string $tranDate = null;

    /** @var string|null Point amount */
    public ?string $pointAmount = null;

    /** @var string Original XML response */
    public string $originalResponse = '';
}
