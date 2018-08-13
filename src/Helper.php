<?php

namespace Citrus\MarketplaceApi;

class Helper
{
	/**
	 * @var Credentials
	 */
	private $credentials;

	public function __construct(Credentials $credentials)
	{
		$this->credentials = $credentials;
	}

	public function deleteAllCallbacks($eventType = null)
	{
		$api = new PartnersApi($this->credentials);

		$existingCallbacks = array_get($api->setFetchAllPages(true)->getCallbackList(), 'list');

		if (isset($eventType))
		{
			$existingCallbacks = array_filter($existingCallbacks, function ($callback) use ($eventType) {
				return $callback['eventType'] === $eventType;
			});
		}

		array_map(function ($callback) use ($api) {

			$api->deleteCallbackActivateCoupon($callback['callbackId']);

		}, $existingCallbacks);
	}

	public function registerCallbacksForAllModules($url)
	{
		$api = new PartnersApi($this->credentials);

		$modules = $api
			->setFetchAllPages(true)
			->marketplaceProductList([
				'modulePartnerId' => $this->credentials->getPartnerId()
			]);

		array_walk($modules['list'], function ($module) use ($api, $url) {
			try
			{
				$api->addCallbackActivateCoupon($module['code'], sprintf($url, $module['code']));
			}
			catch (PartnersApiException $e)
			{
				// skip exception if callback already exists
				if ($e->getErrorCode() !== 'CALLBACK_ALREADY_EXISTS')
				{
					throw $e;
				}
			}
		});
	}

	/**
	 * Удаляет клиента модуля
	 *
	 * @param string $moduleId Код модуля
	 * @param string $key Код ключа
	 * @return bool
	 */
	public function deleteClient($moduleId, $key): bool
	{
		try
		{
			(new ClientsApi($this->credentials, $moduleId))
				->delete($key);
		}
		catch (ClientsApiException $e)
		{
			return false;
		}

		return true;
	}
}