<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Cache;

use Proto\Cache\Policies\ModelPolicy;
use Proto\Controllers\Controller;
use Proto\Http\Router\Request;
use Proto\Tests\Test;

/**
 * ModelPolicyKeysTest
 *
 * @package Proto\Tests\Unit\Cache
 */
final class ModelPolicyKeysTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return ModelPolicy
	 */
	private function policy(): ModelPolicy
	{
		$controller = new class extends Controller {};
		return new class($controller) extends ModelPolicy
		{
			public function exposePattern(string $method, mixed $params): string
			{
				return $this->createKeyPattern($method, $params);
			}

			public function exposeIdentities(Request $request, mixed $id = null, ?object $item = null): array
			{
				$identities = [];
				if ($id !== null && $id !== '')
				{
					$identities[] = $id;
				}

				if ($item !== null)
				{
					foreach (['id', 'guid', 'slug', 'uuid'] as $field)
					{
						$value = $item->$field ?? null;
						if ($value !== null && $value !== '')
						{
							$identities[] = $value;
						}
					}
				}

				$raw = $request->input('id') ?? $request->params()->id ?? null;
				if ($raw !== null && $raw !== '')
				{
					$identities[] = $raw;
				}

				return array_values(array_unique($identities, SORT_REGULAR));
			}
		};
	}

	/**
	 * @return void
	 */
	public function testGetPatternDoesNotCoverIncludeSuffix(): void
	{
		$policy = $this->policy();
		$base = $policy->exposePattern('get', 5);
		$this->assertStringEndsWith(':get:5', $base);
		$this->assertStringEndsWith(':get:5:*', $base . ':*');
	}

	/**
	 * @return void
	 */
	public function testGetPatternKeepsSlugIdentity(): void
	{
		$policy = $this->policy();
		$this->assertStringEndsWith(':get:my-slug', $policy->exposePattern('get', 'my-slug'));
	}

	/**
	 * @return void
	 */
	public function testInvalidateCollectsSlugAndNumericIdentities(): void
	{
		$previous = $_REQUEST['id'] ?? null;
		$_GET['id'] = 'my-slug';
		$_REQUEST['id'] = 'my-slug';

		try
		{
			$identities = $this->policy()->exposeIdentities(
				new Request(),
				12,
				(object)['id' => 12, 'slug' => 'my-slug', 'guid' => 'abc']
			);
			$this->assertContains(12, $identities);
			$this->assertContains('my-slug', $identities);
			$this->assertContains('abc', $identities);
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['id'], $_REQUEST['id']);
			}
			else
			{
				$_GET['id'] = $previous;
				$_REQUEST['id'] = $previous;
			}
		}
	}
}
