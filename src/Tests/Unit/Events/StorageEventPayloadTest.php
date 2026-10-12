<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Events;

use Proto\Events\Events;
use Proto\Models\Model;
use Proto\Storage\Storage;
use Proto\Tests\Test;

/**
 * StorageEventPayloadTest
 *
 * Every storage call used to re-convert its rows to build an event
 * payload even when nothing was subscribed, doubling conversion cost
 * on list reads. The payload is now only built for listeners.
 *
 * @package Proto\Tests\Unit\Events
 */
final class StorageEventPayloadTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		PayloadSpyModel::$conversions = 0;
	}

	/**
	 * @return void
	 */
	public function testNoListenersSkipsPayloadConversion(): void
	{
		$model = new PayloadSpyModel();
		$model->storage->ping();

		$this->assertSame(0, PayloadSpyModel::$conversions);
	}

	/**
	 * @return void
	 */
	public function testListenerReceivesConvertedPayload(): void
	{
		$received = null;
		$token = Events::on('PayloadSpyModel:ping', function (mixed $payload) use (&$received): void
		{
			$received = $payload;
		});

		try
		{
			$model = new PayloadSpyModel();
			$model->storage->ping();
		}
		finally
		{
			Events::off('PayloadSpyModel:ping', (string)$token);
		}

		$this->assertSame(1, PayloadSpyModel::$conversions);
		$this->assertNotNull($received);
		$this->assertFalse(Events::listening('PayloadSpyModel:ping'));
	}
}

/**
 * @package Proto\Tests\Unit\Events
 */
final class PayloadSpyStorage extends Storage
{
	/**
	 * @return object
	 */
	public function ping(): object
	{
		return (object)['rows' => [(object)['id' => 1]]];
	}
}

/**
 * @package Proto\Tests\Unit\Events
 */
final class PayloadSpyModel extends Model
{
	/**
	 * @var int
	 */
	public static int $conversions = 0;

	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'payload_spy';

	/**
	 * @var array
	 */
	protected static array $fields = ['id'];

	/**
	 * @var string
	 */
	protected static string $storageType = PayloadSpyStorage::class;

	/**
	 * @param array $rows
	 * @return array
	 */
	public function convertRows(array $rows): array
	{
		self::$conversions++;
		return parent::convertRows($rows);
	}
}
