<?php declare(strict_types=1);
namespace Proto\Controllers\Traits;

/**
 * UserEnrichmentTrait
 *
 * Attaches session user fields to add/update response data so the UI
 * can render the author's name, avatar, etc. without a refetch.
 *
 * Configure by setting $enrichUserFields on the controller.
 *
 * @package Proto\Controllers\Traits
 */
trait UserEnrichmentTrait
{
	/**
	 * Session user fields to attach to add/update responses.
	 *
	 * @var array
	 */
	protected array $enrichUserFields = [];

	/**
	 * Attaches session user fields to a response data object.
	 *
	 * Used after add/update to enrich the response with author data
	 * so the UI can render immediately without a refetch.
	 *
	 * @param object &$data The response data to enrich.
	 * @return void
	 */
	protected function attachUserFields(object &$data): void
	{
		if (empty($this->enrichUserFields))
		{
			return;
		}

		$user = session()->user ?? null;
		if ($user === null)
		{
			return;
		}

		foreach ($this->enrichUserFields as $field)
		{
			$data->$field = $user->$field ?? null;
		}
	}
}
