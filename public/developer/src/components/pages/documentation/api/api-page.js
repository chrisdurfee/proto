import { Code, H4, Li, P, Pre, Section, Ul } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { DocPage } from "../../doc-page.js";

/**
 * CodeBlock
 *
 * Creates a code block with copy-to-clipboard functionality.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const CodeBlock = Atom((props, children) => (
	Pre(
		{
			...props,
			class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${props.class}`
		},
		[
			Code(
				{
					class: 'font-mono flex-auto text-sm text-wrap',
					click: () => {
						navigator.clipboard.writeText(children[0].textContent);
						// @ts-ignore
						app.notify({
							title: "Code copied",
							description: "The code has been copied to your clipboard.",
							icon: null
						});
					}
				},
				children
			)
		]
	)
));

/**
 * ApiPage
 *
 * This page documents Proto's API system. The API system uses a REST router
 * that lets you create routes, redirects, and resources. You can declare middleware
 * on the router or per route. API routes are defined in an api.php file
 * inside the api folder of a module. Nested folders allow deep API paths.
 *
 * @returns {DocPage}
 */
export const ApiPage = () =>
	DocPage(
		{
			title: 'API System',
			description: 'Learn how to build APIs in Proto using a REST router, middleware, resources, and redirects.'
		},
		[
			// Overview Section
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto's API system is built on a REST router that enables you to define routes, set up redirects,
					and declare resource controllers with ease. Middleware can be applied globally on the router or
					individually on routes. API routes are defined in an api.php file located in the module's API folder.`
				)
			]),

			// API File Structure Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'API File Structure'),
				P(
					{ class: 'text-muted-foreground' },
					`APIs are declared in files named api.php inside the api folder of a module.
					You can nest folders in the API folder to create deep API routes. For example:`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Modules/User/Api/api.php"),
					Li("Modules/User/Api/Account/api.php")
				])
			]),

			// Resource Routes Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Resource Routes'),
				P({ class: 'text-muted-foreground' },
					`A resource route automatically handles CRUD operations for a controller.
					For example, an API file for the User module might look like this:`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Modules\\User\\Api;

use Modules\\User\\Controllers\\UserController;
use Proto\\Http\\Middleware\\CrossSiteProtectionMiddleware;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()
    ->middleware([
        CrossSiteProtectionMiddleware::class
    ])
    ->resource('user', UserController::class);`
				),
				P({ class: 'text-muted-foreground' },
					`For deeper routes, nest folders in the API folder. For example, for account-related routes:`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Modules\\User\\Api\\Account;

use Modules\\User\\Controllers\\UserController;

/**
 * User API Routes for Accounts
 *
 * This file handles API routes for user accounts.
 */
router()
    ->resource('user/:userId/account', UserController::class);`
				)
			]),

			// Main API Router Setup Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Main API Router Setup'),
				P({ class: 'text-muted-foreground' },
					`The application bootstraps using a main route which then redirects to module-specific API routes. There are some global functions available for session management and routing.`
				),
				CodeBlock(
`<?php declare(strict_types=1);

/**
 * Thsi will get the router.
 *
 * @return Router
 */
function router(): Router
{
}

/**
 * This will get the session.
 *
 * @return SessionInterface
 */
function session(): Session\\SessionInterface
{
}
`
				)
			]),

			// Individual Routes Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Individual Routes'),
				P({ class: 'text-muted-foreground' },
					`Apart from resource routes, you can also define individual API routes.
					For example:`
				),
				CodeBlock(
`<?php declare(strict_types=1);

$router = router();

/**
 * This will get a patient by ID.
 *
 * @param string $req - the request class
 * @param object $params
 * @return object
 */
$router->get('patients/:id/', function($req, $params)
{
	$id = $req::input('module');

	return $params;
});

/**
 * This will get a patient by ID.
 *
 * @param string $req - the request class
 * @param object $params
 * @return object
 */
$router->get('patients/:id/', [PatientController::class, 'get']);

/**
 * This will redirect with a 301 code.
 *
 * @param string $req - the request class
 * @param object $params
 * @return object
 */
$router->redirect('patients/:id/', './appointments/', 302);

/**
 * This will get a resource with a 301 code.
 *
 * @param string $req - the request class
 * @param object $params
 * @return object
 */
$router->get('patients/:id?/', function($req, $params)
{
	// this will set a response code
	$params->code = 301;

	// this will json encode the value
	return $params;
});

/**
 * This will post a resource.
 *
 * @param string $req - the request class
 * @param object $params
 * @return object
 */
$router->post('patients/:id?/', function($req, $params)
{
	// this will json encode the value
	return $params;
});

/**
 * This will get an upload file.
 *
 * @param string $req - the request class
 * @param object $params
 * @return object
 */
$router->get('appoinmtents/*', function($req, $params)
{
	$file = $req::file('fileName');

	// this will json encode the value
	return $params;
});

/**
 * This will always route.
 *
 * @param string $req - the request class
 * @param object $params
 * @return object
 */
$router->get('*', function($req, $params)
{
	var_dump($params);
});
`
				)
			]),

			// group routes Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Group Routes'),
				P({ class: 'text-muted-foreground' },
					`You can group routes together to apply common middleware or settings. For example:`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Modules\\User\\Api\\Auth;

use Modules\\User\\Controllers\\AuthController;
use Proto\\Http\\Middleware\\CrossSiteProtectionMiddleware;
use Proto\\Http\\Router\\Router;

/**
 * Auth API Routes
 *
 * This file defines the API routes for user authentication, including
 * login, logout, registration, MFA, and CSRF token retrieval.
 */
router()
	->middleware(([
		CrossSiteProtectionMiddleware::class,
	]))
	->group('user/auth', function(Router $router)
	{
		$controller = new AuthController();
		// standard login / logout / register
		$router->post('login', [$controller, 'login']);
		$router->post('logout', [$controller, 'logout']);
		$router->post('register', [$controller, 'register']);

		// MFA: send & verify one-time codes
		$router->post('mfa/code', [$controller, 'getAuthCode']);
		$router->post('mfa/verify', [$controller, 'verifyAuthCode']);

		// CSRF token (no body, safe to GET)
		$router->get('csrf-token', [$controller, 'getToken']);
	});
`)
		]),

		Section({ class: 'space-y-4 mt-12' }, [
			H4({ class: 'text-lg font-bold' }, 'Caching'),
			P({ class: 'text-muted-foreground' },
				`Proto supports server-side caching for API responses. It will automatically cache and invalidate cache for registered "resource" routes using Redis. The cache is handled by a Cache Proxy that will run after authentication. If the request is a "GET" request, it will check if the cache exists. If it does, it will return the cached response. If not, it will call the controller and cache the response. Non get methods will clear the cache.`
			)
		])
	]);

export default ApiPage;