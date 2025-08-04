<?php declare(strict_types=1);
namespace Proto\Models\Joins;

use Proto\Utils\Strings;
use ReflectionClass;

/**
 * BelongsToHelper
 *
 * Provides reusable steps for constructing a many-to-many (belongs-to-many)
 * chain of ModelJoin calls in JoinBuilder-based classes.
 *
 * @package Proto\Models\Joins
 */
class BelongsToHelper
{
	/**
	 * 1) Extract the “self” base name (snake_case short class) from the owner model.
	 *
	 * @param string $ownerModelClass Fully-qualified owner model class name.
	 * @return string Snake-cased short class name (e.g. "user").
	 */
	public static function inferSelfBase(string $ownerModelClass): string
	{
		$shortName = (new ReflectionClass($ownerModelClass))->getShortName();
		return Strings::snakeCase($shortName);
	}

	/**
	 * 2) Extract the “other” base name (snake_case short class) from the related model.
	 *
	 * @param string $relatedModelClass Fully-qualified related model class name.
	 * @return string Snake-cased short class name (e.g. "role").
	 */
	public static function inferOtherBase(string $relatedModelClass): string
	{
		$shortName = (new ReflectionClass($relatedModelClass))->getShortName();
		return Strings::snakeCase($shortName);
	}

	/**
	 * 3) Determine the pivot-table name by sorting two bases and pluralizing.
	 *
	 * @param string $selfBase  Snake-cased owner base (e.g. "user").
	 * @param string $otherBase Snake-cased related base (e.g. "role").
	 * @return string Pivot table name (e.g. "role_users").
	 */
	public static function inferPivotTable(string $selfBase, string $otherBase): string
	{
		$tables = [ $selfBase, $otherBase ];
		sort($tables, SORT_STRING);
		return implode('_', $tables) . 's';
	}

	/**
	 * 4) Infer foreign Pivot column name on pivot table for the “self” model.
	 *
	 * @param string $selfBase Snake-cased owner base (e.g. "user").
	 * @return string Foreign-key column name (e.g. "user_id").
	 */
	public static function inferForeignPivot(string $selfBase): string
	{
		return $selfBase . '_id';
	}

	/**
	 * 5) Infer foreign Pivot column name on pivot table for the “other” model.
	 *
	 * @param string $otherBase Snake-cased related base (e.g. "role").
	 * @return string Foreign-key column name (e.g. "role_id").
	 */
	public static function inferRelatedPivot(string $otherBase): string
	{
		return $otherBase . '_id';
	}

	/**
	 * 6) Create the “pivot” join on JoinBuilder:
	 *    - join(pivotTable, alias=pivotTable)
	 *    - left()
	 *    - on([ 'id', foreignPivot ])
	 *    - multiple()
	 *
	 * @param JoinBuilder $builder
	 * @param string $pivotTable Pivot table name
	 * @param string $foreignPivot Foreign-key column on pivot table
	 * @param string[] $pivotFields Optional list of columns to select from pivot
	 * @return ModelJoin The ModelJoin for the pivot-table side.
	 */
	public static function createPivotJoin(
		JoinBuilder $builder,
		string $pivotTable,
		string $foreignPivot,
		array $pivotFields = []
	): ModelJoin
	{
		$pivotJoin = $builder
			->join($pivotTable, $pivotTable)
			->left()
			->on([ 'id', $foreignPivot ])
			->multiple();

		if (!empty($pivotFields))
		{
			$pivotJoin->fields(...$pivotFields);
		}

		return $pivotJoin;
	}

	/**
	 * 6) Create the “pivot” join on JoinBuilder:
	 *    - join(pivotTable, alias=pivotTable)
	 *    - left()
	 *    - on([ 'id', foreignPivot ])
	 *    - multiple()
	 *
	 * @param JoinBuilder $builder
	 * @param string $pivotTable Pivot table name
	 * @param string $foreignPivot Foreign-key column on pivot table
	 * @param string[] $pivotFields Optional list of columns to select from pivot
	 * @return ModelJoin The ModelJoin for the pivot-table side.
	 */
	public static function createChildPivotJoin(
		JoinBuilder $builder,
		string $pivotTable,
		string $foreignPivot,
		array $pivotFields = []
	): ModelJoin
	{
		$pivotJoin = $builder
			->createJoin($pivotTable, $pivotTable)
			->left()
			->on([ 'id', $foreignPivot ])
			->multiple();

		if (!empty($pivotFields))
		{
			$pivotJoin->fields(...$pivotFields);
		}

		return $pivotJoin;
	}

	/**
	 * 7) Chain from pivot → related table:
	 *    - obtains a new linked JoinBuilder via ->join($relatedModelClass)
	 *    - calls createChildModelJoin(...) to attach the ModelJoin
	 *    - sets multiple‐flag on pivotJoin via setMultipleJoin(...)
	 *    - adds on([ relatedPivot, relatedIdKey ])
	 *    - adds fields from the related model (or uses defaults)
	 *
	 * @param ModelJoin $pivotJoin The ModelJoin returned by createPivotJoin()
	 * @param string $relatedModelClass Fully-qualified related model class
	 * @param string[] $relatedFields Optional columns to select from related table
	 * @return ModelJoin The final ModelJoin on the related table.
	 */
	public static function createFinalJoin(
		ModelJoin $pivotJoin,
		string $relatedModelClass,
		array $relatedFields = []
	): ModelJoin
	{
		// (a) Build a linked JoinBuilder off of the pivotJoin
		$linkedBuilder = $pivotJoin->join($relatedModelClass);

		// (b) Use createChildModelJoin to produce the “pivot → related” join
		$finalJoin = $pivotJoin->createChildModelJoin(
			$linkedBuilder,
			$relatedModelClass,
			'left'
		);

		// (c) Mark that pivotJoin has a “multiple” child join
		$pivotJoin->setMultipleJoin($finalJoin);

		// (d) Add the appropriate ON‐clause: pivot.relatedPivot = related.id
		$otherBase = static::inferOtherBase($relatedModelClass);
		$relatedPivot = static::inferRelatedPivot($otherBase);
		$finalJoin->on([ $relatedPivot, $relatedModelClass::idKeyName() ]);

		// (e) Decide which fields to pull from related—use all if none passed
		if (count($relatedFields) < 1)
		{
			$relatedFields = $relatedModelClass::fields();
		}

		$finalJoin->fields(...$relatedFields);
		return $finalJoin;
	}

	/**
	 * Sets up the pivot join for a belongs-to-many relationship.
	 *
	 * @param JoinBuilder $builder
	 * @param string $relatedModelClass
	 * @param array $pivotFields
	 * @return ModelJoin
	 */
	public static function setupPivotJoin(
		JoinBuilder $builder,
		string $relatedModelClass,
		array $pivotFields = []
	): ModelJoin
	{
		$ownerModelClass = $builder->getOwnerModelClass();
		$selfBase = static::inferSelfBase($ownerModelClass);
		$otherBase = static::inferOtherBase($relatedModelClass);
		$pivotTable = static::inferPivotTable($selfBase, $otherBase);
		$foreignPivot = static::inferForeignPivot($selfBase);

		return static::createPivotJoin(
			$builder,
			$pivotTable,
			$foreignPivot,
			$pivotFields
		);
	}

	/**
	 * Sets up the pivot join for a belongs-to-many relationship.
	 *
	 * @param JoinBuilder $builder
	 * @param string $relatedModelClass
	 * @param array $pivotFields
	 * @return ModelJoin
	 */
	public static function setupChildPivotJoin(
		JoinBuilder $builder,
		string $relatedModelClass,
		array $pivotFields = []
	): ModelJoin
	{
		$ownerModelClass = $builder->getOwnerModelClass();
		$selfBase = static::inferSelfBase($ownerModelClass);
		$otherBase = static::inferOtherBase($relatedModelClass);
		$pivotTable = static::inferPivotTable($selfBase, $otherBase);
		$foreignPivot = static::inferForeignPivot($selfBase);

		return static::createChildPivotJoin(
			$builder,
			$pivotTable,
			$foreignPivot,
			$pivotFields
		);
	}

	/**
	 * 8) Master method: calls all of the above steps in sequence,
	 * returning the final ModelJoin for pivot→related.
	 *
	 * @param JoinBuilder $builder
	 * @param string $relatedModelClass Fully-qualified related model class
	 * @param string[] $relatedFields Optional columns from related table
	 * @param string[] $pivotFields Optional columns from pivot table
	 * @return ModelJoin
	 */
	public static function createBelongsToMany(
		JoinBuilder $builder,
		string $relatedModelClass,
		array $relatedFields = [],
		array $pivotFields = []
	): ModelJoin
	{
		$pivotJoin = static::setupPivotJoin(
			$builder,
			$relatedModelClass,
			$pivotFields
		);

		return static::createFinalJoin(
			$pivotJoin,
			$relatedModelClass,
			$relatedFields
		);
	}
}
