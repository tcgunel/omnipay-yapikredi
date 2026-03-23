<?php

namespace Omnipay\Yapikredi\Models;

abstract class BaseModel
{
	public function __construct(?array $abstract)
	{
		if ($abstract === null) {
			return;
		}

		foreach ($abstract as $key => $arg) {

			if (property_exists($this, $key)) {

				if (is_string($arg)) {

					$arg = trim($arg);

				}

				$this->$key = $arg;

			}

		}
	}
}
