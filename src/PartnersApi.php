<?php

namespace Citrus\MarketplaceApi;

/**
 *
 * Интерфейс для взаимодействия в Партерским API 1С-Битрикс
 *
 * @link https://dev.1c-bitrix.ru/community/blogs/marketplace_apps24/new-partner-for-the-rest-of-the-marketplace.php
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&INDEX=Y
 * @package Citrus\MarketplaceApi
 */
class PartnersApi
{
	use ConvertsEncoding;

	private $fetchAllPages = false;

	/** @var Credentials */
	protected $credentials;

	const REST_ENDPOINT = 'https://partners.1c-bitrix.ru/rest/';

	const ACTION_AUTH_KEYS = [
		'delete.callback.bitrix24.portal.change.tariff' => ['callbackId'],
		'delete.callback.bitrix24.portal.become.active' => ['callbackId'],
		'delete.callback.b24mp.add.coupon' => ['callbackId'],
		'delete.callback.activate.coupon' => ['callbackId'],
		'delete.callback.add.bitrix24.partner.portal' => ['callbackId'],
		'delete.callback.add.coupon' => ['callbackId'],
		'delete.callback.b24mp.activate.coupon' => ['callbackId'],
		'marketplace.product.check' => ['key'],
		'marketplace.product.self.check' => ['key'],
		'marketplace.client.add' => ['key', 'code'],
		'marketplace.client.update' => ['code'],
		'marketplace.coupon.add' => ['code'],
		'key.info' => ['key'],
	];

	/**
	 * @param Credentials $credentials
	 */
	public function __construct(Credentials $credentials)
	{
		$this->credentials = $credentials;
	}

	protected function callInternalList($method, $params = [])
	{
		if ($this->isFetchAllPages() && isset($params['navigation']))
		{
			unset($params['navigation']);
		}

		$result = $pageResult = array_get($this->callInternal($method, $params), 'result');

		while ($this->isFetchAllPages()
			&& array_get($pageResult, 'navigation.pageNumber') < array_get($result, 'navigation.pageCount'))
		{
			$params['navigation'] = ['page' => array_get($pageResult, 'navigation.pageNumber') + 1];
			$pageResult = array_get($this->callInternal($method, $params), 'result');

			if (!\is_array($pageResult['list']))
			{
				break;
			}

			$result['list'] = array_merge($result['list'], $pageResult['list']);
		}

		return $result;
	}

	protected function callInternal($method, $params = [])
	{
		$action = $this->methodToRestAction($method);

		if (!$action)
		{
			throw new \BadMethodCallException('Bad method call: ' . $method);
		}

		$params = $this->encode($params);

		$paramsForHash = array_map(function ($param) use ($params) {
			if (!isset($params[$param]))
			{
				throw new \InvalidArgumentException(sprintf('Missing $params[%s]', var_export($param, 1)));
			}

			return $params[$param];
		}, static::ACTION_AUTH_KEYS[$action] ?? []);

		$queryData = http_build_query(array_merge($params, [
				"action" => $action,
				"partnerId" => $this->credentials->getPartnerId(),
				"auth" => md5(implode('|', array_merge($paramsForHash, [$action, $this->credentials->getPartnerId(), $this->credentials->getSecret()]))),
			]
		));

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => static::REST_ENDPOINT,
			CURLOPT_POSTFIELDS => $queryData,
		]);

		$response = $this->decode(json_decode(curl_exec($curl), true));
		curl_close($curl);

		$this->handleRestError($action, array_get($response, 'error'));

		return $response;
	}

	protected function handleRestError($method, array $errors = null)
	{
		if (\is_array($errors) && \count($errors))
		{
			throw new PartnersApiException($method, $errors);
		}
	}

	protected function methodToRestAction($method)
	{
		if (preg_match_all('/((?:^|[A-Z])[a-z]+)/', $method, $matches))
		{
			array_walk($matches[0], function (&$v) {
				$v = strtolower($v);
			});

			return implode('.', $matches[0]);
		}

		return null;
	}

	protected function makeNavigationParam($page = null): array
	{
		return $page ? ['navigation' => ['page' => $page]] : [];
	}

	/**
	 * Метод позволяет получить информацию о других методах: полная структура ответа, которая должна быть у запрашиваемого метода.
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10461
	 * @param string $actionCode Код метода по которому запрашивается справка
	 * @return mixed
	 */
	public function help($actionCode)
	{
		return array_get($this->callInternal(__FUNCTION__, ['actionCode' => $actionCode]), 'result');
	}

	/**
	 * Добавление обработчика на событие активации купона маркетплейса
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10431
	 * @param string $code Код модуля
	 * @param string $url URL на обработчик события
	 * @return int ID обработчика
	 */
	public function addCallbackActivateCoupon($code, $url): int
	{
		return array_get($this->callInternal(__FUNCTION__, compact('code', 'url')), 'result.callbackId');
	}

	/**
	 * Добавление обработчика на событие добавления купона маркетплейса
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10435
	 * @param string $code Код модуля
	 * @param string $url URL на обработчик события
	 * @return int ID обработчика
	 */
	public function addCallbackAddCoupon($code, $url): int
	{
		return array_get($this->callInternal(__FUNCTION__, compact('code', 'url')), 'result.callbackId');
	}

	/**
	 * Добавление обработчика на событие активации купона маркетплейса Битрикс24
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10437
	 * @param string $code Код модуля
	 * @param string $url URL на обработчик события
	 * @return int ID обработчика
	 */
	public function addCallbackB24mpActivateCoupon($code, $url): int
	{
		return array_get($this->callInternal(__FUNCTION__, compact('code', 'url')), 'result.callbackId');
	}

	/**
	 * Добавление обработчика на событие добавления купона маркетплейса Битрикс24
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10437
	 * @param string $code Код модуля
	 * @param string $url URL на обработчик события
	 * @return int ID обработчика
	 */
	public function addCallbackB24mpAddCoupon($code, $url): int
	{
		return array_get($this->callInternal(__FUNCTION__, compact('code', 'url')), 'result.callbackId');
	}

	/**
	 * ???
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10433
	 * @param string $url URL на обработчик события
	 * @return int ID обработчика
	 */
	public function addCallbackBitrix24PortalBecomeActive($url): int
	{
		return array_get($this->callInternal(__FUNCTION__, compact('url')), 'result.callbackId');
	}

	/**
	 * Добавление обработчика на событие активации портала Битрикс24.
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10441
	 * @param string $url URL на обработчик события
	 * @return int ID обработчика
	 */
	public function addCallbackBitrix24PartnerPortal($url): int
	{
		return array_get($this->callInternal(__FUNCTION__, compact('url')), 'result.callbackId');
	}

	/**
	 * Добавление обработчика на событие активации портала Битрикс24.
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10443
	 * @param string $url URL на обработчик события
	 * @return int ID обработчика
	 */
	public function addCallbackBitrix24PortalChangeTariff($url): int
	{
		return array_get($this->callInternal(__FUNCTION__, compact('url')), 'result.callbackId');
	}

	protected function isTrue($value): bool
	{
		return \in_array($value, [1, 'Y']);
	}

	/**
	 * Удаление обработчика на событие активации купона маркетплейса
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10431
	 * @param int $callbackId ID обаботчика события
	 * @return bool Успешность удаления
	 */
	public function deleteCallbackActivateCoupon($callbackId): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('callbackId')), 'result.deleteSuccess'));
	}

	/**
	 * Удаление обработчика на добавление купона маркетплейса
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10449
	 * @param int $callbackId ID обаботчика события
	 * @return bool Успешность удаления
	 */
	public function deleteCallbackAddCoupon($callbackId): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('callbackId')), 'result.deleteSuccess'));
	}

	/**
	 * Удаление обработчика на событие создание портала Битрикс24
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10447
	 * @param int $callbackId ID обаботчика события
	 * @return bool Успешность удаления
	 */
	public function deleteCallbackAddBitrix24PartnerPortal($callbackId): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('callbackId')), 'result.deleteSuccess'));
	}

	/**
	 * Удаление обработчика на событие смены тарифа портала Битрикс24
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10457
	 * @param int $callbackId ID обаботчика события
	 * @return bool Успешность удаления
	 */
	public function deleteCallbackBitrix24PortalChangeTariff($callbackId): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('callbackId')), 'result.deleteSuccess'));
	}

	/**
	 * Удаление обработчика на событие активации портала Битрикс24.
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10455
	 * @param int $callbackId ID обаботчика события
	 * @return bool Успешность удаления
	 */
	public function deleteCallbackBitrix24PortalBecomeActive($callbackId): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('callbackId')), 'result.deleteSuccess'));
	}

	/**
	 * Удаление обработчика на добавление купона маркетплейса Битрикс24
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10453
	 * @param int $callbackId ID обаботчика события
	 * @return bool Успешность удаления
	 */
	public function deleteCallbackB24mpAddCoupon($callbackId): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('callbackId')), 'result.deleteSuccess'));
	}

	/**
	 * Удаление обработчика на событие активации купона маркетплейса Битрикс24
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10451
	 * @param int $callbackId ID обаботчика события
	 * @return bool Успешность удаления
	 */
	public function deleteCallbackB24mpActivateCoupon($callbackId): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('callbackId')), 'result.deleteSuccess'));
	}

	/**
	 * Возвращает список всех обработчиков
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10459
	 * @see PartnersApi::setFetchAllPages()
	 * @param int $page Номер страницы
	 * @return array ['list' => [...], 'navigation' => [...]]
	 */
	public function getCallbackList($page = null): array
	{
		return $this->callInternalList(__FUNCTION__, $this->makeNavigationParam($page));
	}

	/**
	 * Возвращает список опубликованных модулей из маркеплейса
	 *
	 * @see PartnersApi::setFetchAllPages()
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10475
	 * @param array $filter
	 * @param array $order
	 * @param int $page
	 * @return array ['list' => [...], 'navigation' => [...]]
	 */
	public function marketplaceProductList(array $filter = [], array $order = null, $page = null): array
	{
		return $this->callInternalList(__FUNCTION__, array_filter(compact('filter') + [
			'order' => $order,
		] + $this->makeNavigationParam($page)));
	}

	/**
	 * Проверяет подходит ли модуль к данному ключу. Проверка происходит по редакции, можно проверять любой модуль.
	 *
	 * @see PartnersApi::marketplaceProductSelfCheck()
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10473
	 * @param string $code Код модуля
	 * @param string $key Ключ продукта
	 * @return bool
	 */
	public function marketplaceProductCheck($code, $key): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, compact('code', 'key')), 'result.compatible'));
	}

	/**
	 * Проверяет, подходит ли ваш модуль к редакции и установлен ли он.
	 *
	 * В отличие от marketplaceProductCheck возвращает данные о модуле у клиента.
	 *
	 * @see PartnersApi::marketplaceProductCheck()
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10473
	 * @param string $code Код модуля
	 * @param string $key Ключ продукта
	 * @return array
	 */
	public function marketplaceProductSelfCheck($code, $key): array
	{
		return array_get($this->callInternal(__FUNCTION__, compact('code', 'key')), 'result');
	}

	/**
	 * Привязывает модуль к ключу клиента
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10465
	 * @param string $code Код модуля
	 * @param string $key Ключ клиента (код ключа)
	 * @param array $clientInfo Массив с данными клиента. [clientName, clientSiteUrl, clientEmail, clientContactPerson, clientPhone, comments]
	 * @return int Идентификатор купона
	 */
	public function marketplaceClientAdd($code, $key, array $clientInfo = []): int
	{
		return array_get($this->callInternal(__FUNCTION__, array_merge(
			compact('code', 'key'),
			$clientInfo
		)), 'result.couponId');
	}

	/**
	 * Возвращает список клиентов одного из ваших модулей
	 *
	 * @see PartnersApi::setFetchAllPages()
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10467
	 * @param string $code Код модуля
	 * @param array $order Сортировка
	 * @param int $page Номер страницы
	 * @return array ['list' => [...], 'navigation' => [...]]
	 */
	public function marketplaceClientList($code, array $order = null, $page = null): array
	{
		return $this->callInternalList(__FUNCTION__, array_filter([
			'filter' => ['code' => $code],
			'order' => $order,
		] + $this->makeNavigationParam($page)));
	}

	/**
	 * Изменяет контактную информацию о клиенте
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10469
	 * @param string $code Код модуля
	 * @param string $key Ключ клиента (код ключа)
	 * @param array $clientInfo Массив с данными клиента. [clientName, clientSiteUrl, clientEmail, clientContactPerson, clientPhone, comments]
	 * @return bool Обновлён
	 */
	public function marketplaceClientUpdate($code, $key, array $clientInfo = []): bool
	{
		return $this->isTrue(array_get($this->callInternal(__FUNCTION__, array_merge(
			compact('code', 'key'),
			$clientInfo
		)), 'result.isUpdated'));
	}

	/**
	 * Делать запросы для получения всех страниц (для запросов с постраничной навигацией)
	 *
	 * @param bool $fetchAllPages
	 * @return $this
	 */
	public function setFetchAllPages($fetchAllPages = true): self
	{
		$this->fetchAllPages = $fetchAllPages;

		return $this;
	}

	/**
	 * Делать запросы для получения всех страниц (для запросов с постраничной навигацией)
	 *
	 * @return bool
	 */
	public function isFetchAllPages(): bool
	{
		return $this->fetchAllPages;
	}

	/**
	 * Возвращает информацию по ключу
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10463
	 * @param string $key Ключ
	 * @return array
	 */
	public function keyInfo($key): array
	{
		return array_get($this->callInternal(__FUNCTION__, compact('key')), 'result');
	}

	/**
	 * Создает купон на модуль для клиента
	 *
	 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10471
	 * @param string $code Код модуля
	 * @param string $name Имя покупателя
	 * @param string $email E-mail покупателя
	 * @param bool $active Активность
	 * @param bool $prolongation Продление
	 * @param int $prolongationPeriod Период продления (3, 6 или 12)
	 * @param bool $sendCouponToBuyer Отослать покупателю
	 * @return array ['coupon' => 'MPX-...', 'couponSentToBuyer' => true|false]
	 */
	public function marketplaceCouponAdd($code, $name, $email, $active = true, $prolongation = false, $prolongationPeriod = null, $sendCouponToBuyer = false): array
	{
		if ($prolongation && !\in_array($prolongationPeriod, [3, 6, 12], true))
		{
			throw new \InvalidArgumentException('prolongationPeriod must be one of: 3, 6, 12');
		}

		$active = $active ? 'Y' : 'N';
		$prolongation = $prolongation ? 'Y' : 'N';
		$sendCouponToBuyer = $sendCouponToBuyer ? 'Y' : 'N';

		return array_get($this->callInternal(__FUNCTION__, compact(
			'code',
			'name',
			'email',
			'active',
			'prolongation',
			'prolongationPeriod',
			'sendCouponToBuyer'
		)), 'result');
	}

	/**
	 * @return string
	 */
	function getApiCharset(): string
	{
		return 'windows-1251';
	}
}