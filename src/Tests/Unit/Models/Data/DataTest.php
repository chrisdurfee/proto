<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Models\Data;

use PHPUnit\Framework\TestCase;
use Proto\Models\Data\Data;
use Proto\Models\Joins\JoinBuilder;
use Proto\Models\Joins\ModelJoin;

/**
 * Test Data mapping with join field collisions.
 */
class DataTest extends TestCase
{
	/**
	 * Creates a ModelJoin with the given fields.
	 *
	 * @param mixed ...$fields Join fields.
	 * @return ModelJoin
	 */
	protected function createJoin(mixed ...$fields): ModelJoin
	{
		$joins = [];
		$builder = new JoinBuilder($joins, 'post_media', 'pm', true);
		$join = new ModelJoin($builder, 'users', 'u', true);
		$join->fields(...$fields);
		return $join;
	}

	/**
	 * A join field that collides with one of the model's own columns
	 * must not shadow the column: map() must still include it in
	 * write payloads.
	 */
	public function testOwnColumnIsNotShadowedByCollidingJoinField(): void
	{
		$join = $this->createJoin('status', 'email');

		$data = new Data(
			['id', 'status', 'filename'],
			[$join]
		);

		$data->set('status', 'processing');
		$data->set('filename', 'a.jpg');
		$data->set('email', 'joined@example.com');

		$mapped = $data->map();

		$this->assertTrue(property_exists($mapped, 'status'), 'Own column must survive a join field collision');
		$this->assertSame('processing', $mapped->status);
		$this->assertSame('a.jpg', $mapped->filename);
		$this->assertFalse(property_exists($mapped, 'email'), 'Non-colliding join fields must be excluded from writes');
	}

	/**
	 * Aliased join fields must still be excluded from write payloads
	 * without affecting the model's own column.
	 */
	public function testAliasedJoinFieldIsExcludedFromWrites(): void
	{
		$join = $this->createJoin(['status', 'userStatus'], 'email');

		$data = new Data(
			['id', 'status', 'filename'],
			[$join]
		);

		$data->set('status', 'processing');
		$data->set('userStatus', 'online');

		$mapped = $data->map();

		$this->assertTrue(property_exists($mapped, 'status'));
		$this->assertSame('processing', $mapped->status);
		$this->assertFalse(property_exists($mapped, 'userStatus'), 'Aliased join fields must be excluded from writes');
	}
}
