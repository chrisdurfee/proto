<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Database\Migrations\Guide;
use Proto\Database\Migrations\Models\Migration;
use Proto\Http\Router\Request;

/**
 * MigrationController
 *
 * Handles migration operations such as running, reverting, and retrieving migration records.
 *
 * @package Modules\Developer\Controllers
 */
class MigrationController extends Controller
{
	/**
	 * Initializes the migration guide service.
	 *
	 * @param Guide|null $service Migration guide service instance.
	 * @return void
	 */
	public function __construct(
		protected ?Guide $service = new Guide()
	)
	{
		parent::__construct();
	}

	/**
	 * Handles migration requests.
	 *
	 * @param Request $req The request object containing migration parameters.
	 * @return object Response object.
	 */
	public function apply(Request $req): object
	{
		$direction = $req->input('direction');
		return $this->update($direction);
	}

	/**
	 * Updates migrations based on the provided direction.
	 *
	 * Supported directions:
	 * - "up": Runs pending migrations.
	 * - "down": Reverts the last executed migrations.
	 *
	 * @param string $direction Direction of migration execution.
	 * @return object Response object.
	 */
	public function update(string $direction): object
	{
		$result = false;

		switch ($direction)
		{
			case 'up':
				$result = $this->run();
				break;
			case 'down':
				$result = $this->revert();
				break;
		}

		return $this->response($result);
	}

	/**
	 * Runs pending migrations.
	 *
	 * @return bool Result of running migrations.
	 */
	public function run(): bool
	{
		return $this->service->run();
	}

	/**
	 * Reverts the last executed migrations.
	 *
	 * @return bool Result of reverting migrations.
	 */
	public function revert(): bool
	{
		return $this->service->revert();
	}

	/**
	 * Sets the filter for migration records.
	 *
	 * @param string|null $filter Optional filter for migration rows.
	 * @return array Filter array.
	 */
	protected function setFilter(?string $filter): array
	{
		if (empty($filter))
		{
			return [];
		}

		$obj = json_decode(urldecode($filter)) ?? (object)[];
		return (array)$obj;
	}

	/**
	 * Retrieves migration records.
	 *
	 * @param mixed $filter Optional filter for migration rows.
	 * @param int|null $offset Optional offset for pagination.
	 * @param int|null $limit Optional count of rows to retrieve.
	 * @param array|null $modifiers Optional query modifiers.
	 * @return object Response object containing migration records.
	 */
	public function all(
		Request $request
	): object
	{
		$filter = $request->input('filter');
		$filter = $this->setFilter($filter);

		$offset = $request->getInt('offset');
		$limit = $request->getInt('limit');
		$search = $request->input('search');
		$custom = $request->input('custom');

		$result = Migration::all($filter, $offset, $limit, [
			'search' => $search,
			'custom' => $custom
		]);
		return $this->response($result);
	}
}