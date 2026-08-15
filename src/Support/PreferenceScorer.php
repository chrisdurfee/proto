<?php declare(strict_types=1);
namespace Proto\Support;

/**
 * PreferenceScorer
 *
 * Weighted overlap scoring for discovery / for-you feeds.
 * Dimension counts come from the app; this class only applies weights,
 * featured boosts, geo decay, and mismatch penalties.
 *
 * @package Proto\Support
 */
class PreferenceScorer
{
	/**
	 * @param array<string, float> $multipliers Dimension => weight.
	 * @param float $featuredMultiplier Applied when options['featured'] is true.
	 * @param float $geoMaxBonus Maximum miles-based bonus.
	 * @param float $geoDecayMiles Distance at which the geo bonus reaches zero.
	 * @param float $mismatchPenalty Multiplier when a dimension has explicit
	 *        targets and zero overlap (0 = hard exclude).
	 */
	public function __construct(
		protected array $multipliers = [],
		protected float $featuredMultiplier = 1.3,
		protected float $geoMaxBonus = 2.0,
		protected float $geoDecayMiles = 100.0,
		protected float $mismatchPenalty = 0.0
	)
	{
	}

	/**
	 * Score one candidate.
	 *
	 * @param array<string, int|float> $overlapCounts Hits per dimension.
	 * @param array{
	 *     featured?: bool,
	 *     distanceMiles?: float|null,
	 *     targeted?: array<string, bool>
	 * } $options
	 * @return float
	 */
	public function score(array $overlapCounts, array $options = []): float
	{
		$score = 0.0;
		$targeted = $options['targeted'] ?? [];
		$mismatched = false;

		foreach ($this->multipliers as $dimension => $weight)
		{
			$hits = (float)($overlapCounts[$dimension] ?? 0);
			if ($hits <= 0 && !empty($targeted[$dimension]))
			{
				$mismatched = true;
				continue;
			}

			$score += $hits * $weight;
		}

		if (!empty($options['featured']))
		{
			$score *= $this->featuredMultiplier;
		}

		$distance = $options['distanceMiles'] ?? null;
		if ($distance !== null && $this->geoDecayMiles > 0)
		{
			$ratio = max(0.0, 1.0 - ((float)$distance / $this->geoDecayMiles));
			$score += $this->geoMaxBonus * $ratio;
		}

		if ($mismatched)
		{
			$score *= $this->mismatchPenalty;
		}

		return $score;
	}
}
