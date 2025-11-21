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
}
