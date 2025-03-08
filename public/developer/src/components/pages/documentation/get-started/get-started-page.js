import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { DocPage } from "../../doc-page.js";

/**
 * This will create a code block with copy-to-clipboard functionality.
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
							icon: Icons.clipboard.checked
						});
					}
				},
				children
			)
		]
	)
));

/**
 * GetStartedPage
 *
 * This page details how to install, configure, and begin developing with the Proto framework.
 * It covers prerequisites, folder structure, auto-bootstrapping, modules, gateways, and
 * the developer app in public/developer.
 *
 * @returns {DocPage}
 */
export const GetStartedPage = () =>
	DocPage(
		{
			title: 'Getting Started with Proto',
			description: 'Learn how to install, configure, and build applications using the Proto framework.'
		},
		[
			// 1) About Proto
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'About Proto'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto is a modular monolith framework inspired by Dashr. It allows scalable server applications to be created quickly and securely.
					 The framework auto-bootstraps whenever you interact with a module, router, or controller, so minimal setup is required.`
				),
				P(
					{ class: 'text-muted-foreground' },
					`In Proto, core framework code lives in the "proto" folder (read-only), shared code goes in "common", and
					 each major domain or feature resides in its own module under the "modules" folder. This structure
					 supports team collaboration and easier testing, while still allowing a module to be spun out into a
					 separate service if it grows too large.`
				)
			]),

			// 2) Prerequisites & Installation
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Prerequisites & Installation'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto requires PHP 8.2 or higher. It also uses Composer for dependency management.
					 Make sure you have both installed on your machine.`
				),
				P(
					{ class: 'text-muted-foreground' },
					`To install Proto and its dependencies, run the following command in your project's root folder:`
				),
				CodeBlock(
`composer install
# or
composer update`
				),
				P(
					{ class: 'text-muted-foreground' },
					`This will download all packages defined in your composer.json file. Once installed, you can begin customizing your application.`
				)
			]),

			// 3) Project Structure
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Project Structure'),
				P(
					{ class: 'text-muted-foreground' },
					`A typical Proto application has the following structure:`
				),
				CodeBlock(
`common/          // Shared code (replaces the old "App" directory from Dashr)
modules/         // Each major feature or domain is a self-contained module
proto/           // Core framework code (do not modify)
public/          // Public-facing files (including the developer app in /public/developer)
`
				),
				P(
					{ class: 'text-muted-foreground' },
					`Proto automatically loads modules and other resources on demand, ensuring performance and
					 maintainability as your application grows.`
				)
			]),

			// 4) Configuration
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Configuration'),
				P(
					{ class: 'text-muted-foreground' },
					`Before development, configure your application settings in "common/Config" (e.g., .env).
					 Proto\\Config (a singleton) loads these settings at bootstrap. You can retrieve config values using:`
				),
				CodeBlock(
`use Proto\\Config;

// Access the config instance
$config = Config::getInstance();
$baseUrl = $config->get('baseUrl');

// Or use static access
$connections = Config::access('connections');

// The env() helper is also available
$connections = env('connections');
`
				),
				P(
					{ class: 'text-muted-foreground' },
					`All environment variables should be registered as JSON within your .env file.`
				)
			]),

			// 5) Bootstrapping
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Bootstrapping'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto automatically bootstraps when you call a module, router, or controller.
					 Simply include "/proto/autoload.php" and invoke the namespaced classes you need.`
				),
				CodeBlock(
`<?php declare(strict_types=1);

// Example usage
require_once __DIR__ . '/proto/autoload.php';

// Once included, you can call modules, controllers, etc.
modules()->user()->v1()->createUser($data);`
				),
				P(
					{ class: 'text-muted-foreground' },
					`There is no need for extensive manual setup; Proto handles loading, event registration,
					 and other behind-the-scenes tasks automatically.`
				)
			]),

			// 6) Modules & Gateway
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Modules & Gateways'),
				P(
					{ class: 'text-muted-foreground' },
					`Each feature or domain is encapsulated in its own module within "modules/".
					 Modules can have APIs, controllers, models, and gateways.
					 If an API request path doesn't match a module, that module API is never loaded, improving performance.`
				),
				P(
					{ class: 'text-muted-foreground' },
					`Gateways provide a public interface for modules. Other modules can call them like so:
					 modules()->example()->add();
					 or with versioning: modules()->example()->v1()->add();`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Modules\\Example\\Gateway;

class Gateway
{
    public function add(): void
    {
        // Implementation
    }

    public function v1(): V1\\Gateway
    {
        return new V1\\Gateway();
    }

    public function v2(): V2\\Gateway
    {
        return new V2\\Gateway();
    }
}`
				),
				P(
					{ class: 'text-muted-foreground' },
					`You can also define API routes in each module. For example,
					 placing an api.php or subfolders within your module's "Api" directory
					 registers routes only if a request path matches the module's route prefix.`
				),
				CodeBlock(
`router()
    ->resource('user', UserController::class)
    ->middleware([
        CrossSiteProtectionMiddleware::class
    ]);`
				)
			]),

			// 7) Developer App
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Developer App in public/developer'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto includes a developer application located in "public/developer" that provides
					 error tracking, migration management, and a generator system. The generator can
					 create modules, gateways, APIs, controllers, and models to speed up development.`
				),
				P(
					{ class: 'text-muted-foreground' },
					`Use this app to quickly scaffold new features or manage existing ones without needing
					 a fully distributed microservices setup.`
				)
			])
		]
	);

export default GetStartedPage;