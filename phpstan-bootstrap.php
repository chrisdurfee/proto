<?php declare(strict_types=1);

/**
 * PHPStan bootstrap.
 *
 * Defines the runtime constants Proto normally sets while booting
 * (Proto\Base::setBasePath() and src/Config.php) so static analysis can
 * resolve them without reading common/Config/.env or connecting to services.
 */

if (!defined('BASE_PATH'))
{
	define('BASE_PATH', __DIR__);
}

if (!defined('ENV_URL'))
{
	define('ENV_URL', 'http://localhost');
}
