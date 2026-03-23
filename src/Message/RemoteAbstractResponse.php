<?php

namespace Omnipay\Yapikredi\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

abstract class RemoteAbstractResponse extends AbstractResponse
{
	protected $response;

	protected $request;

	public function __construct(RequestInterface $request, $data)
	{
		parent::__construct($request, $data);

		$this->request = $request;

		$this->response = $data;
	}
}
