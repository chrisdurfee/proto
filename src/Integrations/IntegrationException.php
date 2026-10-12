<?php declare(strict_types=1);
namespace Proto\Integrations;

/**
 * IntegrationException
 *
 * Thrown when an upstream integration (token exchange, API call)
 * fails in a way the caller must handle.
 *
 * @package Proto\Integrations
 */
class IntegrationException extends \RuntimeException
{
}
