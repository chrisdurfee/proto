<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Storage\Filter;
use Proto\Tests\Test;

/**
 * PassthroughFilterKeysTest
 *
 * Verifies `$passthroughFilterKeys` and `rawRequestFilter()` on
 * {@see ResourceController}: the escape hatch that replaces Rally's
 * `VIRTUAL_FILTER_KEYS` + private `rawClientFilter()` pattern.
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class PassthroughFilterKeysTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * A passthrough key survives `sanitizeRequestFilter()` even though
	 * it is not a real model field.
	 *
	 * @return void
	 */
	public function testPassthroughKeySurvivesSanitizeEvenWhenNotAModelField(): void
	{
		$controller = $this->passthroughController(['isFeatured']);

		$allowed = $controller->exposeFilterColumns();
		$this->assertContains('isFeatured', $allowed);
		$this->assertContains('status', $allowed, 'Model fields must still be allowed alongside passthrough keys.');

		$result = Filter::sanitizeRequestFilter(
			(object)[
				'isFeatured' => true,
				'status' => 'published'
			],
			$allowed
		);

		$values = (array)$result;
		$this->assertArrayHasKey('isFeatured', $values);
		$this->assertTrue($values['isFeatured']);
		$this->assertArrayHasKey('status', $values);
	}

	/**
	 * A key that is neither a model field nor a passthrough key is
	 * still dropped.
	 *
	 * @return void
	 */
	public function testNonPassthroughNonFieldKeyIsStillDropped(): void
	{
		$controller = $this->passthroughController(['isFeatured']);
		$allowed = $controller->exposeFilterColumns();

		$result = Filter::sanitizeRequestFilter(
			(object)[
				'isFeatured' => true,
				'notAllowedAtAll' => 'x'
			],
			$allowed
		);

		$values = (array)$result;
		$this->assertArrayHasKey('isFeatured', $values);
		$this->assertArrayNotHasKey('notAllowedAtAll', $values);
	}

	/**
	 * Array values on a passthrough key still go through the IN-cap /
	 * promotion logic (plain array shorthand promoted to an IN tuple,
	 * scalar values coerced into a one-element IN list).
	 *
	 * @return void
	 */
	public function testPassthroughKeyArrayValueIsStillPromotedAndCapped(): void
	{
		$controller = $this->passthroughController(['tagIds']);
		$allowed = $controller->exposeFilterColumns();

		$result = Filter::sanitizeRequestFilter(
			(object)['tagIds' => [1, 2, 3]],
			$allowed
		);

		$values = (array)$result;
		$this->assertArrayHasKey('tagIds', $values);
		$this->assertSame(['IN', [1, 2, 3]], $values['tagIds']);

		$params = [];
		$sql = Filter::format(['tagIds', ...$values['tagIds']], $params, false);
		$this->assertSame('tagIds IN (?, ?, ?)', $sql);
		$this->assertSame([1, 2, 3], $params);
	}

	/**
	 * rawRequestFilter() returns the raw decoded client filter JSON,
	 * bypassing the allowlist entirely — the pre-existing escape hatch
	 * for filter services that read named properties directly.
	 *
	 * @return void
	 */
	public function testRawRequestFilterReturnsUnfilteredDecodedJson(): void
	{
		$controller = $this->passthroughController([]);

		$request = new class extends Request
		{
			public function input(string $name, mixed $default = null): mixed
			{
				if ($name === 'filter')
				{
					return json_encode(['isFeatured' => true, 'email' => 'a@b.c']);
				}

				return parent::input($name, $default);
			}
		};

		$raw = $controller->rawRequestFilter($request);
		$this->assertNotNull($raw);
		$this->assertTrue($raw->isFeatured);
		$this->assertSame('a@b.c', $raw->email, 'rawRequestFilter() is not allowlisted; secrets are the caller\'s responsibility.');
	}

	/**
	 * @param array<int, string> $passthroughFilterKeys
	 * @return ResourceController
	 */
	private function passthroughController(array $passthroughFilterKeys): ResourceController
	{
		return new class($passthroughFilterKeys) extends ResourceController
		{
			public function __construct(private array $keys)
			{
				$this->model = PassthroughCaptureModel::class;
				$this->passthroughFilterKeys = $this->keys;
				parent::__construct();
			}

			public function exposeFilterColumns(): ?array
			{
				return $this->requestFilterColumns();
			}

			public function rawRequestFilter(Request $request): ?object
			{
				return parent::rawRequestFilter($request);
			}
		};
	}
}

/**
 * @package Proto\Tests\Unit\Controllers
 */
final class PassthroughCaptureModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'passthrough_capture';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'status', 'email', 'password'];
}
