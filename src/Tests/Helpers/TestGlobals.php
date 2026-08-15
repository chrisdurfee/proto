<?php declare(strict_types=1);

if (!function_exists('router'))
{
	/**
	 * @return \Proto\Http\Router\Router
	 */
	function router(): \Proto\Http\Router\Router
	{
		if (!isset($GLOBALS['router']) || !$GLOBALS['router'] instanceof \Proto\Http\Router\Router)
		{
			throw new \RuntimeException('router() is not bound for this test.');
		}

		return $GLOBALS['router'];
	}
}

if (!function_exists('session'))
{
	/**
	 * @return object
	 */
	function session(): object
	{
		return (object)['user' => $GLOBALS['protoTestActor'] ?? null];
	}
}
