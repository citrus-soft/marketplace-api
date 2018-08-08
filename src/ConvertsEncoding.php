<?php

namespace Citrus\MarketplaceApi;

trait ConvertsEncoding
{
	protected $userCharset = 'utf-8';

	/**
	 * @return string
	 */
	abstract function getApiCharset(): string;

	/**
	 * @param string $userCharset
	 * @return $this
	 */
	public function setUserCharset(string $userCharset): ConvertsEncoding
	{
		$this->userCharset = $userCharset;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserCharset(): string
	{
		return $this->userCharset;
	}

	protected function needEncodingConvertion(): bool
	{
		return $this->getApiCharset() !== $this->userCharset;
	}

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	protected function encode($data)
	{
		if ($this->needEncodingConvertion())
		{
			return mb_convert_encoding_array($data, $this->getApiCharset(), $this->userCharset);
		}

		return $data;
	}

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	protected function decode($data)
	{
		if ($this->needEncodingConvertion())
		{
			return mb_convert_encoding_array($data, $this->userCharset, $this->getApiCharset());
		}

		return $data;
	}
}