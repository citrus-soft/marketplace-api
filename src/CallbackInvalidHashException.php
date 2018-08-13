<?php

namespace Citrus\MarketplaceApi;

use Throwable;

class CallbackInvalidHashException extends MarketplaceApiException
{
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
	{
		parent::__construct($message ?: 'Invalid hash', $code ?: 400, $previous);
	}
}