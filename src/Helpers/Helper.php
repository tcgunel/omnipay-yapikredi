<?php

namespace Omnipay\Yapikredi\Helpers;

use SimpleXMLElement;

class Helper
{
	/**
	 * Format amount for Posnet: multiply by 100, no decimals.
	 * e.g. 1.50 TRY => "150"
	 */
	public static function formatAmount(int $amountInteger): string
	{
		return (string) $amountInteger;
	}

	/**
	 * Format expiry date for Posnet: YYMM format.
	 * e.g. expiryYear=2025, expiryMonth=3 => "2503"
	 */
	public static function formatExpiryDate(string $expiryYear, string $expiryMonth): string
	{
		$yy = substr($expiryYear, -2);
		$mm = str_pad($expiryMonth, 2, '0', STR_PAD_LEFT);

		return $yy . $mm;
	}

	/**
	 * Pad order ID with leading zeros to 24 characters.
	 * Posnet requires orderID to be exactly 24 characters.
	 */
	public static function padOrderId(string $orderId): string
	{
		return str_pad($orderId, 24, '0', STR_PAD_LEFT);
	}

	/**
	 * Format installment for Posnet: "00" for no installment, "02", "03", etc.
	 * Single payment or installment=1 => "00"
	 */
	public static function formatInstallment($installment): string
	{
		$installment = (int) $installment;

		if ($installment <= 1) {
			return '00';
		}

		return str_pad((string) $installment, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Generate the first hash for 3D Secure: SHA256(storekey + ";" + terminalID)
	 */
	public static function hashFirst(string $storeKey, string $terminalId): string
	{
		return base64_encode(hash('sha256', $storeKey . ';' . $terminalId, true));
	}

	/**
	 * Generate MAC hash for 3D Secure:
	 * SHA256(xid + ";" + amount + ";" + currency + ";" + mid + ";" + firstHash)
	 */
	public static function hashMac(
		string $xid,
		string $amount,
		string $currency,
		string $mid,
		string $firstHash
	): string {
		$data = $xid . ';' . $amount . ';' . $currency . ';' . $mid . ';' . $firstHash;

		return base64_encode(hash('sha256', $data, true));
	}

	/**
	 * Build posnetRequest XML string.
	 */
	public static function buildXml(string $mid, string $tid, array $transactionNode): string
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-9"?><posnetRequest/>');

		$xml->addChild('mid', $mid);
		$xml->addChild('tid', $tid);
		$xml->addChild('tranDateRequired', '1');

		$transactionType = array_key_first($transactionNode);
		$transactionData = $transactionNode[$transactionType];

		$txnElement = $xml->addChild($transactionType);

		foreach ($transactionData as $key => $value) {
			$txnElement->addChild($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
		}

		return $xml->asXML();
	}

	/**
	 * Build oosRequestData XML for 3D Secure step 1.
	 */
	public static function buildOosRequestDataXml(
		string $mid,
		string $tid,
		string $posnetId,
		array $data
	): string {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-9"?><posnetRequest/>');

		$xml->addChild('mid', $mid);
		$xml->addChild('tid', $tid);
		$xml->addChild('tranDateRequired', '1');

		$oos = $xml->addChild('oosRequestData');

		foreach ($data as $key => $value) {
			$oos->addChild($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
		}

		return $xml->asXML();
	}

	/**
	 * Build oosResolveMerchantData XML for 3D Secure completePurchase step 1.
	 */
	public static function buildOosResolveMerchantDataXml(
		string $mid,
		string $tid,
		array $data
	): string {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-9"?><posnetRequest/>');

		$xml->addChild('mid', $mid);
		$xml->addChild('tid', $tid);

		$resolve = $xml->addChild('oosResolveMerchantData');

		foreach ($data as $key => $value) {
			$resolve->addChild($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
		}

		return $xml->asXML();
	}

	/**
	 * Build oosTranData XML for 3D Secure completePurchase step 3.
	 */
	public static function buildOosTranDataXml(
		string $mid,
		string $tid,
		array $data
	): string {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-9"?><posnetRequest/>');

		$xml->addChild('mid', $mid);
		$xml->addChild('tid', $tid);
		$xml->addChild('tranDateRequired', '1');

		$tran = $xml->addChild('oosTranData');

		foreach ($data as $key => $value) {
			$tran->addChild($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
		}

		return $xml->asXML();
	}

	/**
	 * Parse Posnet XML response string to SimpleXMLElement.
	 */
	public static function parseXml(string $xml): SimpleXMLElement
	{
		return new SimpleXMLElement($xml);
	}
}
