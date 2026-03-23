<?php

namespace Omnipay\Yapikredi\Models;

class CompletePurchaseResponseModel extends BaseModel
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

    /** @var string|null mdStatus from oosResolveMerchantData */
    public ?string $mdStatus = null;

    /** @var string|null mdErrorMessage from oosResolveMerchantData */
    public ?string $mdErrorMessage = null;

    /** @var string|null bankData from oosResolveMerchantData (needed for oosTranData) */
    public ?string $bankData = null;

    /** @var string|null merchantData from oosResolveMerchantData */
    public ?string $merchantData = null;

    /** @var string Original XML response (last response) */
    public string $originalResponse = '';

    /** @var string Original XML response for oosResolveMerchantData */
    public string $resolveResponse = '';
}
