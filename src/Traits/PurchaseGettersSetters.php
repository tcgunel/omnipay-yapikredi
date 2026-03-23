<?php

namespace Omnipay\Yapikredi\Traits;

trait PurchaseGettersSetters
{
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getTerminalId()
    {
        return $this->getParameter('terminalId');
    }

    public function setTerminalId($value)
    {
        return $this->setParameter('terminalId', $value);
    }

    public function getPosnetId()
    {
        return $this->getParameter('posnetId');
    }

    public function setPosnetId($value)
    {
        return $this->setParameter('posnetId', $value);
    }

    public function getStoreKey()
    {
        return $this->getParameter('storeKey');
    }

    public function setStoreKey($value)
    {
        return $this->setParameter('storeKey', $value);
    }

    public function getInstallment()
    {
        return $this->getParameter('installment');
    }

    public function setInstallment($value)
    {
        return $this->setParameter('installment', $value);
    }

    public function getSecure()
    {
        return $this->getParameter('secure');
    }

    public function setSecure($value)
    {
        return $this->setParameter('secure', $value);
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function getBankPacket()
    {
        return $this->getParameter('bankPacket');
    }

    public function setBankPacket($value)
    {
        return $this->setParameter('bankPacket', $value);
    }

    public function getMerchantPacket()
    {
        return $this->getParameter('merchantPacket');
    }

    public function setMerchantPacket($value)
    {
        return $this->setParameter('merchantPacket', $value);
    }

    public function getSign()
    {
        return $this->getParameter('sign');
    }

    public function setSign($value)
    {
        return $this->setParameter('sign', $value);
    }

    public function getXid()
    {
        return $this->getParameter('xid');
    }

    public function setXid($value)
    {
        return $this->setParameter('xid', $value);
    }
}
