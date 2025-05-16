<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Database\Migrations\Guide;
use Proto\Database\Migrations\Models\Migration;

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
	 * Retrieves migration records.
	 *
	 * @param mixed $filter Optional filter for migration rows.
	 * @param int|null $offset Optional offset for pagination.
	 * @param int|null $count Optional count of rows to retrieve.
	 * @param array|null $modifiers Optional query modifiers.
	 * @return object Response object containing migration records.
	 */
	public function all(
		$filter = null,
		?int $offset = null,
		?int $count = null,
		?array $modifiers = null
	): object
	{
		$result = Migration::all($filter, $offset, $count, $modifiers);
		return $this->response($result);
	}
}