<?php

namespace Citrus\MarketplaceApi;

use Throwable;

class PartnersApiException extends MarketplaceApiException
{
	protected $errors;
	protected $errorCode;

	public function __construct($method, $errors, Throwable $previous = null)
	{
		$this->errors = $errors;

		$firstError = reset($errors);
		$this->errorCode = $firstError['code'];

		parent::__construct(sprintf('REST call to %s failed: %s', $method, $this->errorCode), $firstError['id'], $previous);
	}

	/**
	 * @return mixed
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	 * @return mixed
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}