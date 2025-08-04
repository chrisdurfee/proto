<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * DispatchInterface
 *
 * This interface defines a contract for dispatching responses.
 *
 * @package Proto\Dispatch
 */
interface DispatchInterface
{
	/**
	 * Sends the dispatch request and returns a response.
	 *
	 * @return Response The dispatched response object.
	 */
	public function send(): Response;
}