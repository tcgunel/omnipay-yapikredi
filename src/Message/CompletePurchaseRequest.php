<?php

namespace Omnipay\Yapikredi\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Yapikredi\Constants\Currency;
use Omnipay\Yapikredi\Helpers\Helper;
use Omnipay\Yapikredi\Models\CompletePurchaseRequestModel;
use Omnipay\Yapikredi\Models\CompletePurchaseResponseModel;

/**
 * Yapikredi 3D Secure Complete Purchase Request.
 *
 * Three-step process:
 * Step 1: POST oosResolveMerchantData with BankPacket, MerchantPacket, Sign, mac
 * Step 2: Check mdStatus=1
 * Step 3: POST oosTranData with bankData and mac
 */
class CompletePurchaseRequest extends RemoteAbstractRequest
{
    protected $endpoint;

    /**
     * @throws InvalidRequestException
     */
    public function getData()
    {
        $this->validateAll();

        return new CompletePurchaseRequestModel([
            'mid' => $this->getMerchantId(),
            'tid' => $this->getTerminalId(),
            'posnetId' => $this->getPosnetId(),
            'storeKey' => $this->getStoreKey(),
            'bankPacket' => $this->getBankPacket(),
            'merchantPacket' => $this->getMerchantPacket(),
            'sign' => $this->getSign(),
            'amount' => Helper::formatAmount($this->getAmountInteger()),
            'currencyCode' => Currency::mapCurrency($this->getCurrency()),
            'xid' => Helper::padOrderId($this->getTransactionId()),
        ]);
    }

    /**
     * @throws InvalidRequestException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate(
            'storeKey',
            'amount',
            'transactionId',
            'bankPacket',
            'merchantPacket',
            'sign',
        );
    }

    /**
     * @param CompletePurchaseRequestModel $data
     * @return CompletePurchaseResponse
     */
    public function sendData($data)
    {
        // Generate MAC hash
        $firstHash = Helper::hashFirst($data->storeKey, $data->tid);
        $mac = Helper::hashMac(
            $data->xid,
            $data->amount,
            $data->currencyCode,
            $data->mid,
            $firstHash
        );

        // Step 1: POST oosResolveMerchantData
        $resolveXml = Helper::buildOosResolveMerchantDataXml(
            $data->mid,
            $data->tid,
            [
                'bankData' => $data->bankPacket,
                'merchantData' => $data->merchantPacket,
                'sign' => $data->sign,
                'mac' => $mac,
            ]
        );

        $resolveResponseBody = $this->postXmlData($resolveXml);

        $resolveXmlParsed = Helper::parseXml($resolveResponseBody);

        // Step 2: Check mdStatus
        $mdStatus = (string) ($resolveXmlParsed->oosResolveMerchantDataResponse->mdStatus ?? '0');
        $mdErrorMessage = (string) ($resolveXmlParsed->oosResolveMerchantDataResponse->mdErrorMessage ?? '');
        $bankData = (string) ($resolveXmlParsed->oosResolveMerchantDataResponse->bankData ?? '');
        $merchantData = (string) ($resolveXmlParsed->oosResolveMerchantDataResponse->merchantData ?? '');
        $resolveApproved = (string) ($resolveXmlParsed->approved ?? '0');

        if ($resolveApproved !== '1' || $mdStatus !== '1') {
            // 3D verification failed
            $model = new CompletePurchaseResponseModel([
                'approved' => '0',
                'respCode' => (string) ($resolveXmlParsed->respCode ?? null),
                'respText' => (string) ($resolveXmlParsed->respText ?? $mdErrorMessage),
                'mdStatus' => $mdStatus,
                'mdErrorMessage' => $mdErrorMessage,
                'bankData' => $bankData,
                'merchantData' => $merchantData,
            ]);

            $model->resolveResponse = $resolveResponseBody;
            $model->originalResponse = $resolveResponseBody;

            return $this->response = new CompletePurchaseResponse($this, $model);
        }

        // Step 3: POST oosTranData
        $tranXml = Helper::buildOosTranDataXml(
            $data->mid,
            $data->tid,
            [
                'bankData' => $bankData,
                'mac' => $mac,
            ]
        );

        $tranResponseBody = $this->postXmlData($tranXml);

        return $this->createResponse([
            'tranResponse' => $tranResponseBody,
            'resolveResponse' => $resolveResponseBody,
            'mdStatus' => $mdStatus,
            'mdErrorMessage' => $mdErrorMessage,
            'bankData' => $bankData,
            'merchantData' => $merchantData,
        ]);
    }

    /**
     * @param array $data
     * @return CompletePurchaseResponse
     */
    protected function createResponse($data): CompletePurchaseResponse
    {
        $xml = Helper::parseXml($data['tranResponse']);

        $model = new CompletePurchaseResponseModel([
            'approved' => (string) ($xml->approved ?? '0'),
            'respCode' => (string) ($xml->respCode ?? null),
            'respText' => (string) ($xml->respText ?? null),
            'hostlogkey' => (string) ($xml->hostlogkey ?? null),
            'authCode' => (string) ($xml->authCode ?? null),
            'tranDate' => (string) ($xml->tranDate ?? null),
            'mdStatus' => $data['mdStatus'],
            'mdErrorMessage' => $data['mdErrorMessage'],
            'bankData' => $data['bankData'],
            'merchantData' => $data['merchantData'],
        ]);

        $model->originalResponse = $data['tranResponse'];
        $model->resolveResponse = $data['resolveResponse'];

        return $this->response = new CompletePurchaseResponse($this, $model);
    }
}
