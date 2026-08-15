<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Support;

use Proto\Support\PreferenceScorer;
use Proto\Support\SeenDemotion;
use Proto\Support\UnionFeed;
use Proto\Tests\Test;

/**
 * FeedHelpersTest
 *
 * @package Proto\Tests\Unit\Support
 */
final class FeedHelpersTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testPreferenceScorerWeightsAndFeatured(): void
	{
		$scorer = new PreferenceScorer(['make' => 2.0, 'style' => 1.0], 1.5);
		$score = $scorer->score(['make' => 2, 'style' => 1], ['featured' => true]);
		$this->assertEquals(7.5, $score);
	}

	/**
	 * Mismatch penalty applies to the finished score, not mid-loop.
	 *
	 * @return void
	 */
	public function testPreferenceScorerMismatchIsOrderIndependent(): void
	{
		$scorer = new PreferenceScorer(['make' => 2.0, 'style' => 3.0], 1.0, 0.0, 100.0, 0.0);
		$first = $scorer->score(['make' => 0, 'style' => 2], ['targeted' => ['make' => true]]);
		$last = $scorer->score(['make' => 2, 'style' => 0], ['targeted' => ['style' => true]]);
		$this->assertEquals(0.0, $first);
		$this->assertEquals(0.0, $last);
	}

	/**
	 * @return void
	 */
	public function testPreferenceScorerGeoDecay(): void
	{
		$scorer = new PreferenceScorer([], 1.0, 2.0, 100.0);
		$near = $scorer->score([], ['distanceMiles' => 0]);
		$far = $scorer->score([], ['distanceMiles' => 100]);
		$this->assertEquals(2.0, $near);
		$this->assertEquals(0.0, $far);
	}

	/**
	 * Hard exclude (penalty 0) must zero featured + geo, not add geo after.
	 *
	 * @return void
	 */
	public function testPreferenceScorerMismatchZerosGeoAndFeatured(): void
	{
		$scorer = new PreferenceScorer(['make' => 2.0], 1.5, 2.0, 100.0, 0.0);
		$score = $scorer->score(
			['make' => 0],
			['targeted' => ['make' => true], 'featured' => true, 'distanceMiles' => 0]
		);
		$this->assertEquals(0.0, $score);
	}

	/**
	 * @return void
	 */
	public function testSeenDemotionBuckets(): void
	{
		$now = strtotime('2026-08-15 12:00:00');
		$demotion = new SeenDemotion();
		$this->assertEquals(1.0, $demotion->factor(null, $now));
		$this->assertEquals(0.2, $demotion->factor('2026-08-15 10:00:00', $now));
		$this->assertEquals(0.5, $demotion->factor('2026-08-12 12:00:00', $now));
		$this->assertEquals(0.8, $demotion->factor('2026-07-01 12:00:00', $now));
		$this->assertEquals(2.0, $demotion->apply(10.0, '2026-08-15 10:00:00', $now));
	}

	/**
	 * @return void
	 */
	public function testUnionFeedMergesSortsAndPaginates(): void
	{
		$feed = (new UnionFeed())
			->source('posts', fn(): array => [
				(object)['id' => 1, 'sortDate' => '2026-01-01'],
				(object)['id' => 2, 'sortDate' => '2026-03-01']
			])
			->source('listings', fn(): array => [
				['id' => 3, 'sortDate' => '2026-02-01']
			])
			->orderBy('sortDate', 'desc');

		$page = $feed->paginate(0, 2);
		$this->assertEquals(3, $page->total);
		$this->assertCount(2, $page->rows);
		$this->assertEquals(2, $page->rows[0]->id);
		$this->assertEquals('posts', $page->rows[0]->source);
		$this->assertEquals(3, $page->rows[1]->id);
		$this->assertEquals('listings', $page->rows[1]->source);
		$this->assertEquals(3, $page->lastCursor);
	}
}
