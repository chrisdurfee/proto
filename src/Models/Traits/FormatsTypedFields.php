<?php declare(strict_types=1);
namespace Proto\Models\Traits;

/**
 * FormatsTypedFields
 *
 * Helper trait for the `static format(?object $data): ?object` hook on
 * {@see \Proto\Models\Model} subclasses. Models repeatedly hand-roll loops
 * to coerce MySQL `TINYINT(1)` / `INT` columns back into PHP `bool` / `int`
 * after a join returns string-typed values. This trait centralises the
 * loops.
 *
 * Usage:
 *
 *   class PostComment extends Model
 *   {
 *       use FormatsTypedFields;
 *
 *       protected const FORMAT_BOOL_FIELDS = ['enabled'];
 *       protected const FORMAT_INT_FIELDS = ['likeCount', 'replyCount'];
 *
 *       protected static function format(?object $data): ?object
 *       {
 *           if (!$data) return null;
 *           static::castFormattedFields($data);
 *           return $data;
 *       }
 *   }
 *
 * Or pass field lists inline when the model needs different sets per
 * call site:
 *
 *   static::castBooleanFields($data, ['isPinned', 'isLocked']);
 *   static::castIntegerFields($data, ['likeCount', 'shareCount']);
 *
 * @package Proto\Models\Traits
 */
trait FormatsTypedFields
{
	/**
	 * Cast both the model's `FORMAT_BOOL_FIELDS` and `FORMAT_INT_FIELDS`
	 * constants on the given row. Safe to call on a null row.
	 *
	 * @param object|null $data
	 * @return object|null
	 */
	protected static function castFormattedFields(?object $data): ?object
	{
		if ($data === null)
		{
			return null;
		}

		if (defined(static::class . '::FORMAT_BOOL_FIELDS'))
		{
			$boolFields = static::FORMAT_BOOL_FIELDS;
			if (is_array($boolFields))
			{
				static::castBooleanFields($data, $boolFields);
			}
		}

		if (defined(static::class . '::FORMAT_INT_FIELDS'))
		{
			$intFields = static::FORMAT_INT_FIELDS;
			if (is_array($intFields))
			{
				static::castIntegerFields($data, $intFields);
			}
		}

		return $data;
	}

	/**
	 * Coerce each listed field on `$data` to a boolean when present.
	 *
	 * @param object $data
	 * @param array<int, string> $fields
	 * @return void
	 */
	protected static function castBooleanFields(object $data, array $fields): void
	{
		foreach ($fields as $field)
		{
			if (isset($data->$field))
			{
				$data->$field = (bool)$data->$field;
			}
		}
	}

	/**
	 * Coerce each listed field on `$data` to an integer when present.
	 *
	 * @param object $data
	 * @param array<int, string> $fields
	 * @return void
	 */
	protected static function castIntegerFields(object $data, array $fields): void
	{
		foreach ($fields as $field)
		{
			if (isset($data->$field))
			{
				$data->$field = (int)$data->$field;
			}
		}
	}
}
