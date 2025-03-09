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
 * This page documents Proto’s API system. The API system uses a REST router
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
					`Proto’s API system is built on a REST router that enables you to define routes, set up redirects,
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
					Li("Modules/User/User/Api/api.php"),
					Li("Modules/User/User/Api/Account/api.php")
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
namespace Modules\\User\\User\\Api;

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
namespace Modules\\User\\User\\Api\\Account;

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
					`The application bootstraps using a main route which then redirects to module-specific API routes.
					For example, the main entry point may include:`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace {
    use Proto\\Base;
    use Proto\\Http\\Router\\Router;
    use Proto\\Http\\Session;

    // Bootstrap the application
    $base = new Base();
    Session::init();

    function router(): Router {
        $basePath = env('router')->basePath ?? '/';
        return new Router($basePath);
    }

    // Global session functions
    function session(): Session\\SessionInterface {
        return Session::getInstance();
    }

    // Example usage in the main API entry point:
    $router = router();
    $router->all(':resource.*', function($req, $params) {
        // Dynamically include the module API if the resource path is valid
        $resourcePath = ResourceHelper::getResource($params->resource);
        if (!$resourcePath) {
            return Proto\\Api\\ApiRouter::error('Resource not found.', 404);
        }
        ResourceHelper::includeResource($resourcePath);
    }, [Proto\\Http\\Middleware\\ApiRateLimiterMiddleware::class]);

    // Initialize the API router
    Proto\\Api\\ApiRouter::initialize();
}`
				)
			]),

			// Individual Routes Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Individual Routes'),
				P({ class: 'text-muted-foreground' },
					`Apart from resource routes, you can also define individual API routes.
					For example, in a developer API file:`
				),
				CodeBlock(
`<?php declare(strict_types=1);
include_once __DIR__ . '/../common/autoload.php';

use Proto\\Http\\Router\\Router;
use Developer\\App\\Controllers\\GeneratorController;

$router = new Router('/developer/api/');

// Create a new resource via the generator.
$router->post('generator', function($req, $params) {
    $resource = $req::json('resource');
    $type = $req::input('type');
    $controller = new GeneratorController();
    return $controller->addByType($type, $resource);
});

// Additional individual routes can be defined similarly.
`
				)
			])
		]
	);

export default ApiPage;