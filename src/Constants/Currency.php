<?php

namespace Omnipay\Yapikredi\Constants;

class Currency
{
	/**
	 * Posnet currency codes.
	 * TRY -> "TL", USD -> "US", EUR -> "EU"
	 */
	public const TRY = 'TL';

	public const TL = 'TL';

	public const USD = 'US';

	public const EUR = 'EU';

	/**
	 * Map ISO currency codes to Posnet currency codes.
	 */
	public const MAP = [
		'TRY' => 'TL',
		'TL'  => 'TL',
		'USD' => 'US',
		'EUR' => 'EU',
	];

	public static function mapCurrency(?string $currency): string
	{
		if ($currency === null) {
			return self::TL;
		}

		return self::MAP[strtoupper($currency)] ?? self::TL;
	}
}
