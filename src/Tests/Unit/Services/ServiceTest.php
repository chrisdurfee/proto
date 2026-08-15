<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Services;

use Proto\Services\Service;
use Proto\Services\ServiceResult;
use Proto\Tests\Test;

/**
 * ServiceTest
 *
 * @package Proto\Tests\Unit\Services
 */
final class ServiceTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return Service
	 */
	private function service(): Service
	{
		return new class extends Service
		{
			public function ok(mixed $data): ServiceResult
			{
				return $this->success($data);
			}

			public function fail(string $message): ServiceResult
			{
				return $this->failure($message, 'X');
			}

			public function strip(object $data, array $fields): object
			{
				$this->restrictFields($data, $fields);
				return $data;
			}

			public function uuid(): string
			{
				return $this->generateUuid();
			}
		};
	}

	/**
	 * @return void
	 */
	public function testSuccessAndFailureHelpers(): void
	{
		$ok = $this->service()->ok(['id' => 1]);
		$this->assertTrue($ok->success);
		$this->assertEquals(['id' => 1], $ok->data);

		$fail = $this->service()->fail('Nope');
		$this->assertTrue($fail->failed());
		$this->assertEquals('Nope', $fail->error);
		$this->assertEquals('X', $fail->code);
	}

	/**
	 * @return void
	 */
	public function testRestrictFieldsAndUuid(): void
	{
		$data = (object)['id' => 1, 'secret' => 'x', 'name' => 'Ada'];
		$data = $this->service()->strip($data, ['secret']);
		$this->assertFalse(isset($data->secret));
		$this->assertEquals('Ada', $data->name);

		$uuid = $this->service()->uuid();
		$this->assertMatchesRegularExpression(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
			$uuid
		);
	}
}
