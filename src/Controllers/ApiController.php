<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Http\HttpTerminationException;
use Proto\Http\Router\Request;
use Proto\Utils\Format\JsonFormat;
use Proto\Api\Validator;
use Proto\Storage\Filter;

/**
 * ApiController
 *
 * This abstract class provides a base implementation for API controllers.
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class ApiController extends Controller
{
	/**
	 * Maximum number of records returned by all() queries.
	 * Override in subclasses to adjust per-controller.
	 *
	 * @var int
	 */
	protected int $maxLimit = 1000;

	/**
	 * Restricts the fields from the given data.
	 *
	 * @param object $data The data to restrict.
	 * @param array $fields The fields to restrict.
	 * @return void
	 */
	protected function restrictFields(object &$data, array $fields = []): void
	{
		foreach ($fields as $field)
		{
			unset($data->$field);
		}
	}

	/**
	 * Validates the request data.
	 *
	 * This method can be overridden in subclasses to provide specific validation logic.
	 *
	 * @return array An array of validation errors, if any.
	 */
	protected function validate(): array
	{
		return [];
	}

	/**
	 * Validates the request data.
	 *
	 * Note: this never actually returns `false`. On failure it delegates
	 * to {@see errorValidating()}, which throws {@see HttpTerminationException}
	 * and terminates the request. The `bool` return exists so callers
	 * that check the return value (`if (!$this->validateItem(...))`)
	 * keep compiling; that branch is unreachable in practice because the
	 * exception is thrown first. Callers do not need to check the
	 * return value — call `validateRules()`/`validateItem()` and let a
	 * failure throw.
	 *
	 * @param object|array $data The data to validate.
	 * @param array $rules The validation rules to apply.
	 * @return bool Always true when this method returns; throws on failure.
	 * @throws HttpTerminationException When validation fails.
	 */
	protected function validateRules(object|array $data, array $rules = []): bool
	{
		if (count($rules) < 1)
		{
			return true;
		}

		$validator = Validator::create($data, $rules);
		if (!$validator->isValid())
		{
			$this->errorValidating($validator);
		}

		return true;
	}

	/**
	 * Handles validation errors by throwing an {@see HttpTerminationException}
	 * carrying a JSON error response with both a summary message and
	 * field-level error details.
	 *
	 * Previously this rendered the response and called `die` directly,
	 * which skipped any `finally`/rollback block around the call site
	 * and made the failure path untestable without killing the PHPUnit
	 * process. The exception is caught once at the router's dispatch
	 * entry point ({@see \Proto\Http\Router\Router::activateRoute()}),
	 * which renders the identical response a real HTTP request produced
	 * before this change.
	 *
	 * @param Validator $validator The validator object containing the errors.
	 * @return never
	 * @throws HttpTerminationException Always.
	 */
	protected function errorValidating(Validator $validator): never
	{
		$fieldErrors = $validator->getFieldErrors();
		$error = $this->error($validator->getMessage(), 422, $fieldErrors);
		throw new HttpTerminationException($error, 422);
	}

	/**
	 * Sets an error response and terminates the request.
	 *
	 * Throws {@see HttpTerminationException} instead of calling `die`
	 * directly; see {@see errorValidating()} for why.
	 *
	 * @param string|null $message The error message.
	 * @param int $code The HTTP status code.
	 * @return never
	 * @throws HttpTerminationException Always.
	 */
	protected function setError(?string $message = null, int $code = 400): never
	{
		$error = $this->error($message ?? 'An error occurred', $code);
		throw new HttpTerminationException($error, $code);
	}

	/**
	 * Retrieves the resource ID from the request.
	 *
	 * The route `:id` always wins. Policies authorize the route id, so a
	 * query or body `id` must never redirect the action to another row.
	 * The request `id` is only used when the route has no `:id`.
	 *
	 * @param Request $request The request object.
	 * @return int|null The resource ID or null if not found.
	 */
	protected function getResourceId(Request $request): ?int
	{
		$routeId = $this->getRouteId($request);
		if ($routeId !== null)
		{
			return $routeId;
		}

		$id = $request->getInt('id');
		return (isset($id) && is_numeric($id)) ? (int) $id : null;
	}

	/**
	 * Retrieves the numeric `:id` route parameter, if the route has one.
	 *
	 * @param Request $request The request object.
	 * @return int|null
	 */
	protected function getRouteId(Request $request): ?int
	{
		$id = $request->params()->id ?? null;
		return (isset($id) && is_numeric($id)) ? (int) $id : null;
	}

	/**
	 * Retrieves all inputs from the list request.
	 *
	 * @param Request $request The request object.
	 * @return object The collected inputs.
	 */
	public function getAllInputs(Request $request): object
	{
		$filter = $this->getFilter($request);
		$offset = $request->getInt('offset') ?? 0;
		$limit = $request->getInt('limit') ?? 50;
		$search = $request->input('search');
		$custom = $request->input('custom');
		$lastCursor = $this->scalarInput($request->input('lastCursor'));
		$sortColumns = $this->requestSortColumns();
		$dates = $this->sanitizeDateModifier($this->setDateModifier($request), $sortColumns);
		$orderBy = $this->sanitizeOrderByModifier($this->setOrderByModifier($request), $sortColumns);
		$groupBy = $this->sanitizeGroupByModifier($this->setGroupByModifier($request), $sortColumns);
		$since = $this->scalarInput($request->input('since'));

		// Enforce limit bounds to prevent unbounded or invalid queries
		$maxLimit = $this->maxLimit ?? 1000;
		$limit = max(1, min($limit, $maxLimit));
		$offset = max(0, $offset);

		return (object) [
			'filter' => $filter,
			'offset' => $offset,
			'limit' => $limit,
			'search' => $search,
			'custom' => $custom,
			'lastCursor' => $lastCursor,
			'dates' => $dates,
			'orderBy' => $orderBy,
			'groupBy' => $groupBy,
			'since' => $since,
			'modifiers' => [
				'search' => $search,
				'custom' => $custom,
				'dates' => $dates,
				'orderBy' => $orderBy,
				'groupBy' => $groupBy,
				'cursor' => $lastCursor,
				'since' => $since
			]
		];
	}

	/**
	 * Modifies the filter object based on the request.
	 *
	 * When $scopeToUser is enabled on the controller, automatically
	 * injects the session user's ID into the filter.
	 * When $routeParams is set, auto-applies route parameters to the filter.
	 * When $filterParams is set, auto-applies query string parameters to the filter.
	 *
	 * @param mixed $filter
	 * @param Request $request
	 * @return object|null
	 */
	protected function modifyFilter(?object $filter, Request $request): ?object
	{
		if (property_exists($this, 'scopeToUser') && $this->scopeToUser)
		{
			$field = $this->userScopeField ?? 'userId';
			$filter ??= (object)[];
			$filter->$field = (int)(session()->user->id ?? 0);
		}

		if (property_exists($this, 'routeParams') && !empty($this->routeParams))
		{
			$params = $request->params();
			foreach ($this->routeParams as $param => $required)
			{
				$value = (int)($params->$param ?? 0);
				if ($value)
				{
					$filter ??= (object)[];
					$filter->$param = $value;
				}
			}
		}

		if (property_exists($this, 'filterParams') && !empty($this->filterParams))
		{
			foreach ($this->filterParams as $param => $type)
			{
				$value = match ($type)
				{
					'int' => $request->getInt($param),
					'bool' => $request->getBool($param),
					default => $request->input($param),
				};

				if ($value !== null && $value !== '' && $value !== 0)
				{
					$filter ??= (object)[];
					$filter->$param = $value;
				}
			}
		}

		return $filter;
	}

	/**
	 * Decodes the raw client `filter`/`option` request input into an
	 * object, before any allowlisting or sanitization. Shared by
	 * {@see getFilter()} and {@see rawRequestFilter()} so both stay in
	 * sync on how the client payload is parsed.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	protected function decodeRequestFilter(Request $request): object
	{
		$filter = $request->input('filter') ?? $request->input('option');
		if (is_string($filter))
		{
			$filter = urldecode($filter);
		}

		return JsonFormat::decode($filter) ?? (object)[];
	}

	/**
	 * Returns the raw, decoded client filter JSON before
	 * {@see Filter::sanitizeRequestFilter()}'s model-fields allowlist runs.
	 *
	 * This is an escape hatch, not the common path: prefer
	 * `$passthroughFilterKeys` (on `ResourceController`) so client
	 * filter values still flow through `getFilter()`'s normal
	 * sanitize/qualify pipeline. Use `rawRequestFilter()` only when a
	 * filter service reads **named, unprefixed** properties off the raw
	 * client filter (e.g. `$clientFilter->isFeatured`) before building
	 * its own SQL — see `docs/RALLY_MIGRATION.md` for the `$qualifyFilters`
	 * vs custom filter service guidance.
	 *
	 * @param Request $request The request object.
	 * @return object|null The decoded client filter, or an empty object when none was sent.
	 */
	public function rawRequestFilter(Request $request): ?object
	{
		return $this->decodeRequestFilter($request);
	}

	/**
	 * This will get the filter from the request.
	 *
	 * @param Request $request The request object.
	 * @return mixed The filter criteria.
	 */
	public function getFilter(Request $request): mixed
	{
		$filter = $this->decodeRequestFilter($request);
		$filter = Filter::sanitizeRequestFilter($filter, $this->requestFilterColumns());
		return $this->modifyFilter($filter, $request);
	}

	/**
	 * Allowed request-filter columns, or null to skip the field allowlist.
	 *
	 * ResourceController passes the model's filterable fields (unioned
	 * with `$passthroughFilterKeys`, when set). App-built filters
	 * appended after getFilter() are not passed through this.
	 *
	 * @return array<int, string>|null
	 */
	protected function requestFilterColumns(): ?array
	{
		return null;
	}

	/**
	 * Allowed request `orderBy` / `groupBy` / `dates.field` columns, or
	 * null to skip the allowlist (identifier sanitizing still applies).
	 *
	 * ResourceController passes the model's sortable fields, which
	 * exclude blacklisted, secret, and PII columns.
	 *
	 * @return array<int, string>|null
	 */
	protected function requestSortColumns(): ?array
	{
		return null;
	}

	/**
	 * Keeps a cursor/marker input only when it is a scalar.
	 *
	 * @param mixed $value
	 * @return int|string|null
	 */
	protected function scalarInput(mixed $value): int|string|null
	{
		if (is_int($value) || is_string($value))
		{
			return $value === '' ? null : $value;
		}

		return is_float($value) ? (string)$value : null;
	}

	/**
	 * Drops `orderBy` keys that are not allowed sort columns.
	 *
	 * @param mixed $orderBy
	 * @param array<int, string>|null $allowed
	 * @return object|null
	 */
	protected function sanitizeOrderByModifier(mixed $orderBy, ?array $allowed): ?object
	{
		if (!is_object($orderBy) && !is_array($orderBy))
		{
			return null;
		}

		$out = [];
		foreach ((array)$orderBy as $field => $direction)
		{
			if (!is_string($field) || !is_scalar($direction) || !Filter::isAllowedRequestColumn($field, $allowed))
			{
				continue;
			}

			$out[$field] = strtoupper((string)$direction) === 'DESC' ? 'desc' : 'asc';
		}

		return $out === [] ? null : (object)$out;
	}

	/**
	 * Drops `groupBy` entries that are not allowed sort columns.
	 *
	 * @param mixed $groupBy
	 * @param array<int, string>|null $allowed
	 * @return array<int, string>|null
	 */
	protected function sanitizeGroupByModifier(mixed $groupBy, ?array $allowed): ?array
	{
		if (!is_object($groupBy) && !is_array($groupBy))
		{
			return null;
		}

		$out = [];
		foreach ((array)$groupBy as $field)
		{
			if (is_string($field) && Filter::isAllowedRequestColumn($field, $allowed))
			{
				$out[] = $field;
			}
		}

		return $out === [] ? null : $out;
	}

	/**
	 * Validates the `dates` modifier: the field must be an allowed sort
	 * column and `start` / `end` must be plain dates or datetimes.
	 * Anything else drops the whole modifier, so a client cannot run a
	 * BETWEEN comparison against an arbitrary column or value.
	 *
	 * @param object|null $dates
	 * @param array<int, string>|null $allowed
	 * @return object|null
	 */
	protected function sanitizeDateModifier(?object $dates, ?array $allowed): ?object
	{
		if ($dates === null)
		{
			return null;
		}

		$field = $dates->field ?? null;
		$start = $this->normalizeDateBound($dates->start ?? null);
		$end = $this->normalizeDateBound($dates->end ?? null);
		if (
			!is_string($field) || !Filter::isAllowedRequestColumn($field, $allowed)
			|| $start === null || $end === null
		)
		{
			return null;
		}

		return (object)[
			'field' => $field,
			'start' => $start,
			'end' => $end
		];
	}

	/**
	 * Accepts a plain date/datetime as-is, or an ISO 8601 timestamp with
	 * `Z` or a UTC offset (what `Date.prototype.toISOString()` sends),
	 * converted to the app timezone as `Y-m-d H:i:s`. Anything else is
	 * rejected (null).
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	protected function normalizeDateBound(mixed $value): ?string
	{
		if (!is_string($value))
		{
			return null;
		}

		if (Filter::isSafeTimestamp($value))
		{
			return $value;
		}

		// <input type="datetime-local"> sends "2026-10-12T10:00" (no seconds).
		if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}$/', $value))
		{
			return str_replace('T', ' ', $value) . ':00';
		}

		if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2}(?:\.\d{1,6})?)?(?:Z|[+-]\d{2}:?\d{2})$/', $value))
		{
			return null;
		}

		try
		{
			$date = new \DateTimeImmutable($value);
		}
		catch (\Exception)
		{
			return null;
		}

		return $date->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
	}

	/**
	 * Sets the date modifier for the request.
	 *
	 * @param Request $request The request object.
	 * @return object|null The date modifier object or null.
	 */
	protected function setDateModifier(Request $request): ?object
	{
		$dates = $request->json('dates');
		if ($dates === null)
		{
			return null;
		}

		if (empty($dates->field))
		{
			$dates->field = 'createdAt';
		}

		return $dates;
	}

	/**
	 * Sets the order by modifier for the request.
	 *
	 * @param Request $request The request object.
	 * @return object|null The order by modifier object or null.
	 */
	protected function setOrderByModifier(Request $request): ?object
	{
		return $request->json('orderBy') ?? null;
	}

	/**
	 * Sets the group by modifier for the request.
	 *
	 * @param Request $request The request object.
	 * @return array|object|null The group by field list or null.
	 */
	protected function setGroupByModifier(Request $request): array|object|null
	{
		$groupBy = $request->json('groupBy');
		return (is_array($groupBy) || is_object($groupBy)) ? $groupBy : null;
	}
}