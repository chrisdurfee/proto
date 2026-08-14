<?php declare(strict_types=1);
namespace Proto\Controllers\Traits;

use Proto\Controllers\Traits\BatchEnrichmentTrait;

/**
 * CurrentUserFlagsTrait
 *
 * Standardized batch-enrichment for "did the current user X this row?"
 * questions (liked, bookmarked, saved, favorited, following, etc).
 *
 * Usage on a ResourceController:
 *
 *   use Proto\Controllers\Traits\CurrentUserFlagsTrait;
 *
 *   class EventController extends ResourceController
 *   {
 *       use CurrentUserFlagsTrait;
 *
 *       protected array $currentUserFlags = [
 *           'isBookmarked' => [
 *               'model' => Bookmark::class,
 *               'foreignKey' => 'itemId',
 *               'extraFilter' => [['itemType', 'event']],
 *           ],
 *       ];
 *
 *       protected function enrichRows(array &$rows, Request $request): void
 *       {
 *           $userId = session()->user->id ?? null;
 *           $this->enrichCurrentUserFlags($rows, $userId ? (int)$userId : null);
 *       }
 *   }
 *
 * Each entry in `$currentUserFlags` is keyed by the field name set on the
 * row, and accepts:
 *
 *  - `model`        (string, required) Fully-qualified Model class.
 *  - `foreignKey`   (string, required) Column on the related model.
 *  - `userField`    (string, optional) User id column. Default `userId`.
 *  - `extraFilter`  (array,  optional) Extra filter rows.
 *  - `sourceKey`    (string, optional) Column on the row. Default `id`.
 *
 * If the user is not signed in, all declared flag fields are still set to
 * `false` so frontend code never has to null-check them.
 *
 * @package Proto\Controllers\Traits
 */
trait CurrentUserFlagsTrait
{
	use BatchEnrichmentTrait;

	/**
	 * Apply every declared flag to the supplied rows.
	 *
	 * @param array $rows
	 * @param int|null $userId
	 * @return void
	 */
	protected function enrichCurrentUserFlags(array &$rows, ?int $userId): void
	{
		if (empty($this->currentUserFlags) || empty($rows))
		{
			return;
		}

		if ($userId === null || $userId <= 0)
		{
			foreach ($this->currentUserFlags as $field => $_)
			{
				foreach ($rows as &$row)
				{
					$row->$field = false;
				}
				unset($row);
			}
			return;
		}

		foreach ($this->currentUserFlags as $field => $config)
		{
			$model = $config['model'] ?? null;
			$foreignKey = $config['foreignKey'] ?? null;
			if ($model === null || $foreignKey === null)
			{
				continue;
			}

			$userField = $config['userField'] ?? 'userId';
			$sourceKey = $config['sourceKey'] ?? 'id';
			$extraFilter = $config['extraFilter'] ?? [];

			$filter = array_merge(
				[[$userField, $userId]],
				$extraFilter
			);

			$this->batchMapExists(
				$rows,
				$model,
				$foreignKey,
				$field,
				$filter,
				$sourceKey
			);
		}
	}
}
