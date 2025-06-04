<?php declare(strict_types=1);
namespace Proto\Models\Relations;

use Proto\Database\QueryBuilder\Select;
use Proto\Models\Model;

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
	 * Get all related model instances for this parent.
	 *
	 * @return Model[]
	 */
	public function getResults(): array
	{
		$query = $this->buildBaseQuery();
		$parentId = $this->getParentId();
		$on = "{$this->pivotTable}.{$this->relatedPivot} = r.{$this->relatedKey}";

		$joinDef = [
			'table' => $this->pivotTable,
			'alias' => 'p',
			'type' => 'inner',
			'on' => [$on],
			'fields' => null
		];

		$rows = $query
			->join([$joinDef])
			->where("p.{$this->foreignPivot} = ?")
			->fetch([$parentId]);

		return array_map(
			fn(object $row): Model => new ($this->related)($row),
			$rows
		);
	}

	/**
	 * Attach one or more related IDs to this parent.
	 *
	 * @param int|int[]|array<int,array> $ids Single ID, array of IDs, or [id => extraData].
	 * @param array $extra Additional pivot data when attaching a single ID.
	 * @return void
	 */
	public function attach($ids, array $extra = []): void
	{
		$parentId = $this->getParentId();
		$toInsert = $this->prepareAttachRows($ids, $extra, $parentId);

		foreach ($toInsert as $row)
		{
			$this->parent
				->storage()
				->table($this->pivotTable)
				->insert((object)$row);
		}
	}

	/**
	 * Detach one or more related IDs from this parent.
	 *
	 * @param int|int[] $ids Single ID or array of IDs.
	 * @return void
	 */
	public function detach($ids): void
	{
		$parentId = $this->getParentId();
		$table = $this->parent
			->storage()
			->table($this->pivotTable);

		foreach ((array)$ids as $rid)
		{
			$table
				->delete()
				->where([
					"{$this->foreignPivot} = ?",
					"{$this->relatedPivot} = ?"
				])
				->execute([$parentId, $rid]);
		}
	}

	/**
	 * Sync pivot so that exactly the given IDs remain attached.
	 *
	 * @param int[] $ids
	 */
	public function sync(array $ids): void
	{
		$current = array_map(
			fn(Model $m): int => $m->{$this->relatedKey},
			$this->getResults()
		);

		$toDetach = array_diff($current, $ids);
		$toAttach = array_diff($ids, $current);
		if ($toDetach !== [])
		{
			$this->detach(array_values($toDetach));
		}

		if ($toAttach !== [])
		{
			$this->attach(array_values($toAttach));
		}
	}

	/**
	 * Toggle given IDs on the pivot (attach if missing, detach if present).
	 *
	 * @param int[] $ids
	 */
	public function toggle(array $ids): void
	{
		$current = array_map(
			fn(Model $m): int => $m->{$this->relatedKey},
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
			$this->detach($detach);
		}

		if ($attach !== [])
		{
			$this->attach($attach);
		}
	}

	/**
	 * Build the base query for selecting related rows.
	 *
	 * @return Select
	 */
	protected function buildBaseQuery(): Select
	{
		$relatedTable = ($this->related)::table();
		return $this->parent
			->storage()
			->table($relatedTable, 'r')
			->select('r.*');
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
					// Associative: [2 => ['meta'=>'x'], 5 => ['meta'=>'y']]
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
	 * Get the parent model's primary key value.
	 *
	 * @return int
	 */
	protected function getParentId(): int
	{
		return (int)$this->parent->{$this->parentKey};
	}
}