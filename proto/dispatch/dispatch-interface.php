<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * DispatchInterface
 *
 * This will create a dispatch interface.
 *
 * @package Proto\Dispatch
 */
interface DispatchInterface
{
    /**
     * This will send the dispatch.
     *
     * @return Response
     */
    public function send(): Response;
}