<?php

namespace Omnipay\Yapikredi\Models;

class EnrolmentRequestModel extends BaseModel
{
    /** @var string Merchant ID */
    public string $mid;

    /** @var string Terminal ID */
    public string $tid;

    /** @var string Posnet ID */
    public string $posnetId;

    /** @var string Amount in kuruş */
    public string $amount;

    /** @var string Currency code (TL, US, EU) */
    public string $currencyCode;

    /** @var string Installment count in 00 format */
    public string $installment;

    /** @var string Card number */
    public string $ccno;

    /** @var string Expiry date in YYMM format */
    public string $expDate;

    /** @var string CVV */
    public string $cvc;

    /** @var string Order ID padded to 24 characters */
    public string $orderID;

    /** @var string Merchant return URL */
    public string $merchantReturnURL;

    /** @var string Store key for hash generation */
    public string $storeKey;

    /** @var string|null XID (transaction id for 3D) */
    public ?string $xid = null;

    /**
     * Get the oosRequestData fields for 3D Secure step 1 API call.
     */
    public function toOosRequestDataArray(): array
    {
        return [
            'posnetid' => $this->posnetId,
            'ccno' => $this->ccno,
            'expDate' => $this->expDate,
            'cvc' => $this->cvc,
            'amount' => $this->amount,
            'currencyCode' => $this->currencyCode,
            'installment' => $this->installment,
            'XID' => $this->orderID,
            'cardHolderName' => '',
            'tranType' => 'Sale',
        ];
    }

    /**
     * Get the 3D redirect form fields (step 2 - POST to 3D URL).
     */
    public function toRedirectFormData(
        string $posnetData,
        string $posnetData2,
        string $digest,
        string $mac
    ): array {
        return [
            'mid' => $this->mid,
            'posnetID' => $this->posnetId,
            'posnetData' => $posnetData,
            'posnetData2' => $posnetData2,
            'digest' => $digest,
            'merchantReturnURL' => $this->merchantReturnURL,
            'url' => '',
            'lang' => 'tr',
        ];
    }
}
