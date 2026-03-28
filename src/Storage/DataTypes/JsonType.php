<?php declare(strict_types=1);
namespace Proto\Storage\DataTypes;

/**
 * JsonType
 *
 * Handles JSON data type encoding/decoding.
 *
 * @package Proto\Storage\DataTypes
 */
class JsonType extends DataType
{
	/**
	 * @inheritDoc
	 */
	public function getPlaceholder(): string
	{
		return '?';
	}

	/**
	 * @inheritDoc
	 */
	public function toParams(mixed $value): array
	{
		if (is_string($value))
		{
			return [$value];
		}

		return [json_encode($value)];
	}

	/**
	 * Decode a JSON string from the database into a PHP array.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function fromDb(mixed $value): mixed
	{
		if (!is_string($value))
		{
			return $value;
		}

		return json_decode($value, true);
	}
}
