import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
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
 * ModulesPage
 *
 * This page explains how to structure and work with modules in Proto.
 * Modules are self-contained units for each major feature or domain and can
 * communicate with other modules through gateways. They can be generated via the developer code generator
 * and must be registered in the configuration.
 *
 * @returns {DocPage}
 */
export const ModulesPage = () =>
	DocPage(
		{
			title: 'Modules',
			description: 'Learn how to create, manage, and register modules in Proto.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`Each feature or domain of your application should be developed as a separate module.
					Modules are self-contained units that encapsulate APIs, controllers, models, and gateways.
					They can interact with other registered modules when necessary.`
				)
			]),

			// Module Folder Structure
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Module Folder Structure'),
				P({ class: 'text-muted-foreground' },
					`All modules reside in their own folders inside the modules directory.
					Modules can be generated using the developer code generator, making it easy to add new features.`
				)
			]),

			// Module Gateway
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Module Gateway'),
				P({ class: 'text-muted-foreground' },
					`Modules can include a gateway file within a gateway subfolder. The gateway provides
					a public interface for accessing module functionality from other modules. Gateways can also support versioning
					to allow updates while maintaining backward compatibility.`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Modules\\Example\\Gateway;

/**
 * Gateway
 *
 * This will handle the example module gateway.
 * Call it from another module like:
 * modules()->example()->add();
 * To use versioned methods:
 * modules()->example()->v1()->add();
 * modules()->example()->v2()->add();
 */
class Gateway
{
    public function add(): void
    {
        // Implementation for adding an example.
    }

    public function v1(): V1\\Gateway
    {
        return new \\Modules\\Example\\Gateway\\V1\\Gateway();
    }

    public function v2(): V2\\Gateway
    {
        return new \\Modules\\Example\\Gateway\\V2\\Gateway();
    }
}`
				)
			]),

			// Example Module
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Example Module'),
				P({ class: 'text-muted-foreground' },
					`Below is an example module that demonstrates how to encapsulate a feature within a module.
					The module extends the base Module class, sets up configurations, and registers events.`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Modules\\Example;

use Proto\\Module\\Module;

/**
 * ExampleModule
 *
 * This module is an example of how to create a module in the Proto framework.
 */
class ExampleModule extends Module
{
    public function activate(): void
    {
        $this->setConfigs();
    }

    private function setConfigs(): void
    {
        setEnv('settingName', 'value');
    }

    protected function addEvents(): void
    {
        // Add an event for when a ticket is added.
        $this->event('Ticket:add', fn($ticket): void => var_dump($ticket));
    }
}`
				)
			]),

			// Module Registration
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Module Registration'),
				P({ class: 'text-muted-foreground' },
					`For a module to be valid and loaded, it must be registered in your configuration file
					(e.g. in the common .env file) under the "modules" key. For example:`
				),
				CodeBlock(
`"modules": [
    "Example\\ExampleModule",
    "Product\\ProductModule",
    "User\\UserModule"
]`
				)
			])
		]
	);

export default ModulesPage;