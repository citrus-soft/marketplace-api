<?php

namespace Citrus\MarketplaceApi;

use Throwable;

class HashMismatchException extends MarketplaceApiException
{
	protected $errors;

	public function __construct(Credentials $credentials, Throwable $previous = null)
	{
		parent::__construct('Hash mismatch', 0, $previous);
	}
}