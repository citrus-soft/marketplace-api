<?php

namespace Citrus\MarketplaceApi;

/**
 * Return the default value of the given value.
 *
 * @param  mixed $value
 * @return mixed
 */
function value($value)
{
	return $value instanceof \Closure ? $value() : $value;
}

/**
 * Get an item from an array using "dot" notation.
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function array_get($array, $key, $default = null)
{
	if (\is_null($key))
	{
		return $array;
	}
	if (isset($array[$key]))
	{
		return $array[$key];
	}
	foreach (explode('.', $key) as $segment)
	{
		if (!\is_array($array) || !array_key_exists($segment, $array))
		{
			return value($default);
		}
		$array = $array[$segment];
	}

	return $array;
}

/**
 * @param mixed $data
 * @param string $to_encoding
 * @param null|string|string[] $from_encoding
 * @return mixed
 */
function mb_convert_encoding_array($data, $to_encoding, $from_encoding = null)
{
	if (is_iterable($data))
	{
		array_walk_recursive($data, function (&$data) use ($to_encoding, $from_encoding) {
			$data = mb_convert_encoding_array($data, $to_encoding, $from_encoding);
		});

		return $data;
	}

	return mb_convert_encoding($data, $to_encoding, $from_encoding);
}