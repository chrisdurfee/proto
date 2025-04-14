<?php declare(strict_types=1);
namespace Modules\User\Auth\Policies;

/**
 * ContentPolicy
 *
 * This policy handles access control for content-related actions.
 *
 * @package Modules\User\Auth\Policies
 */
class ContentPolicy extends Policy
{
	/**
	 * Default policy for methods that don't have an explicit policy method.
	 *
	 * @return bool True if the user can access the default policy, otherwise false.
	 */
	public function default(): bool
	{
		return $this->canAccess('content.view');
	}

	/**
	 * Example: can the user "view all" content?
	 *
	 * @param mixed $filter The filter criteria for retrieving content.
	 * @param ?int $offset The offset for pagination.
	 * @param ?int $count The number of items to retrieve.
	 * @param ?array $modifiers Additional modifiers for the query.
	 * @return bool True if the user can view all content, otherwise false.
	 */
	public function all(
		mixed $filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null
	): bool
	{
		return $this->canAccess('content.view');
	}

	/**
	 * Example: can the user "get" a single content resource?
	 *
	 * @param mixed $id The ID of the content to retrieve.
	 * @return bool True if the user can access the content, otherwise false.
	 */
	public function get(mixed $id): bool
	{
		return $this->canAccess('content.view');
	}

	/**
	 * Example: can the user "create" new content?
	 *
	 * @param object $data The data for the new content.
	 * @return bool True if the user can create content, otherwise false.
	 */
	public function add(object $data): bool
	{
		return $this->canAccess('content.create');
	}

	/**
	 * Example: can the user "update" existing content?
	 *
	 * @param object $data The data for the content to update.
	 * @return bool True if the user can update content, otherwise false.
	 */
	public function update(object $data): bool
	{
		return $this->canAccess('content.edit');
	}

	/**
	 * Example: can the user "delete" content?
	 *
	 * @param mixed $data The data for the content to delete.
	 * @return bool True if the user can delete content, otherwise false.
	 */
	public function delete(mixed $data): bool
	{
		return $this->canAccess('content.delete');
	}

	/**
	 * Example: can the user "publish" content?
	 *
	 * @param mixed $id The ID of the content to publish.
	 * @return bool True if the user can publish content, otherwise false.
	 */
	public function publish(mixed $id): bool
	{
		return $this->canAccess('content.publish');
	}

	/**
	 * Example: can the user "search" among content?
	 *
	 * @param mixed $search The search criteria.
	 * @return bool True if the user can search content, otherwise false.
	 */
	public function search(mixed $search): bool
	{
		return $this->canAccess('content.view');
	}

	/**
	 * Another example: can the user "updateStatus" of content?
	 *
	 * @param mixed $id The ID of the content to update the status.
	 * @param mixed $status The new status value.
	 * @return bool True if the user can update the status, otherwise false.
	 */
	public function updateStatus(mixed $id, $status): bool
	{
		return $this->canAccess('content.edit');
	}
}
