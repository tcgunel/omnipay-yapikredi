<?php

namespace Omnipay\Yapikredi\Models;

class EnrolmentResponseModel extends BaseModel
{
    /** @var string "1" for approved, "0" or other for declined */
    public string $approved = '0';

    /** @var string|null Response code */
    public ?string $respCode = null;

    /** @var string|null Response text / error message */
    public ?string $respText = null;

    /** @var string|null posnetData from oosRequestData response */
    public ?string $data1 = null;

    /** @var string|null posnetData2 from oosRequestData response */
    public ?string $data2 = null;

    /** @var string|null sign/digest from oosRequestData response */
    public ?string $sign = null;

    /** @var string Original XML response */
    public string $originalResponse = '';
}
