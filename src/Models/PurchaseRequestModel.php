<?php

namespace Omnipay\Yapikredi\Models;

use Omnipay\Yapikredi\Constants\Currency;
use Omnipay\Yapikredi\Helpers\Helper;

class PurchaseRequestModel extends BaseModel
{
	/** @var string Merchant ID */
	public string $mid;

	/** @var string Terminal ID */
	public string $tid;

	/** @var string Amount in kuruş (smallest currency unit) */
	public string $amount;

	/** @var string Card number */
	public string $ccno;

	/** @var string Currency code (TL, US, EU) */
	public string $currencyCode;

	/** @var string CVV */
	public string $cvc;

	/** @var string Expiry date in YYMM format */
	public string $expDate;

	/** @var string Order ID padded to 24 characters */
	public string $orderID;

	/** @var string Installment count in 00 format */
	public string $installment;

	public function __construct(?array $abstract)
	{
		parent::__construct($abstract);
	}

	/**
	 * Get the XML transaction node for non-3D sale.
	 */
	public function toXmlArray(): array
	{
		return [
			'sale' => [
				'amount'       => $this->amount,
				'ccno'         => $this->ccno,
				'currencyCode' => $this->currencyCode,
				'cvc'          => $this->cvc,
				'expDate'      => $this->expDate,
				'orderID'      => $this->orderID,
				'installment'  => $this->installment,
			],
		];
	}
}
