<?php declare(strict_types=1);
namespace Proto\Models\Relations;

use Proto\Models\Model;
use Proto\Utils\Strings;

/**
 * Class BelongsToMany
 *
 * Handles many-to-many queries and pivot-table operations using the storage's query builder.
 *
 * @package Proto\Models\Relations
 */
class BelongsToMany
{
	/**
	 * Related model class (e.g. Role::class).
	 *
	 * @var string
	 */
	protected string $related;

	/**
	 * Pivot table name (e.g. 'role_user').
	 *
	 * @var string
	 */
	protected string $pivotTable;

	/**
	 * Foreign key column on pivot for the parent model (e.g. 'user_id').
	 *
	 * @var string
	 */
	protected string $foreignPivot;

	/**
	 * Foreign key column on pivot for the related model (e.g. 'role_id').
	 *
	 * @var string
	 */
	protected string $relatedPivot;

	/**
	 * Primary key column on the parent model (e.g. 'id').
	 *
	 * @var string
	 */
	protected string $parentKey;

	/**
	 * Primary key column on the related model (e.g. 'id').
	 *
	 * @var string
	 */
	protected string $relatedKey;

	/**
	 * The parent model instance.
	 *
	 * @var Model
	 */
	protected Model $parent;

	/**
	 * BelongsToMany constructor.
	 *
	 * @param string $related Related model class.
	 * @param string $pivotTable Pivot table name.
	 * @param string $foreignPivot FK on pivot for this model.
	 * @param string $relatedPivot FK on pivot for the related model.
	 * @param string $parentKey PK on this model.
	 * @param string $relatedKey PK on related model.
	 * @param Model $parent Parent model instance.
	 */
	public function __construct(
		string $related,
		string $pivotTable,
		string $foreignPivot,
		string $relatedPivot,
		string $parentKey,
		string $relatedKey,
		Model $parent
	)
	{
		$this->related = $related;
		$this->pivotTable = $pivotTable;
		$this->foreignPivot = $foreignPivot;
		$this->relatedPivot = $relatedPivot;
		$this->parentKey = $parentKey;
		$this->relatedKey = $relatedKey;
		$this->parent = $parent;
	}

	/**
	 * Get the base select query for fetching related models.
	 *
	 * @return object
	 */
	protected function getSelectQuery(): object
	{
		$query = $this->buildBaseQuery();
		$on = "p.{$this->relatedPivot} = r.{$this->relatedKey}";

		$joinDef = [
			'table' => $this->pivotTable,
			'alias' => 'p',
			'type' => 'inner JOIN',
			'on' => [$on],
			'fields' => null
		];

		return $query
			->join($joinDef)
			->where("p.{$this->foreignPivot} = ?");
	}

	/**
	 * Get all related model instances for this parent.
	 *
	 * @return object[]
	 */
	public function getResults(): array
	{
		$parentId = $this->getParentId();

		$query = $this->getSelectQuery();
		$rows = $query->fetch([$parentId]);

		$instance = new $this->related();
		return $instance->convertRows($rows);
	}

	/**
	 * Get all related model instances for this parent.
	 *
	 * @return object[]
	 */
	public function all(): array
	{
		$parentId = $this->getParentId();

		$query = $this->getSelectQuery();
		$rows = $query->fetch([$parentId]);

		$instance = new $this->related();
		return $instance->convertRows($rows);
	}

	/**
	 * Attach one or more related IDs to this parent.
	 *
	 * @param int|int[]|array<int,array> $ids Single ID, array of IDs, or [id => extraData].
	 * @param array $extra Additional pivot data when attaching a single ID.
	 * @return bool
	 */
	public function attach($ids, array $extra = []): bool
	{
		$parentId = $this->getParentId();
		$toInsert = $this->prepareAttachRows($ids, $extra, $parentId);
		$isSnake = $this->parent->isSnakeCase();

		$success = true;
		foreach ($toInsert as $row)
		{
			$data = $isSnake
				? $this->snakeCaseKeys($row)
				: $row;

			$result = $this->parent
				->storage
				->insertInto($this->pivotTable, (object)$data);

			if (!$result)
			{
				$success = false;
				break; // stop on first failure
			}
		}
		return $success;
	}

	/**
	 * Detach one or more related IDs from this parent.
	 *
	 * @param int|int[] $ids Single ID or array of IDs.
	 * @return bool
	 */
	public function detach($ids): bool
	{
		$parentId = $this->getParentId();
		$isSnake = $this->parent->isSnakeCase();

		$success = true;
		foreach ((array)$ids as $rid)
		{
			$whereClauses = $isSnake
				? [
					Strings::snakeCase($this->foreignPivot) . ' = ?',
					Strings::snakeCase($this->relatedPivot) . ' = ?'
				]
				: [
					"{$this->foreignPivot} = ?",
					"{$this->relatedPivot} = ?"
				];

			$params = [$parentId, $rid];

			$result = $this->parent
				->storage
				->deleteFrom($this->pivotTable, $whereClauses, $params);

			if (!$result)
			{
				$success = false;
				break; // stop on first failure
			}
		}
		return $success;
	}

	/**
	 * Sync pivot so that exactly the given IDs remain attached.
	 *
	 * @param int[] $ids
	 * @return bool
	 */
	public function sync(array $ids): bool
	{
		$current = array_map(
			fn($m): int => $m->{$this->relatedKey},
			$this->getResults()
		);

		$toDetach = array_diff($current, $ids);
		$toAttach = array_diff($ids, $current);
		if ($toDetach !== [])
		{
			$result = $this->detach(array_values($toDetach));
			if (!$result)
			{
				return false;
			}
		}

		if ($toAttach !== [])
		{
			$result = $this->attach(array_values($toAttach));
			if (!$result)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Toggle given IDs on the pivot (attach if missing, detach if present).
	 *
	 * @param int[] $ids
	 * @return bool
	 */
	public function toggle(array $ids): bool
	{
		$current = array_map(
			fn($m): int => $m->{$this->relatedKey},
			$this->getResults()
		);

		$attach = [];
		$detach = [];

		foreach ($ids as $rid)
		{
			if (in_array($rid, $current, true))
			{
				$detach[] = $rid;
			}
			else
			{
				$attach[] = $rid;
			}
		}

		if ($detach !== [])
		{
			$result = $this->detach($detach);
			if (!$result)
			{
				return false;
			}
		}

		if ($attach !== [])
		{
			$result = $this->attach($attach);
			if (!$result)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Build the base query for selecting related rows.
	 *
	 * @return object
	 */
	protected function buildBaseQuery(): object
	{
		$relatedTable = ($this->related)::table();
		return $this->parent
			->storage
			->table($relatedTable, 'r')
			->select(['r.*']);
	}

	/**
	 * Prepare row data for attach() calls.
	 *
	 * @param int|int[]|array<int,array> $ids
	 * @param array $extra
	 * @param int $parentId
	 * @return array<int,array>
	 */
	protected function prepareAttachRows($ids, array $extra, int $parentId): array
	{
		$rows = [];

		if (is_array($ids))
		{
			foreach ($ids as $key => $val)
			{
				if (is_int($key))
				{
					// Numeric array: [2,3,4]
					$rows[] = [
						$this->foreignPivot => $parentId,
						$this->relatedPivot => $val,
						...$extra
					];
				}
				else
				{
					// Associative: [2 => ['meta' => 'x'], 5 => ['meta' => 'y']]
					$rows[] = [
						$this->foreignPivot => $parentId,
						$this->relatedPivot => $key,
						...$val
					];
				}
			}
		}
		else
		{
			// Single ID
			$rows[] = [
				$this->foreignPivot => $parentId,
				$this->relatedPivot => $ids,
				...$extra
			];
		}

		return $rows;
	}

	/**
	 * Convert array keys to snake_case.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function snakeCaseKeys(array $data): array
	{
		$result = [];

		foreach ($data as $key => $value)
		{
			$result[Strings::snakeCase($key)] = $value;
		}

		return $result;
	}

	/**
	 * Get the parent model's primary key value.
	 *
	 * @return int
	 */
	protected function getParentId(): int
	{
		return (int)$this->parent->{$this->parentKey};
	}
}