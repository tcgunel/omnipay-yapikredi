# Omnipay: Yapikredi (Posnet)

**Yapikredi (Posnet) gateway for the Omnipay PHP payment processing library.**

[![Latest Stable Version](https://poser.pugx.org/tcgunel/omnipay-yapikredi/v/stable)](https://packagist.org/packages/tcgunel/omnipay-yapikredi)
[![Total Downloads](https://poser.pugx.org/tcgunel/omnipay-yapikredi/downloads)](https://packagist.org/packages/tcgunel/omnipay-yapikredi)
[![License](https://poser.pugx.org/tcgunel/omnipay-yapikredi/license)](https://packagist.org/packages/tcgunel/omnipay-yapikredi)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP. This package implements Yapikredi (Posnet) support for Omnipay.

## Installation

```bash
composer require tcgunel/omnipay-yapikredi
```

## Requirements

- PHP >= 8.0
- ext-simplexml
- ext-dom

## Supported Methods

| Method | Description |
|---|---|
| `purchase()` | Non-3D direct sale via Posnet XML API |
| `enrolment()` | 3D Secure enrolment (oosRequestData + redirect) |
| `completePurchase()` | 3D Secure completion (oosResolveMerchantData + oosTranData) |

## Not Implemented

The following features are **not available** through the CP.VPOS Posnet integration:

- **Cancel/Refund**: Not supported (returns error). Use the Yapikredi merchant panel instead.
- **Sale Query**: Not supported through this API.
- **Installment/BIN Query**: Not available through Posnet.

## Credentials

| Parameter | Description |
|---|---|
| `merchantId` | Merchant ID (mid) |
| `terminalId` | Terminal ID (tid) |
| `posnetId` | Posnet ID (merchant password) |
| `storeKey` | Store key for 3D Secure hash generation |

## Endpoints

| Environment | API URL | 3D URL |
|---|---|---|
| Test | https://setmpos.ykb.com/PosnetWebService/XML | https://setmpos.ykb.com/3DSWebService/YKBPaymentService |
| Production | https://posnet.yapikredi.com.tr/PosnetWebService/XML | https://posnet.yapikredi.com.tr/3DSWebService/YKBPaymentService |

Test panel: https://setmpos.ykb.com/PosnetF1/Login.jsp

## Usage

### Non-3D Direct Sale (purchase)

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('Yapikredi');

$gateway->setMerchantId('6706598320');
$gateway->setTerminalId('67005551');
$gateway->setPosnetId('1010028724');
$gateway->setTestMode(true);

$response = $gateway->purchase([
    'amount'        => '1.50',
    'currency'      => 'TRY',
    'transactionId' => 'ORDER-001',
    'installment'   => 1,
    'card'          => [
        'number'      => '4506349116608409',
        'expiryMonth' => '03',
        'expiryYear'  => '2030',
        'cvv'         => '000',
    ],
])->send();

if ($response->isSuccessful()) {
    echo "Transaction Reference: " . $response->getTransactionReference();
} else {
    echo "Error: " . $response->getMessage();
    echo "Code: " . $response->getCode();
}
```

### 3D Secure Payment (enrolment + completePurchase)

#### Step 1: Enrolment (redirect to 3D page)

```php
$response = $gateway->enrolment([
    'amount'        => '1.50',
    'currency'      => 'TRY',
    'transactionId' => 'ORDER-001',
    'installment'   => 1,
    'returnUrl'     => 'https://example.com/payment/callback',
    'storeKey'      => '10,10,10,10,10,10,10,10',
    'card'          => [
        'number'      => '4506349116608409',
        'expiryMonth' => '03',
        'expiryYear'  => '2030',
        'cvv'         => '000',
    ],
])->send();

if ($response->isRedirect()) {
    $response->redirect(); // Redirects to 3D Secure page
} else {
    echo "Error: " . $response->getMessage();
}
```

#### Step 2: Complete Purchase (after 3D callback)

```php
// In your callback URL handler:
$response = $gateway->completePurchase([
    'amount'         => '1.50',
    'currency'       => 'TRY',
    'transactionId'  => 'ORDER-001',
    'storeKey'       => '10,10,10,10,10,10,10,10',
    'bankPacket'     => $_POST['BankPacket'],
    'merchantPacket' => $_POST['MerchantPacket'],
    'sign'           => $_POST['Sign'],
])->send();

if ($response->isSuccessful()) {
    echo "Payment successful!";
    echo "Transaction Reference: " . $response->getTransactionReference();
} else {
    echo "Payment failed: " . $response->getMessage();
}
```

## Posnet Protocol Notes

- **Amount Format**: Multiplied by 100, no decimals (e.g., 1.50 TRY = "150")
- **Currency Codes**: TRY = "TL", USD = "US", EUR = "EU"
- **Order ID**: Padded with leading zeros to 24 characters
- **Expiry Date**: YYMM format (e.g., March 2030 = "3003")
- **Installment**: "00" for single payment, "02"-"12" for installments
- **3D Secure Hash**: SHA256 based. First hash: `SHA256(storeKey + ";" + terminalId)`, MAC: `SHA256(xid + ";" + amount + ";" + currency + ";" + mid + ";" + firstHash)`

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
