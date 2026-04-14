<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Utils\Strings;
use Proto\Utils\Sanitize;

/**
 * Class Filter
 *
 * Handles filtering operations.
 *
 * @package Proto\Storage
 */
class Filter
{
	/**
	 * Decamelizes a string.
	 *
	 * @param string $str
	 * @return string
	 */
	protected static function decamelize(string $str): string
	{
		return Strings::snakeCase($str);
	}

	/**
	 * Retrieves the filter array.
	 *
	 * @param array|object|null $filter
	 * @return array
	 */
	public static function get(array|object|null $filter): array
	{
		if (!$filter)
		{
			return [];
		}

		if (is_object($filter))
		{
			$filter = (array)$filter;
		}

		return (count($filter) > 0) ? $filter : [];
	}

	/**
	 * Retrieves the value for a filter entry.
	 *
	 * @param mixed $value
	 * @param array $params
	 * @param bool $isSnakeCase
	 * @return mixed
	 */
	public static function format(mixed $value, array &$params, bool $isSnakeCase = true): mixed
	{
		/**
		 * This is a raw sql.
		 */
		if (!is_array($value))
		{
			self::logRawFilter($value);
			return $value;
		}

		/**
		 * If a first item is an array, this is not to be modified, just merge the params
		 * and return the raw SQL.
		 */
		$firstItem = $value[1] ?? null;
		if (is_array($firstItem))
		{
			$params = array_merge($params, $firstItem);
			return $value[0];
		}

		/**
		 * This will handle the array [key, value] or [key, operator, value] and replace
		 * the key with a prepared column name and the value with a placeholder.
		 */
		$value[0] = self::prepareColumn($value[0], $isSnakeCase);

		/**
		 * This will get the last element of the array and assign it to $param
		 * to replace it with a placeholder.
		 */
		$valueCount = count($value);
		$end = $valueCount - 1;
		$param = $value[$end];

		// CRITICAL FIX: Handle NULL values with IS NULL / IS NOT NULL
		if ($param === null)
		{
			// Determine operator based on format
			if ($valueCount === 3)
			{
				$operator = strtoupper(trim((string)$value[1]));
				// If operator is != or <>, use IS NOT NULL, otherwise IS NULL
				if (in_array($operator, ['!=', '<>', 'NOT']))
				{
					return $value[0] . ' IS NOT NULL';
				}
			}
			// Default to IS NULL for null values
			return $value[0] . ' IS NULL';
		}

		// Handle IN/NOT IN with array values: ['field', 'IN', [1, 2, 3]]
		if ($valueCount === 3 && is_array($param))
		{
			$operator = strtoupper(trim((string)$value[1]));
			if (in_array($operator, ['IN', 'NOT IN'], true))
			{
				if (empty($param))
				{
					return ($operator === 'IN') ? '1 = 0' : '1 = 1';
				}

				$placeholders = implode(', ', array_fill(0, count($param), '?'));
				$params = array_merge($params, array_values($param));
				return $value[0] . ' ' . $operator . ' (' . $placeholders . ')';
			}
		}

		// Replace value with placeholder for non-null values
		$value[$end] = '?';

		if ($valueCount === 3)
		{
			$value[1] = self::filterOperator((string)$value[1]);
		}
		else if ($valueCount === 2)
		{
			// Default operator
			$value = [$value[0], '=', $value[1]];
		}
		else if ($valueCount > 3)
		{
			// Invalid format, reset value
			$value = [];
		}

		$params[] = $param;
		return join(' ', [...$value]);
	}

	/**
	 * Filters the operator to ensure it's allowed.
	 *
	 * @param string $operator
	 * @return string
	 */
	protected static function filterOperator(string $operator): string
	{
		return (in_array($operator, self::allowedOperators(), true))
			? $operator
			: '=';
	}

	/**
	 * Returns the list of allowed operators.
	 *
	 * @return array
	 */
	protected static function allowedOperators(): array
	{
		return [
			'=',
			'!=',
			'<',
			'>',
			'<=',
			'>=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'IS NULL',
			'IS NOT NULL',
			'BETWEEN',
			'NOT BETWEEN'
		];
	}

	/**
	 * Sets up the filter.
	 *
	 * @param mixed $filter
	 * @param array $params
	 * @param bool $isSnakeCase
	 * @return array
	 */
	public static function setup(
		mixed $filter = null,
		array &$params = [],
		bool $isSnakeCase = true
	): array
	{
		$filter = self::get($filter);
		if (count($filter) < 1)
		{
			return [];
		}

		/**
		 * Normalize mixed filters (associative keys + numeric-indexed arrays)
		 * into a fully numeric-indexed array so format() processes each
		 * entry uniformly.
		 */
		$filter = self::normalize($filter);

		$filters = [];
		foreach ($filter as $item)
		{
			$value = self::format($item, $params, $isSnakeCase);
			$filters[] = $value;
		}

		return $filters;
	}

	/**
	 * Normalizes a filter array so every entry is numeric-indexed.
	 *
	 * Associative entries ('column' => $value) become [$column, $value],
	 * and associative entries ('column' => [$op, $val]) become
	 * [$column, $op, $val]. Numeric entries are kept as-is.
	 *
	 * @param array $filter
	 * @return array
	 */
	protected static function normalize(array $filter): array
	{
		$normalized = [];

		foreach ($filter as $key => $item)
		{
			if (is_string($key))
			{
				$normalized[] = is_array($item) ? [$key, ...$item] : [$key, $item];
			}
			else
			{
				$normalized[] = $item;
			}
		}

		return $normalized;
	}

	/**
	 * Formats an associative array for SET clause in UPDATE queries.
	 *
	 * @param array $data The key-value pairs to set.
	 * @param array $params The params array (passed by reference).
	 * @param bool $isSnakeCase Whether to convert keys to snake_case.
	 * @return array The formatted set clauses.
	 */
	public static function formatForSet(
		array $data,
		array &$params = [],
		bool $isSnakeCase = true
	): array
	{
		$setClauses = [];

		foreach ($data as $key => $value)
		{
			$columnName = self::prepareColumn($key, $isSnakeCase);

			// Handle NOW() and other SQL functions
			if (is_string($value) && preg_match('/^[A-Z_]+\(\)$/i', $value))
			{
				$setClauses[$columnName] = $value;
				continue;
			}

			// Regular values use placeholders
			$setClauses[$columnName] = '?';
			$params[] = $value;
		}

		return $setClauses;
	}

	/**
	 * Prepares the column name.
	 *
	 * @param string|int $field
	 * @param bool $isSnakeCase
	 * @return string
	 */
	protected static function prepareColumn(string|int $field, bool $isSnakeCase = true): string
	{
		$field = (string)$field;
		$columnName = ($isSnakeCase) ? self::decamelize($field) : $field;
		return Sanitize::cleanColumn($columnName);
	}

	/**
	 * Logs a deprecation notice when a raw SQL string is used as a filter.
	 *
	 * Helps track raw filter usage so they can gradually be replaced
	 * with parameterized alternatives.
	 *
	 * @param mixed $value The raw filter value.
	 * @return void
	 */
	protected static function logRawFilter(mixed $value): void
	{
		if (!is_string($value))
		{
			return;
		}

		/**
		 * Skip safe static conditions that contain no user input.
		 * Pattern: "alias.column IS [NOT] NULL"
		 */
		if (preg_match('/^[a-z_]+\.[a-z_]+\s+IS\s+(NOT\s+)?NULL$/i', $value))
		{
			return;
		}

		$domain = env('domain') ?? null;
		if ($domain !== 'development')
		{
			return;
		}

		$caller = self::getFilterCaller();
		$message = "[Proto\\Filter] Raw SQL filter detected: \"{$value}\"";
		if ($caller)
		{
			$message .= " in {$caller}";
		}

		error_log($message);
	}

	/**
	 * Gets the caller information for a raw filter log entry.
	 *
	 * Walks the backtrace to find the first caller outside the
	 * Filter/Storage internals.
	 *
	 * @return string|null
	 */
	protected static function getFilterCaller(): ?string
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
		$skipClasses = ['Proto\\Storage\\Filter', 'Proto\\Storage\\Storage'];

		foreach ($trace as $frame)
		{
			$class = $frame['class'] ?? '';
			if (!in_array($class, $skipClasses, true) && !empty($frame['file']))
			{
				$file = basename($frame['file']);
				$line = $frame['line'] ?? '?';
				return "{$file}:{$line}";
			}
		}

		return null;
	}

	/**
	 * Build an EXISTS subquery filter condition.
	 *
	 * Returns an array suitable for use in a filter array. The generated
	 * SQL is fully parameterized.
	 *
	 * @param string $table The related table name.
	 * @param string $alias The table alias for the subquery.
	 * @param string $joinCondition The ON/WHERE clause linking to the parent (e.g., "ea.event_id = e.id").
	 * @param array $conditions Additional parameterized conditions. Each entry is either:
	 *   - [column, value] for equality
	 *   - [column, operator, value] for comparison
	 *   - [column, 'IN', [values]] for IN clauses
	 * @return array A filter entry: [sql, [params]].
	 */
	public static function exists(string $table, string $alias, string $joinCondition, array $conditions = []): array
	{
		return self::buildSubqueryFilter('EXISTS', $table, $alias, $joinCondition, $conditions);
	}

	/**
	 * Build a NOT EXISTS subquery filter condition.
	 *
	 * @param string $table The related table name.
	 * @param string $alias The table alias for the subquery.
	 * @param string $joinCondition The ON/WHERE clause linking to the parent (e.g., "ea.event_id = e.id").
	 * @param array $conditions Additional parameterized conditions.
	 * @return array A filter entry: [sql, [params]].
	 */
	public static function notExists(string $table, string $alias, string $joinCondition, array $conditions = []): array
	{
		return self::buildSubqueryFilter('NOT EXISTS', $table, $alias, $joinCondition, $conditions);
	}

	/**
	 * Build a parameterized EXISTS or NOT EXISTS subquery filter.
	 *
	 * @param string $type 'EXISTS' or 'NOT EXISTS'.
	 * @param string $table The related table name.
	 * @param string $alias The table alias.
	 * @param string $joinCondition The join condition linking to parent table.
	 * @param array $conditions Additional conditions.
	 * @return array A filter entry: [sql, [params]].
	 */
	protected static function buildSubqueryFilter(
		string $type,
		string $table,
		string $alias,
		string $joinCondition,
		array $conditions
	): array
	{
		$table = Sanitize::cleanColumn($table);
		$alias = Sanitize::cleanColumn($alias);
		$params = [];

		$whereParts = [$joinCondition];

		foreach ($conditions as $condition)
		{
			if (!is_array($condition) || count($condition) < 2)
			{
				continue;
			}

			$col = Sanitize::cleanColumn((string)$condition[0]);
			$condCount = count($condition);

			if ($condCount === 2)
			{
				$whereParts[] = "{$col} = ?";
				$params[] = $condition[1];
			}
			elseif ($condCount === 3)
			{
				$operator = strtoupper(trim((string)$condition[1]));
				if (!in_array($operator, self::allowedOperators(), true))
				{
					$operator = '=';
				}

				if (in_array($operator, ['IN', 'NOT IN'], true) && is_array($condition[2]))
				{
					if (empty($condition[2]))
					{
						$whereParts[] = ($operator === 'IN') ? '1 = 0' : '1 = 1';
					}
					else
					{
						$placeholders = implode(', ', array_fill(0, count($condition[2]), '?'));
						$whereParts[] = "{$col} {$operator} ({$placeholders})";
						$params = array_merge($params, array_values($condition[2]));
					}
				}
				else
				{
					$whereParts[] = "{$col} {$operator} ?";
					$params[] = $condition[2];
				}
			}
		}

		$where = implode(' AND ', $whereParts);
		$sql = "{$type} (SELECT 1 FROM {$table} {$alias} WHERE {$where})";

		return [$sql, $params];
	}

	/**
	 * Build a condition with table alias prefix.
	 *
	 * Convenience method for `[alias.column, value]` style conditions
	 * that properly sanitize the column name.
	 *
	 * @param string $alias Table alias (e.g., 'e').
	 * @param string $column Column name (camelCase or snake_case).
	 * @param mixed $value The filter value.
	 * @param string|null $operator Optional operator (=, !=, >, <, LIKE, IN, etc.).
	 * @return array A filter entry.
	 */
	public static function aliased(string $alias, string $column, mixed $value, ?string $operator = null): array
	{
		$alias = Sanitize::cleanColumn($alias);
		$column = Sanitize::cleanColumn(Strings::snakeCase($column));
		$qualified = "{$alias}.{$column}";

		if ($operator !== null)
		{
			return [$qualified, $operator, $value];
		}

		return [$qualified, $value];
	}

	/**
	 * Build a safe static condition string.
	 *
	 * Used for conditions with no user input like `'alias.column IS NULL'`
	 * or `'alias.column > NOW()'`. Column and alias are sanitized.
	 *
	 * @param string $alias Table alias.
	 * @param string $column Column name (camelCase or snake_case).
	 * @param string $expression SQL expression (e.g., 'IS NULL', '> NOW()').
	 * @return string A safe static condition string.
	 */
	public static function condition(string $alias, string $column, string $expression): string
	{
		$alias = Sanitize::cleanColumn($alias);
		$column = Sanitize::cleanColumn(Strings::snakeCase($column));

		/**
		 * Whitelist common static expressions to prevent injection.
		 */
		$allowedExpressions = [
			'IS NULL',
			'IS NOT NULL',
			'> NOW()',
			'>= NOW()',
			'< NOW()',
			'<= NOW()',
			'= NOW()',
		];

		$normalized = strtoupper(trim($expression));
		if (!in_array($normalized, $allowedExpressions, true))
		{
			trigger_error(
				"[Proto\\Filter] Unrecognized static expression: \"{$expression}\". Use parameterized conditions for dynamic values.",
				E_USER_WARNING
			);
			return '1=1';
		}

		return "{$alias}.{$column} {$expression}";
	}
}
