<?php declare(strict_types=1);
namespace Proto\Database\Adapters\Sql
{
	/**
	 * SQL class
	 *
	 * Provides SQL functions that can be used to create SQL in storage layers.
	 *
	 * @package Proto\Database\Adapters\SQL
	 */
	class Sql
	{
		/**
		 * Initialize the class to autoload in the adapter to declare the global functions.
		 *
		 * @return void
		 */
		public static function init(): void
		{
		}

		/**
		 * Create a raw SQL string.
		 *
		 * @param string $sql
		 * @return array
		 */
		public static function raw(string $sql): array
		{
			return [$sql];
		}

		/**
		 * Create a raw SQL alias.
		 *
		 * @param mixed $column
		 * @param string $alias
		 * @return array
		 */
		public static function alias(mixed $column, string $alias): array
		{
			return [$column, $alias];
		}

		/**
		 * Handle JSON array data.
		 *
		 * @param array $data
		 * @return string
		 */
		private static function handleJsonArray(array $data): string
		{
			$json = [];
			foreach ($data as $key => $value)
			{
				$json[] = sprintf("'%s', %s", $key, $value);
			}
			return implode(', ', $json);
		}

		/**
		 * Convert the data to a JSON string.
		 *
		 * @param mixed $data
		 * @return string
		 */
		private static function getJsonString(mixed $data): string
		{
			return is_array($data) ? static::handleJsonArray($data) : '';
		}

		/**
		 * Create a raw JSON string.
		 *
		 * @param string $alias
		 * @param mixed $data
		 * @return array
		 */
		public static function json(string $alias, mixed $data): array
		{
			$json = static::getJsonString($data);

			return static::raw("JSON_ARRAYAGG(
				JSON_OBJECT(
					{$json}
				)
			) AS {$alias}");
		}
	}
}

// Global functions
namespace
{
	use Proto\Database\Adapters\Sql\Sql;

	/**
	 * Create a raw SQL array.
	 *
	 * @param string $sql
	 * @return array
	 */
	function Raw(string $sql): array
	{
		return Sql::raw($sql);
	}

	/**
	 * Create a raw JSON array.
	 *
	 * @param string $alias
	 * @param mixed $data
	 * @return array
	 */
	function Json(string $alias, mixed $data): array
	{
		return Sql::json($alias, $data);
	}

	/**
	 * Create a raw SQL alias.
	 *
	 * @param mixed $column
	 * @param string $alias
	 * @return array
	 */
	function Alias(mixed $column, string $alias): array
	{
		return Sql::alias($column, $alias);
	}

	/**
	 * Create a raw SQL alias.
	 *
	 * @param string $column
	 * @param string $alias
	 * @return array
	 */
	function RawAlias(string $column, string $alias): array
	{
		return Sql::alias([$column], $alias);
	}
}