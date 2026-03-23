<?php

namespace Omnipay\Yapikredi\Models;

class CompletePurchaseRequestModel extends BaseModel
{
    /** @var string Merchant ID */
    public string $mid;

    /** @var string Terminal ID */
    public string $tid;

    /** @var string Posnet ID */
    public string $posnetId;

    /** @var string Store key for hash generation */
    public string $storeKey;

    /** @var string BankPacket from 3D callback */
    public string $bankPacket;

    /** @var string MerchantPacket from 3D callback */
    public string $merchantPacket;

    /** @var string Sign from 3D callback */
    public string $sign;

    /** @var string Amount in kuruş */
    public string $amount;

    /** @var string Currency code (TL, US, EU) */
    public string $currencyCode;

    /** @var string XID / order ID */
    public string $xid;
}
