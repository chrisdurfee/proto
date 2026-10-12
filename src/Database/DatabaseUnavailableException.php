<?php declare(strict_types=1);
namespace Proto\Database;

use Proto\Error\ServiceUnavailableException;

/**
 * DatabaseUnavailableException
 *
 * Thrown when a query cannot run because the database connection
 * could not be opened (host down, too many connections, bad
 * credentials). The error handler renders it as a 503 so clients can
 * show an outage state and retry, instead of treating it as "no rows".
 *
 * @package Proto\Database
 */
class DatabaseUnavailableException extends ServiceUnavailableException
{
}
