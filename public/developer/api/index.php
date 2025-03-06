<?php declare(strict_types=1);
include_once __DIR__ . '/../app/autoload.php';

use Proto\Http\Router\Router;
use Developer\App\Auth\Auth;
use Developer\App\Controllers\GeneratorController;
use Developer\App\Controllers\MigrationController;
use Developer\App\Controllers\TableController;
use Developer\App\Controllers\ErrorController;

/**
 * This will check that the server env is set to dev.
 */
Auth::validate();

$router = new Router('/developer/api/');

/**
 * This will create a new server resource from the generator.
 */
$router->post('generator', function(string $req, $params)
{
	$resource = $req::json('resource');
	$type = $req::input('type');

	$controller = new GeneratorController();
	return $controller->addByType($type, $resource);
});

/**
 * This will setup a list filter.
 *
 * @param string|null $filter
 * @return array
 */
function getFilter(?string $filter): array
{
	if (empty($filter))
	{
		return [];
	}

	$obj = json_decode(urldecode($filter)) ?? (object)[];
	return (array)$obj;
}

/**
 * This will get all migrations added to the migration table.
 */
$router->get('migration*', function($req, $params)
{
	$filter = getFilter($req::input('filter'));
	$offset = $req::getInt('start');
	$start = !empty($offset)? $offset : 0;
	$count = $req::getInt('stop');
	$search = $req::input('search');
	$custom = $req::input('custom');

	$controller = new MigrationController();
	return $controller->all($filter, $start, $count, [
		'search' => $search,
		'custom' => $custom
	]);
});

/**
 * This will migrate the database up or down.
 */
$router->post('migration', function($req, $params)
{
	$direction = $req::input('direction');

	$controller = new MigrationController();
	return $controller->update($direction);
});

/**
 * This will setup a list filter.
 *
 * @param mixed $filter
 * @return string[][]
 */
function setFilter(?string $filter): array
{
	$filter = strtolower($filter ?? '');
	if (empty($filter) || $filter === 'all')
	{
		return [];
	}

	return [
		['env', $filter]
	];
}

/**
 * This will get all migrations added to the migration table.
 */
$router->get('error*', function($req, $params)
{
	$filter = setFilter($req::input('filter'));
	$offset = $req::getInt('start');
	$start = !empty($offset)? $offset : 0;
	$count = $req::getInt('stop');
	$search = $req::input('search');
	$custom = $req::input('custom');

	$controller = new ErrorController();
	return $controller->all($filter, $start, $count, [
		'search' => $search,
		'custom' => $custom
	]);
});

/**
 * This will get all migrations added to the migration table.
 */
$router->patch('error', function($req, $params)
{
	$id = $req::getInt('id');
	$resolved = $req::getInt('resolved');

	$controller = new ErrorController();
	return $controller->updateResolved($id, $resolved);
});

/**
 * This will get the table columns.
 */
$router->get('table/columns*', function($req, $params)
{
	$connection = $req::input('connection');
	$tableName = $req::input('tableName');

	if(!$connection || !$tableName)
	{
		return [];
	}

	$controller = new TableController($connection, $tableName);
	return $controller->getColumns();
});
