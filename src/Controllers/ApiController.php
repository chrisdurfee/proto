<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Http\Router\Request;
use Proto\Utils\Format\JsonFormat;
use Proto\Api\Validator;

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
	 * The item key used in requests.
	 *
	 * @var string
	 */
	protected string $item = 'item';

	/**
	 * Retrieves the request item from the request object.
	 *
	 * @param Request $request The request object.
	 * @return object The request item.
	 */
	public function getRequestItem(Request $request): object
	{
		return $request->json($this->item) ?? (object) $request->all();
	}

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
	 * @param object|array $data The data to validate.
	 * @param array $rules The validation rules to apply.
	 * @return bool True if validation passes, false otherwise.
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
			return false;
		}

		return true;
	}

	/**
	 * Handles validation errors by encoding the error message and rendering it as JSON.
	 *
	 * @param Validator $validator The validator object containing the error message.
	 * @return void
	 */
	protected function errorValidating(Validator $validator): void
    {
        $this->setError($validator->getMessage());
    }

	/**
	 * Sets a successful response and renders it as JSON.
	 *
	 * @param mixed $data The response data.
	 * @return void
	 */
	protected function setError(?string $message = null, int $code = 400): array
	{
		$error = $this->error($message ?? 'An error occurred', $code);
		JsonFormat::encodeAndRender($error);
        die;
	}

	/**
	 * Retrieves the resource ID from the request.
	 *
	 * @param Request $request The request object.
	 * @return int|null The resource ID or null if not found.
	 */
	protected function getResourceId(Request $request): ?int
	{
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
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
		$lastCursor = $request->input('lastCursor') ?? null;
		$dates = $this->setDateModifier($request);
		$orderBy = $this->setOrderByModifier($request);
		$groupBy = $this->setGroupByModifier($request);
		$since = $request->input('since') ?? null;

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
	 * @param mixed $filter
	 * @param Request $request
	 * @return object|null
	 */
	protected function modifyFilter(?object $filter, Request $request): ?object
	{
		return $filter;
	}

	/**
	 * This will get the filter from the request.
	 *
	 * @param Request $request The request object.
	 * @return mixed The filter criteria.
	 */
	public function getFilter(Request $request): mixed
	{
		$filter = $request->input('filter') ?? $request->input('option');
		if (is_string($filter))
		{
			$filter = urldecode($filter);
		}

		$filter = JsonFormat::decode($filter) ?? (object)[];
		return $this->modifyFilter($filter, $request);
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
	 * @return object|null The group by modifier object or null.
	 */
	protected function setGroupByModifier(Request $request): ?object
	{
		return $request->json('groupBy') ?? null;
	}
}