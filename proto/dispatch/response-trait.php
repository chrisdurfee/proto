<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * ResponseTrait
 *
 * This will create a response trait that can
 * be used to create response types.
 *
 * @package Proto\Dispatch
 */
trait ResponseTrait
{
    /**
     * This will create response set to error.
     *
     * @param string $message
     * @return Response
     */
    protected function error(string $message): Response
    {
        return new Response(true, $message);
    }

    /**
     * This will create a response.
     *
     * @param bool $error
     * @param string $message
     * @param mixed $data
     * @return Response
     */
    protected function response(
        bool $error = false,
        string $message = '',
        mixed $data = null
    ): Response
    {
        return Response::create($error, $message, $data);
    }
}