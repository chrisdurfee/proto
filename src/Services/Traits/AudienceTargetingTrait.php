<?php declare(strict_types=1);
namespace Proto\Services\Traits;

/**
 * AudienceTargetingTrait
 *
 * Shared multi-dimensional targeting pattern for services.
 *
 * Provides reusable get/save methods for audience targeting dimensions
 * (e.g., brands, vehicle types, models, interests). Services using this
 * trait must implement getTargetingConfig() to define their dimensions.
 *
 * @package Proto\Services\Traits
 */
trait AudienceTargetingTrait
{
	/**
	 * Returns the targeting configuration.
	 *
	 * Each key is a dimension name, with 'model' (class), 'fk' (foreign key
	 * on the target model), and optional 'valueField' (defaults to 'targetId').
	 *
	 * Example:
	 * ```php
	 * return [
	 *     'brands' => ['model' => EventBrandTarget::class, 'fk' => 'eventId'],
	 *     'vehicleTypes' => ['model' => EventVehicleTypeTarget::class, 'fk' => 'eventId'],
	 *     'interests' => ['model' => EventInterestTarget::class, 'fk' => 'eventId', 'valueField' => 'interestId'],
	 * ];
	 * ```
	 *
	 * @return array<string, array{model: string, fk: string, valueField?: string}>
	 */
	abstract protected function getTargetingConfig(): array;

	/**
	 * Get all targeting dimensions for an entity.
	 *
	 * @param int $entityId The entity's primary key.
	 * @return object Object with keys matching getTargetingConfig().
	 */
	public function getTargeting(int $entityId): object
	{
		$config = $this->getTargetingConfig();
		$result = (object)[];

		foreach ($config as $key => $cfg)
		{
			$result->$key = $cfg['model']::fetchWhere([$cfg['fk'] => $entityId]) ?? [];
		}

		return $result;
	}

	/**
	 * Save targeting dimensions for an entity (delete-then-insert).
	 *
	 * @param int $entityId The entity's primary key.
	 * @param object $targets Object with keys matching getTargetingConfig().
	 * @return void
	 */
	public function saveTargets(int $entityId, object $targets): void
	{
		$config = $this->getTargetingConfig();

		foreach ($config as $key => $cfg)
		{
			$existing = $cfg['model']::fetchWhere([$cfg['fk'] => $entityId]);
			foreach ($existing ?? [] as $record)
			{
				$cfg['model']::remove($record->id);
			}

			$items = $targets->$key ?? [];
			$valueField = $cfg['valueField'] ?? 'targetId';
			foreach ($items as $item)
			{
				$record = new $cfg['model']((object)[
					$cfg['fk'] => $entityId,
					$valueField => $item
				]);
				$record->add();
			}
		}
	}
}
