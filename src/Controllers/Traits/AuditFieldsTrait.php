<?php declare(strict_types=1);
namespace Proto\Controllers\Traits;

use Proto\Models\Model;

/**
 * AuditFieldsTrait
 *
 * Automatically injects audit fields (createdBy, updatedBy, deletedBy, etc.)
 * into model instances or plain data objects based on the session user.
 *
 * Used by ResourceController to keep audit logic decoupled from CRUD operations.
 *
 * @package Proto\Controllers\Traits
 */
trait AuditFieldsTrait
{
	/**
	 * Adds user data to the model for creation.
	 *
	 * Sets the createdBy, authorId, and userId fields to the current
	 * user's ID if they exist on the model and are not already set.
	 *
	 * @param Model $model The model instance to enrich.
	 * @return void
	 */
	protected function getAddUserData(Model $model): void
	{
		$userId = session()->user->id ?? null;
		if ($model->has('createdBy') && !isset($model->createdBy))
		{
			$model->createdBy = $userId;
		}

		if ($model->has('authorId') && !isset($model->authorId))
		{
			$model->authorId = $userId;
		}

		if ($model->has('userId') && !isset($model->userId))
		{
			$model->userId = $userId;
		}
	}

	/**
	 * Adds user data to the model for updates.
	 *
	 * Sets the updatedBy and editedBy fields to the current user's ID
	 * if they exist on the model and are not already set.
	 *
	 * @param Model $model The model instance to enrich.
	 * @return void
	 */
	protected function getUpdateUserData(Model $model): void
	{
		$userId = session()->user->id ?? null;
		if ($model->has('updatedBy') && !isset($model->updatedBy))
		{
			$model->updatedBy = $userId;
		}

		if ($model->has('editedBy') && !isset($model->editedBy))
		{
			$model->editedBy = $userId;
		}
	}

	/**
	 * Adds user data to the model for deletions.
	 *
	 * Sets the deletedBy, removedBy, and archivedBy fields to the current
	 * user's ID if they exist on the model and are not already set.
	 *
	 * @param Model $model The model instance to enrich.
	 * @return void
	 */
	protected function getDeleteUserData(Model $model): void
	{
		$userId = session()->user->id ?? null;
		if ($model->has('deletedBy') && !isset($model->deletedBy))
		{
			$model->deletedBy = $userId;
		}

		if ($model->has('removedBy') && !isset($model->removedBy))
		{
			$model->removedBy = $userId;
		}

		if ($model->has('archivedBy') && !isset($model->archivedBy))
		{
			$model->archivedBy = $userId;
		}
	}

	/**
	 * Injects audit fields into a plain data object before service delegation.
	 *
	 * Sets the current session user's ID on each field that is not already set.
	 * This provides the same audit data injection that getAddUserData/getUpdateUserData
	 * perform on Model instances, but for plain objects passed to services.
	 *
	 * @param object &$data The data object to inject audit fields into.
	 * @param array $fields The audit field names to inject.
	 * @return void
	 */
	protected function injectAuditData(object &$data, array $fields): void
	{
		$userId = session()->user->id ?? null;
		if ($userId === null)
		{
			return;
		}

		foreach ($fields as $field)
		{
			if (!isset($data->$field))
			{
				$data->$field = $userId;
			}
		}
	}
}
