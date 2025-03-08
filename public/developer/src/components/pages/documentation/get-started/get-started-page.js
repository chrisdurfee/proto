import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { DocPage } from "../../doc-page.js";

/**
 * CodeBlock
 *
 * This component creates a code block with copy-to-clipboard support.
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
 * This component creates a "Get Started" page for the Proto framework documentation.
 *
 * @returns {DocPage}
 */
export const GetStartedPage = () =>
	DocPage(
		{
			title: 'Get Started with Proto',
			description: 'Learn how to install, configure, and begin working with the Proto framework.'
		},
		[
			// 1) About the Proto Framework
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'About the Proto Framework'),
				P(
					{ class: 'text-muted-foreground' },
					`The Proto framework is designed to allow scalable server applications to be created quickly and securely.
					Leveraging a modular monolith architecture, Proto builds upon the lessons learned from Dashr while promoting a more organized, module-centric codebase.`
				),
				P(
					{ class: 'text-muted-foreground' },
					`The core framework is contained in the <code>proto</code> folder, which is accessible for inclusion but should not be modified by users.
					Shared functionality is placed in the <code>common</code> folder, and all major domains or features reside in individual modules within the <code>modules</code> folder.`
				)
			]),

			// 2) Framework Features
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Framework Features'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto includes a comprehensive set of features for building complex server applications:
					API systems (both resource and REST routers), HTTP resources, security gates and policies,
					authentication with roles and permissions, controllers, caching (e.g., Redis), configs, models,
					storage layers, session management, services, design patterns, HTML templates, email rendering,
					dispatching of email, SMS, and web push notifications, events, resource generators, database adapters,
					query builders, migrations, file storage, integrations, and various utility functions.`
				)
			]),

			// 3) File Structure
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'File Structure'),
				P(
					{ class: 'text-muted-foreground' },
					`A typical Proto application is structured as follows:`
				),
				CodeBlock(
`common/         // The root for your application code and shared components between modules.
proto/          // The core framework. This folder is accessible but should not be modified.
modules/        // Contains self-contained modules for each major domain or feature.
public/         // Front-end assets and public resources.`
				)
			]),

			// 4) Naming Conventions & Namespace Structure
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming Conventions & Namespace Structure'),
				P(
					{ class: 'text-muted-foreground' },
					`All class names should use PascalCase, and all methods and variables should use camelCase.
					File names should use hyphens to concatenate words, and namespaces should reflect the folder structure
					to support autoloading.`
				)
			]),

			// 5) Configuration
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Configuration'),
				P(
					{ class: 'text-muted-foreground' },
					`Before beginning development, configure your environment settings in the <code>common/config</code> directory.
					All application settings should be registered in the .env file as JSON.`
				),
				CodeBlock(
`/**
 * Proto Config
 *
 * The Proto\\Config class loads your application settings during bootstrap.
 * It is implemented as a singleton, so call getInstance() to access the configuration.
 */
$config = Proto\\Config::getInstance();

// Get a configuration value
$value = $config->get('key');

// Set a configuration value
$config->set('key', $value);

// Or access values statically
$value = Proto\\Config::access('key');

// Alternatively, use the global env function
$value = env('key');
`
				)
			]),

			// 6) Bootstrapping & Global Data
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Bootstrapping & Global Data'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto auto bootstraps when interfacing with APIs, controllers, models, storage layers, or routines.
					Simply include the <code>/proto/autoload.php</code> file to initialize the framework.
					Global data is managed using the <code>common/data</code> singleton, providing getter and setter methods for shared data.`
				),
				CodeBlock(
`/**
 * Bootstrapping the Proto application.
 * Include the autoload file to initialize the framework.
 */
require_once __DIR__ . '/proto/autoload.php';

/**
 * Global Data Access
 *
 * Use Common\\Data to retrieve or update global application data.
 */
$data = Common\\Data::getInstance();
$currentValue = $data->get('key');
$data->set('key', 'value');
`
				)
			]),

			// 7) Modules System
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Modules System'),
				P(
					{ class: 'text-muted-foreground' },
					`Each major domain or feature is encapsulated in its own module within the <code>modules</code> folder.
					Modules are self-contained and can declare their own routes, links, and configurations.
					They can also interact with each other using the module gateway interface.
					For example, a module can expose public methods on its gateway, allowing other modules to call them as follows:`
				),
				CodeBlock(
`/**
 * Example Module Gateway for a Feature Module.
 */
namespace Modules\\Example\\Gateway;

class Gateway
{
	/**
	 * Direct method available to other modules.
	 * Usage: modules()->example()->add();
	 */
	public function add(): void
	{
		// Implementation for adding an example.
	}

	/**
	 * Versioned gateways allow for version-specific calls.
	 */
	public function v1(): V1\\Gateway
	{
		return new \\Modules\\Example\\Gateway\\V1\\Gateway();
	}

	public function v2(): V2\\Gateway
	{
		return new \\Modules\\Example\\Gateway\\V2\\Gateway();
	}
}
`
				)
			])
		]
	);

export default GetStartedPage;
