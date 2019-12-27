<?php

namespace Citrus\MarketplaceApi;

/**
 * Класс для работы с клиентами модулей на Marketplace
 *
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=101&LESSON_ID=3222
 * @package Citrus\MarketplaceApi
 */
class ClientsApi
{
	use ConvertsEncoding;

	const API_ENDPOINT = "https://partners.1c-bitrix.ru/add_client.php";

	/** @var string Код модуля */
	protected $moduleId;
	/** @var string Код ключа клиента */
	protected $clientKey;

	/**
	 * @var Credentials
	 */
	protected $credentials;

	/**
	 *
	 * @param Credentials $credentials
	 * @param string $moduleId Код модуля с префиксом партнера (vendor.module)
	 */
	public function __construct(Credentials $credentials, $moduleId)
	{
		$this->setModuleId($moduleId);
		$this->credentials = $credentials;
	}

	/**
	 * Добавляет клиента модуля
	 *
	 * @param string $client_name
	 * @param string $client_email
	 * @param string $client_site
	 * @param string $client_contact_person
	 * @param string $client_phone
	 * @param string $comments
	 * @return string
	 */
	public function add(
		$client_name = "",
		$client_email = "",
		$client_site = "",
		$client_contact_person = "",
		$client_phone = "",
		$comments = ""
	): string
	{
		return $this->query(
			__FUNCTION__,
			$client_name,
			$client_email,
			$client_site,
			$client_contact_person,
			$client_phone,
			$comments
		);
	}

	protected function getUrl(array $params): string
	{
		return static::API_ENDPOINT . '?' . http_build_query($this->encode($params));
	}

	/**
	 * @param string $action
	 * @param string $name
	 * @param string $email
	 * @param string $site_url
	 * @param string $contact_person
	 * @param string $phone
	 * @param string $comments
	 * @return array|string Для action=list массив ключей, в противном случае сообщение об успешной обработке
	 */
	protected function query(
		$action = "",
		$name = "",
		$email = "",
		$site_url = "",
		$contact_person = "",
		$phone = "",
		$comments = ""
	) {
		foreach (get_defined_vars() as $var => $value)
		{
			${$var} = urlencode($value);
		}

		$hash = md5($this->credentials->getPartnerId() . "|" . $this->getModuleId() . "|" . $this->clientKey . "|" . $action . "|" . $this->credentials->getSecret());

		if ($action == "add" || $action == "update")
		{
			$url = $this->getUrl(array_merge(
				[
					'partner_id' => $this->credentials->getPartnerId(),
					'module_id' => $this->getModuleId(),
					'key' => $this->getClientKey(),
				],
				$this->encode(compact('name', 'email', 'site_url', 'contact_person', 'phone', $comments)),
				[
					'is_utf' => 'Y',
					'hash' => $hash,
				]
			));
		}
		elseif ($action == "delete")
		{
			$url = $this->getUrl(				[
				'partner_id' => $this->credentials->getPartnerId(),
				'module_id' => $this->getModuleId(),
				'key' => $this->getClientKey(),
				'action' => $action,
				'hash' => $hash,
			]);
		}
		elseif ($action == "list")
		{
			$url = $this->getUrl(				[
				'partner_id' => $this->credentials->getPartnerId(),
				'module_id' => $this->getModuleId(),
				'key' => $this->clientKey,
				'action' => $action,
				'hash' => $hash,
			]);
		}
		else
		{
			throw new \RuntimeException('action argument must be one of: add, update, delete, list', 'action');
		}

		$res = file_get_contents($url);
		if ($action == 'list' && stripos($res, 'ERROR') !== 0)
		{
			return $this->decode(array_map(function ($e) {
				return (array)$e;
			}, (new \SimpleXMLElement($res))->xpath('/clients/client')));
		}

		$result = explode("<br />", $res);
		if ($result[0] !== 'OK')
		{
			throw new ClientsApiException($result[1]);
		}

		return $result[1];
	}

	/**
	 * @return string
	 */
	public function getModuleId(): string
	{
		return $this->moduleId;
	}

	/**
	 * Устанавливает код модуля (необходимо вызывать перед всеми другими методами)
	 *
	 * @param string $moduleId
	 * @return ClientsApi
	 */
	public function setModuleId($moduleId): ClientsApi
	{
		if (!\is_string($moduleId) || substr_count($moduleId, '.') + 1 !== 2)
		{
			throw new \InvalidArgumentException('moduleId must contain module code with vendor prefix', 'moduleId');
		}

		$this->moduleId = $moduleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getClientKey(): string
	{
		if (!isset($this->clientKey))
		{
			throw new \LogicException('setClientKey must be called before');
		}

		return $this->clientKey;
	}

	/**
	 * @param string $clientKey
	 * @return ClientsApi
	 */
	public function setClientKey($clientKey): ClientsApi
	{
		if (!\is_string($clientKey) || \strlen($clientKey) !== 32)
		{
			throw new \InvalidArgumentException('clientKey must be a string of 32 characters');
		}

		$this->clientKey = $clientKey;

		return $this;
	}

	/**
	 * Обновляет данные клиента модуля
	 *
	 * @param string $client_name
	 * @param string $client_email
	 * @param string $client_site
	 * @param string $client_contact_person
	 * @param string $client_phone
	 * @param string $comments
	 * @return string
	 */
	public function update(
		$client_name = "",
		$client_email = "",
		$client_site = "",
		$client_contact_person = "",
		$client_phone = "",
		$comments = ""
	): string
	{
		return $this->query(
			__FUNCTION__,
			$client_name,
			$client_email,
			$client_site,
			$client_contact_person,
			$client_phone,
			$comments
		);
	}

	/**
	 * Удаляет клиента модуля
	 *
	 * @param string $key Ключ
	 * @return string
	 */
	public function delete($key = null): string
	{
		if (isset($key))
		{
			$this->setClientKey($key);
		}

		return $this->query(__FUNCTION__);
	}

	/**
	 * Получает список клиентов модуля
	 *
	 * @param array $filter
	 * @return array
	 */
	public function getList(array $filter = []): array
	{
		$result = $this->query('list');

		if (\count($filter))
		{
			$result = array_filter($result, function ($resultItem) use ($filter) {
				foreach ($filter as $key => $value)
				{
					$resultValue = $v[$key] ?? '';
					if ($resultValue != $value)
					{
						return false;
					}
				}

				return true;
			});
		}

		return $result;
	}

	/**
	 * Проверяет хэш входящих запросов
	 *
	 * @param array $params Параметры запроса
	 * @return bool
	 */
	public function checkHash(array $params): bool
	{
		static $checkParams = [
			'partner_id',
			'module_id',
			'key',
			'action',
		];

		if (!isset($params['partner_id']))
		{
			$params['partner_id'] = $this->credentials->getPartnerId();
		}

		$checkValues = [];
		foreach ($checkParams as $paramName)
		{
			if (!isset($params[$paramName]))
			{
				continue;
			}
			$checkValues[] = $params[$paramName];
		}
		if (!isset($params['hash']))
		{
			return false;
		}
		$checkValues[] = $this->credentials->getSecret();

		return md5(implode('|', $checkValues)) === (string)$params['hash'];
	}

	function getApiCharset(): string
	{
		return 'utf-8';
	}
}
