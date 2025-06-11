import{a2 as o,a0 as t,G as e,s as n,u as l,N as i,o as c}from"./index-DM3KSgk2.js";import{D as d}from"./doc-page-DoAFO_jW.js";import"./sidebar-menu-page-D_2zNFuZ-DKHVgHxo.js";const a=n((s,r)=>l({...s,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${s.class}`},[i({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(r[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:c.clipboard.checked})}},r)])),f=()=>d({title:"Getting Started with Proto",description:"Learn how to install, configure, and build applications using the Proto framework."},[o({class:"space-y-4"},[t({class:"text-lg font-bold"},"About Proto"),e({class:"text-muted-foreground"},`Proto is a modular monolith framework inspired by Dashr. It allows scalable server applications to be created quickly and securely.
					 The framework auto-bootstraps whenever you interact with a module, router, or controller, so minimal setup is required.`),e({class:"text-muted-foreground"},`In Proto, core framework code lives in the "proto" folder (read-only), shared code goes in "common", and
					 each major domain or feature resides in its own module under the "modules" folder. This structure
					 supports team collaboration and easier testing, while still allowing a module to be spun out into a
					 separate service if it grows too large.`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Prerequisites & Installation"),e({class:"text-muted-foreground"},`Proto requires PHP 8.2 or higher. It also uses Composer for dependency management.
					 Make sure you have both installed on your machine.`),e({class:"text-muted-foreground"},"To install Proto and its dependencies, run the following command in your project's root folder:"),a(`composer install
# or
composer update`),e({class:"text-muted-foreground"},"This will download all packages defined in your composer.json file. Once installed, you can begin customizing your application.")]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Project Structure"),e({class:"text-muted-foreground"},"A typical Proto application has the following structure:"),a(`common/          // Shared code (replaces the old "App" directory from Dashr)
modules/         // Each major feature or domain is a self-contained module
proto/           // Core framework code (do not modify)
public/          // Public-facing files (including the developer app in /public/developer)
`),e({class:"text-muted-foreground"},`Proto automatically loads modules and other resources on demand, ensuring performance and
					 maintainability as your application grows.`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Configuration"),e({class:"text-muted-foreground"},`Before development, configure your application settings in "common/Config" (e.g., .env).
					 Proto\\Config (a singleton) loads these settings at bootstrap. You can retrieve config values using:`),a(`use Proto\\Config;

// Access the config instance
$config = Config::getInstance();
$baseUrl = $config->get('baseUrl');

// Or use static access
$connections = Config::access('connections');

// The env() helper is also available
$connections = env('connections');
`),e({class:"text-muted-foreground"},"All environment variables should be registered as JSON within your .env file.")]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Bootstrapping"),e({class:"text-muted-foreground"},`Proto automatically bootstraps when you call a module, router, or controller.
					 Simply include "/proto/autoload.php" and invoke the namespaced classes you need.`),a(`<?php declare(strict_types=1);

// Example usage
require_once __DIR__ . '/proto/autoload.php';

// Once included, you can call modules, controllers, etc.
modules()->user()->v1()->createUser($data);`),e({class:"text-muted-foreground"},`There is no need for extensive manual setup; Proto handles loading, event registration,
					 and other behind-the-scenes tasks automatically.`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Modules & Gateways"),e({class:"text-muted-foreground"},`Each feature or domain is encapsulated in its own module within "modules/".
					 Modules can have APIs, controllers, models, and gateways.
					 If an API request path doesn't match a module, that module API is never loaded, improving performance.`),e({class:"text-muted-foreground"},`Gateways provide a public interface for modules. Other modules can call them like so:
					 modules()->example()->add();
					 or with versioning: modules()->example()->v1()->add();`),a(`<?php declare(strict_types=1);
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
}`),e({class:"text-muted-foreground"},`You can also define API routes in each module. For example,
					 placing an api.php or subfolders within your module's "Api" directory
					 registers routes only if a request path matches the module's route prefix.`),a(`router()
	->middleware([
        CrossSiteProtectionMiddleware::class
    ])
    ->resource('user', UserController::class);`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Developer App in public/developer"),e({class:"text-muted-foreground"},`Proto includes a developer application located in "public/developer" that provides
					 error tracking, migration management, and a generator system. The generator can
					 create modules, gateways, APIs, controllers, and models to speed up development.`),e({class:"text-muted-foreground"},`Use this app to quickly scaffold new features or manage existing ones without needing
					 a fully distributed microservices setup.`)])]);export{f as GetStartedPage,f as default};
