<?php declare(strict_types=1);
namespace Proto\Error;

/**
 * ServiceUnavailableException
 *
 * Base class for failures caused by a backing service being down
 * (database, session store). The error handler renders these as a
 * generic 503 with `Retry-After`, so clients can show an outage state
 * and retry instead of treating the failure as a bug or as "no data".
 *
 * @package Proto\Error
 */
class ServiceUnavailableException extends \RuntimeException
{
}
