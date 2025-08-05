<?php declare(strict_types=1);
namespace Proto\Models\Relations;

use Proto\Models\Model;

/**
 * Class BelongsTo
 *
 * Defines an inverse relationship (many-to-one or one-to-one).
 *
 * @package Proto\Models\Relations
 */
class BelongsTo
{
	/**
	 * Related model class.
	 *
	 * @var string
	 */
	protected string $related;

	/**
	 * Foreign key on this table.
	 *
	 * @var string
	 */
	protected string $foreignKey;

	/**
	 * Owner key on related model.
	 *
	 * @var string
	 */
	protected string $ownerKey;

	/**
	 * Child model instance.
	 *
	 * @var Model
	 */
	protected Model $child;

	/**
	 * Constructor.
	 *
	 * @param string $related Related model class.
	 * @param string $foreignKey FK on this table.
	 * @param string $ownerKey PK on related model.
	 * @param Model $child Child model instance.
	 */
	public function __construct(
		string $related,
		string $foreignKey,
		string $ownerKey,
		Model $child
	)
	{
		$this->related = $related;
		$this->foreignKey = $foreignKey;
		$this->ownerKey = $ownerKey;
		$this->child = $child;
	}

	/**
	 * Get result of the relationship (single parent model).
	 *
	 * @return object|null
	 */
	public function getResults(): ?object
	{
		$foreignValue = $this->child->{$this->foreignKey};
		if ($foreignValue === null)
		{
			return null;
		}

		return ($this->related)::get($foreignValue);
	}
}
