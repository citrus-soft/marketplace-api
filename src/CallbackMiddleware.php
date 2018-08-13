<?php

namespace Citrus\MarketplaceApi;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallbackMiddleware implements MiddlewareInterface
{
	use ConvertsEncoding;

	/** @var Credentials */
	protected $credentials;

	public function __construct(Credentials $credentials)
	{
		$this->credentials = $credentials;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return bool
	 */
	protected function isValid(ServerRequestInterface $request): bool
	{
		$requestBody = $request->getParsedBody();

		return md5(implode('|', [$requestBody['callbackType'] ?: '', $this->credentials->getPartnerId(), $this->credentials->getSecret()])) === $requestBody['auth'] ?: '';
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$requestHandlerClass = $request->getAttribute('request-handler');

		if (in_array(\Citrus\MarketplaceApi\CallbackHandlerInterface::class, class_implements($requestHandlerClass)))
		{
			if (!$this->isValid($request))
			{
				throw new CallbackInvalidHashException();
			}

			foreach ($this->decode($request->getParsedBody()) as $attribute => $value)
			{
				$request = $request->withAttribute($attribute, $value);
			}
		}

		return $handler->handle($request);
	}

	function getApiCharset(): string
	{
		return 'windows-1251';
	}
}