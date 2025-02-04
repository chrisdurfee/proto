<?php declare(strict_types=1);
namespace Proto\Database\Adapters\SQL
{
	/**
	 * SQL class
	 *
	 * Provides SQL functions that can be used to create SQL in storage layers.
	 *
	 * @package Proto\Database\Adapters\SQL
	 */
	class SQL
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
			$length = count($data);

			$json = '';
			$count = 0;
			$limit = $length - 1;
			foreach ($data as $key => $value)
			{
				$json .= "'" . $key . "', " . $value;
				if ($count < $limit)
				{
					$json .= ',';
				}
				$count++;
			}
			return $json;
		}

		/**
		 * Convert the data to a string.
		 *
		 * @param mixed $data
		 * @return string
		 */
		private static function getJsonString(mixed $data): string
		{
			if (is_array($data))
			{
				return static::handleJsonArray($data);
			}

			return '';
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
			)");
		}
	}
}

// Global functions
namespace
{
	use Proto\Database\Adapters\SQL\SQL;

	/**
	 * Create a raw SQL array.
	 *
	 * @param string $sql
	 * @return array
	 */
	function Raw(string $sql): array
	{
		return SQL::raw($sql);
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
		return SQL::json($alias, $data);
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
		return SQL::alias($column, $alias);
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
		return Alias([$column], $alias);
	}
}