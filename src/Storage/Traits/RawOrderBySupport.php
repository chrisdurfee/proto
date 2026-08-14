<?php declare(strict_types=1);
namespace Proto\Storage\Traits;

/**
 * RawOrderBySupport
 *
 * Storage helper for accepting a `rawOrderBy` modifier: a raw SQL ORDER BY
 * fragment (string or list of strings) that complements the framework's
 * object-based `orderBy` for sort expressions the ORM cannot express
 * (correlated subqueries, CASE WHEN ranking, distance, etc.).
 *
 * Defense-in-depth: the framework's `getAllInputs()` does NOT carry
 * `rawOrderBy` through from the request, so callers can only set it from
 * server-side code. This trait additionally validates every clause against
 * a strict character whitelist and rejects anything that looks like an
 * injection attempt (semicolons, quotes, comment markers, backslashes,
 * non-printable bytes). Rejected clauses are silently dropped: they are
 * a programmer / signal-data bug, not a runtime error.
 *
 * @package Proto\Storage\Traits
 */
trait RawOrderBySupport
{
	/**
	 * Apply order-by conditions.
	 *
	 * Reads the `rawOrderBy` modifier (string|array<string>), validates each
	 * clause, then prepends safe clauses before deferring to the framework's
	 * object-based `orderBy`.
	 *
	 * @param object $sql
	 * @param array|null $modifiers
	 * @param array|null &$params
	 * @return void
	 */
	protected function setOrderBy(object $sql, ?array $modifiers = null, ?array &$params = null): void
	{
		$rawOrderBy = $modifiers['rawOrderBy'] ?? null;

		if (is_string($rawOrderBy))
		{
			$this->applyRawOrderByClause($sql, $rawOrderBy);
		}
		elseif (is_array($rawOrderBy))
		{
			foreach ($rawOrderBy as $clause)
			{
				if (is_string($clause))
				{
					$this->applyRawOrderByClause($sql, $clause);
				}
			}
		}

		parent::setOrderBy($sql, $modifiers, $params);
	}

	/**
	 * Validate and apply a single rawOrderBy clause.
	 *
	 * @param object $sql
	 * @param string $clause
	 * @return void
	 */
	private function applyRawOrderByClause(object $sql, string $clause): void
	{
		$clause = trim($clause);
		if ($clause === '' || !$this->isSafeRawOrderBy($clause))
		{
			return;
		}

		$sql->orderBy($clause);
	}

	/**
	 * Whitelist-based safety check for a rawOrderBy clause.
	 *
	 * Allowed: ASCII letters/digits, underscore, dot, backtick, comma,
	 * whitespace, parentheses, simple arithmetic operators (+ - * /),
	 * equals, question-mark placeholder, end-of-string punctuation,
	 * single-quoted string literals (for CASE WHEN ... IN (...) ranking
	 * expressions), and Unicode letters/marks for non-ASCII taxonomy
	 * values (e.g. "Huracán").
	 *
	 * Callers that inline values inside single quotes MUST strictly
	 * sanitize the inner content (e.g. via a `[\p{L}\p{N} _\-.]+`
	 * whitelist) so a value can never break out of its quotes.
	 *
	 * Rejected: double quotes, semicolons, backslashes, SQL comment
	 * markers, NULs / control bytes.
	 *
	 * @param string $clause
	 * @return bool
	 */
	private function isSafeRawOrderBy(string $clause): bool
	{
		if ($clause === '')
		{
			return false;
		}

		// Reject anything containing forbidden tokens / characters.
		// Single quotes are intentionally NOT rejected: they are used
		// by relevance-ranking CASE expressions that inline strictly
		// sanitized taxonomy values (see the docblock above).
		if (preg_match('/[;"\\\\\x00-\x1f\x7f]|--|\/\*|\*\//', $clause))
		{
			return false;
		}

		// Whitelist of allowed characters. Unicode letters/marks are
		// permitted so accented taxonomy values ("Huracán EVO") can
		// be safely inlined when wrapped in single quotes by the
		// caller's value sanitizer.
		return (bool)preg_match('/^[\'A-Za-z0-9_.,`()\s+\-*\/=?<>\p{L}\p{M}]+$/u', $clause);
	}
}
