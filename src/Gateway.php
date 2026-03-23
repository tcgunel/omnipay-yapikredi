<?php

namespace Omnipay\Yapikredi;

use Omnipay\Common\AbstractGateway;
use Omnipay\Yapikredi\Message\CompletePurchaseRequest;
use Omnipay\Yapikredi\Message\EnrolmentRequest;
use Omnipay\Yapikredi\Message\PurchaseRequest;
use Omnipay\Yapikredi\Traits\PurchaseGettersSetters;

/**
 * Yapikredi (Posnet) Gateway
 * (c) Tolga Can Gunel
 * http://www.github.com/tcgunel/omnipay-yapikredi
 *
 * Supports:
 * - purchase() : Non-3D direct sale via Posnet XML API
 * - enrolment() : 3D Secure enrolment (oosRequestData + redirect)
 * - completePurchase() : 3D Secure completion (oosResolveMerchantData + oosTranData)
 *
 * Not implemented (Posnet limitation):
 * - cancel/refund: Not supported in CP.VPOS
 * - saleQuery: Not supported in CP.VPOS
 * - installment/BIN query: Not available
 *
 * @method \Omnipay\Common\Message\NotificationInterface acceptNotification(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = [])
 */
class Gateway extends AbstractGateway
{
    use PurchaseGettersSetters;

    public function getName(): string
    {
        return 'Yapikredi';
    }

    public function getDefaultParameters()
    {
        return [
            'merchantId' => '',
            'terminalId' => '',
            'posnetId' => '',
            'storeKey' => '',
            'installment' => 1,
            'testMode' => false,
        ];
    }

    /**
     * Non-3D direct sale.
     */
    public function purchase(array $options = [])
    {
        return $this->createRequest(PurchaseRequest::class, $options);
    }

    /**
     * 3D Secure enrolment (step 1: oosRequestData + redirect form).
     */
    public function enrolment(array $options = [])
    {
        return $this->createRequest(EnrolmentRequest::class, $options);
    }

    /**
     * 3D Secure completion (steps: oosResolveMerchantData + oosTranData).
     */
    public function completePurchase(array $options = [])
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }
}
