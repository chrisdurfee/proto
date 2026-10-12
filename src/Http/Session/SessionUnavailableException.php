<?php declare(strict_types=1);
namespace Proto\Http\Session;

use Proto\Error\ServiceUnavailableException;

/**
 * SessionUnavailableException
 *
 * Thrown when the configured session store (Redis or database) cannot
 * be reached and no fallback is configured. Rendered as a 503.
 *
 * @package Proto\Http\Session
 */
class SessionUnavailableException extends ServiceUnavailableException
{
}
