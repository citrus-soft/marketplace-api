<?php

namespace Citrus\MarketplaceApi;

/**
 * Данные для подключения к API
 *
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10487
 * @package Citrus\MarketplaceApi
 */
class Credentials
{
	private $partnerId;
	private $secret;

	/**
	 * Данные указаны в карточке партнера
	 *
	 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=133&LESSON_ID=10487
	 * @param string $partnerId Идентификатор партнера
	 * @param string $secret Секретный ключ для подписи данных
	 */
	public function __construct($partnerId, $secret)
	{
		$this->partnerId = $partnerId;
		$this->secret = $secret;
	}

	/**
	 *
	 * @param string $partnerId Идентификатор партнера
	 * @param string $secret Cекретный ключ, который вы указали в партнерской карточке
	 * @return $this
	 */
	public function setCredentials($partnerId, $secret): self
	{
		if (!$partnerId || !$secret)
		{
			throw new \InvalidArgumentException();
		}

		$this->partnerId = $partnerId;
		$this->secret = $secret;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPartnerId(): string
	{
		return $this->partnerId;
	}

	/**
	 * @return string
	 */
	public function getSecret(): string
	{
		return $this->secret;
	}
}