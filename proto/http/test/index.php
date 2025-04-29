<?php

include_once __DIR__ . '/../../autoload.php';

use Proto\Http\Router\Router;

$router = new Router('/public/proto/http/test/');

// this will get params from the url
$router->get('patients/:id/', function($req, $params)
{
	$id = $req->input('module');

	return $params;
});

// this will redirect
$router->redirect('patients/:id/', './appointments/', 302);

// this will return a response code
$router->get('patients/:id?/', function($req, $params)
{
	// this will set a response code
	$params->code = 301;

	// this will json encode the value
	return $params;
});

$router->post('patients/:id?/', function($req, $params)
{
	// this will json encode the value
	return $params;
});

$router->get('appoinmtents/*', function($req, $params)
{
	$file = $req->file('fileName');

	// this will json encode the value
	return $params;
});

// this will route on anything
$router->get('*', function($req, $params)
{
	var_dump($params);
});
