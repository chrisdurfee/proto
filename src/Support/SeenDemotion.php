<?php declare(strict_types=1);
namespace Proto\Support;

/**
 * SeenDemotion
 *
 * Multipliers for ranking content the viewer has already seen.
 * Unseen stays 1.0; recent impressions are demoted hardest.
 *
 * @package Proto\Support
 */
class SeenDemotion
{
	/**
	 * @param float $unseen Factor when there is no impression.
	 * @param float $recent Factor when first seen within $recentHours.
	 * @param float $mid Factor when first seen within $midDays.
	 * @param float $old Factor when first seen earlier than $midDays.
	 * @param float $recentHours
	 * @param float $midDays
	 */
	public function __construct(
		protected float $unseen = 1.0,
		protected float $recent = 0.2,
		protected float $mid = 0.5,
		protected float $old = 0.8,
		protected float $recentHours = 24.0,
		protected float $midDays = 7.0
	)
	{
	}

	/**
	 * Ranking factor for a single item.
	 *
	 * @param string|null $firstViewedAt MySQL/ISO datetime, or null if unseen.
	 * @param int|null $now Unix timestamp (defaults to time()).
	 * @return float
	 */
	public function factor(?string $firstViewedAt, ?int $now = null): float
	{
		if ($firstViewedAt === null || $firstViewedAt === '')
		{
			return $this->unseen;
		}

		$seen = strtotime($firstViewedAt);
		if ($seen === false)
		{
			return $this->unseen;
		}

		$now ??= time();
		$ageSeconds = max(0, $now - $seen);
		if ($ageSeconds <= $this->recentHours * 3600)
		{
			return $this->recent;
		}

		if ($ageSeconds <= $this->midDays * 86400)
		{
			return $this->mid;
		}

		return $this->old;
	}

	/**
	 * Apply the factor to a score.
	 *
	 * @param float $score
	 * @param string|null $firstViewedAt
	 * @param int|null $now
	 * @return float
	 */
	public function apply(float $score, ?string $firstViewedAt, ?int $now = null): float
	{
		return $score * $this->factor($firstViewedAt, $now);
	}
}
