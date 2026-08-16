<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ModelQualifyFilterTest
 *
 * @package Proto\Tests\Unit\Models
 */
final class ModelQualifyFilterTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Unqualified model fields pick up the table alias.
	 *
	 * @return void
	 */
	public function testQualifyFilterPrefixesIdAndStatus(): void
	{
		$result = QualifyFilterVehicleModel::qualifyFilter([
			'id' => 39,
			'status' => 'active',
			'v.year' => 2018
		]);

		$this->assertSame(39, $result['v.id']);
		$this->assertSame('active', $result['v.status']);
		$this->assertSame(2018, $result['v.year']);
		$this->assertArrayNotHasKey('id', $result);
	}

	/**
	 * Object filters qualify the same way list endpoints do.
	 *
	 * @return void
	 */
	public function testQualifyFilterObjectLeavesDottedKeys(): void
	{
		$result = QualifyFilterVehicleModel::qualifyFilter((object)[
			'id' => 9,
			'v.makeId' => 3
		]);

		$this->assertSame(9, $result->{'v.id'});
		$this->assertSame(3, $result->{'v.makeId'});
		$this->assertFalse(isset($result->id));
	}
}

/**
 * @package Proto\Tests\Unit\Models
 */
final class QualifyFilterVehicleModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'vehicles';

	/**
	 * @var string|null
	 */
	protected static ?string $alias = 'v';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'status', 'year', 'makeId'];
}
