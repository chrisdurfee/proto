<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * PivotModel
 *
 * Base model for write-once pivot/junction tables.
 *
 * Provides default immutable fields appropriate for pivot records
 * that should not be modified after creation (e.g., likes, bookmarks,
 * follows, memberships).
 *
 * @package Proto\Models
 * @abstract
 */
abstract class PivotModel extends Model
{
	/**
	 * Fields that cannot be modified after creation.
	 *
	 * Pivot records are typically write-once, so the user who
	 * created the record and the creation timestamp are immutable.
	 *
	 * @var array
	 */
	protected static array $immutableFields = ['userId', 'createdAt', 'createdBy'];
}
