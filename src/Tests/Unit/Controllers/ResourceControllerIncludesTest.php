<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Proto\Tests\Test;

/**
 * ResourceControllerIncludesTest
 *
 * @package Proto\Tests\Unit\Controllers
 */
final class ResourceControllerIncludesTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return ResourceController
	 */
	private function controller(): ResourceController
	{
		return new class extends ResourceController
		{
			public function __construct()
			{
				$this->allowedIncludes = ['author', 'stats'];
				$this->defaultIncludes = ['author'];
				parent::__construct();
			}
		};
	}

	/**
	 * @return void
	 */
	public function testRequestedIncludesAllowlistAndDefaults(): void
	{
		$previous = $_REQUEST['include'] ?? null;
		$_GET['include'] = 'author,stats,secret';
		$_REQUEST['include'] = 'author,stats,secret';

		try
		{
			$includes = $this->controller()->requestedIncludes(new Request());
			$this->assertEquals(['author', 'stats'], $includes);
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['include'], $_REQUEST['include']);
			}
			else
			{
				$_GET['include'] = $previous;
				$_REQUEST['include'] = $previous;
			}
		}
	}

	/**
	 * Unknown include names are dropped when nothing is allowlisted.
	 *
	 * @return void
	 */
	public function testEmptyAllowlistIgnoresQueryIncludes(): void
	{
		$controller = new class extends ResourceController
		{
			public function __construct()
			{
				parent::__construct();
			}
		};

		$previous = $_REQUEST['include'] ?? null;
		$_GET['include'] = 'author';
		$_REQUEST['include'] = 'author';

		try
		{
			$this->assertSame([], $controller->requestedIncludes(new Request()));
		}
		finally
		{
			if ($previous === null)
			{
				unset($_GET['include'], $_REQUEST['include']);
			}
			else
			{
				$_GET['include'] = $previous;
				$_REQUEST['include'] = $previous;
			}
		}
	}
}
