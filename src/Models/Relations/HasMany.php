<?php declare(strict_types=1);
namespace Proto\Models\Relations;

use Proto\Models\Model;

/**
 * Class HasMany
 *
 * Defines a one-to-many relationship.
 *
 * @package Proto\Models\Relations
 */
class HasMany
{
	/**
	 * Related model class.
	 *
	 * @var string
	 */
	protected string $related;

	/**
	 * Foreign key on related table.
	 *
	 * @var string
	 */
	protected string $foreignKey;

	/**
	 * Local key on this model.
	 *
	 * @var string
	 */
	protected string $localKey;

	/**
	 * Parent model instance.
	 *
	 * @var Model
	 */
	protected Model $parent;

	/**
	 * Constructor.
	 *
	 * @param string $related Related model class.
	 * @param string $foreignKey FK on related table.
	 * @param string $localKey PK on this model.
	 * @param Model $parent Parent model instance.
	 */
	public function __construct(
		string $related,
		string $foreignKey,
		string $localKey,
		Model $parent
	)
	{
		$this->related = $related;
		$this->foreignKey = $foreignKey;
		$this->localKey = $localKey;
		$this->parent = $parent;
	}

	/**
	 * Get results of the relationship (array of related models).
	 *
	 * @return array|null
	 */
	public function getResults(): ?array
	{
		$localValue = $this->parent->{$this->localKey};
		if ($localValue === null)
		{
			return null;
		}

		return ($this->related)::fetchWhere([
			[$this->foreignKey, $localValue]
		]);
	}

	/**
	 * Get all related rows with filters, offsets, and limits.
	 *
	 * @param mixed $filter Filter conditions.
	 * @param int|null $offset Offset for pagination.
	 * @param int|null $limit Limit for pagination.
	 * @param array|null $modifiers Additional query modifiers.
	 * @return object|false
	 */
	public function all(mixed $filter = null, int|null $offset = null, int|null $limit = null, array|null $modifiers = null): object|false
	{
		$localValue = $this->parent->{$this->localKey};
		if ($localValue === null)
		{
			return false;
		}

		// Add the relationship constraint to the filter
		$relationFilter = [[$this->foreignKey, $localValue]];

		if ($filter === null)
		{
			$filter = $relationFilter;
		}
		elseif (is_array($filter))
		{
			$filter = array_merge($relationFilter, $filter);
		}

		return ($this->related)::all($filter, $offset, $limit, $modifiers);
	}

	/**
	 * Count related records.
	 *
	 * @param mixed $filter Filter conditions.
	 * @param array|null $modifiers Additional query modifiers.
	 * @return object|false
	 */
	public function count(mixed $filter = null, array|null $modifiers = null): object|false
	{
		$localValue = $this->parent->{$this->localKey};
		if ($localValue === null)
		{
			return false;
		}

		// Add the relationship constraint to the filter
		$relationFilter = [[$this->foreignKey, $localValue]];

		if ($filter === null)
		{
			$filter = $relationFilter;
		}
		elseif (is_array($filter))
		{
			$filter = array_merge($relationFilter, $filter);
		}

		return ($this->related)::count($filter, $modifiers);
	}
}
