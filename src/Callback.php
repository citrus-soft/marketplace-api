<?php

namespace Citrus\MarketplaceApi;

/**
 * Обработчик события
 *
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&INDEX=Y
 * @property string auth Подпись
 * @property string callbackType Тип обработчика (тип события)
 * @property int callbackId ID обработчика
 * @package Citrus\MarketplaceApi
 */
abstract class Callback
{
	use ConvertsEncoding;

	private $fields = [];

	/**
	 * @var Credentials
	 */
	protected $credentials;

	/**
	 *
	 * @param Credentials $credentials
	 */
	public function __construct(Credentials $credentials)
	{
		$this->credentials = $credentials;

		if (!$this->checkHash())
		{
			throw new HashMismatchException($this->credentials);
		}

		$this->populateFields();
	}

	/**
	 * Проверяет подпись обработчика события
	 *
	 * На основе полей `callbackType` и `auth` в `$_POST`
	 *
	 * @return bool
	 */
	protected function checkHash(): bool
	{
		return md5(implode('|', [$_POST['callbackType'], $this->credentials->getPartnerId(), $this->credentials->getSecret()])) == $_POST['auth'];
	}

	protected function populateFields()
	{
		$this->fields = $this->decode($_POST);
	}

	public function __get($name)
	{
		return $this->fields[$name] ?? null;
	}

	function getApiCharset(): string
	{
		return 'windows-1251';
	}
}