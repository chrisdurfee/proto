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
			class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto rounded-lg border bg-muted whitespace-break-spaces break-all cursor-pointer mt-4 ${props.class}`
		},
		[
			Code(
				{
					class: 'font-mono flex-auto text-sm',
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
 * IntroPage
 *
 * This page introduces the Proto framework by outlining its purpose,
 * features, file structure, naming conventions, configuration, bootstrapping,
 * and global data management. It also includes UI examples.
 *
 * @returns {DocPage}
 */
export const IntroPage = () => (
	DocPage(
		{
			title: 'Introduction to Proto',
			description: 'Proto is an open-source modular monolith framework for building scalable server applications quickly and securely.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`Distributed systems are great except when they are not. Building large team-based systems that can scale has many challenges. Testing, conflicts, build times, response times, developer environments, etc. The Proto framework is created to allow scalable server applications to be created quickly and securely. It's modular to allow teams to build their specific features without many of the issues when building distributed systems. It autoloads and auto bootstraps. Configuration is managed in the Common/Config .env file.`),
			]),

			// Framework Features
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Framework Features'),
				P({ class: 'text-muted-foreground' },
					`Proto includes items for creating complex applications, including:`),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li('Modules system to encpasulate features'),
					Li("API Systems (Both Resource and REST Routers)"),
					Li('Validation'),
					Li('Server Sent Events (SSE)'),
					Li('Websockets'),
					Li('Sockets'),
					Li("HTTP Resources"),
					Li("Security Gates and Policies"),
					Li("Authentication using roles and permissions"),
					Li("Controllers"),
					Li("Caching (Redis)"),
					Li("Configs"),
					Li("Models"),
					Li("Storage Layers"),
					Li("Session"),
					Li("Services"),
					Li("Service Providers"),
					Li('Jobs'),
					Li("Routines"),
					Li("Patterns"),
					Li("HTML Templates"),
					Li("Email Rendering"),
					Li("Dispatching Email, SMS, and Web Push"),
					Li("Events"),
					Li("Resource Generators"),
					Li("Database Adapter"),
					Li("Query Builders"),
					Li("Migrations"),
					Li("File Storage (Local, S3)"),
					Li("Integrations"),
					Li("Utils")
				])
			]),

			// File Structure
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

			// Naming Conventions & Namespace Structure
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming Conventions & Namespace Structure'),
				P({ class: 'text-muted-foreground' },
					`All class names should be in PascalCase, and all methods and variables should be in camelCase.
					Class names should be singular, while namespace paths can be plural.`),
				P({ class: 'text-muted-foreground' },
					`Folder names should be lowercase and use hyphens to concatenate words. Files should use PascalCase.
					Namespaces should reflect the folder structure to support autoloading.`)
			]),

			// Configuration
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Configuration'),
				P({ class: 'text-muted-foreground' },
					`Before beginning, configure your application settings in the Common/Config .env file.
					All settings should be registered as JSON.`),
				P({ class: 'text-muted-foreground' },
					`The Proto\\Config class loads your settings during bootstrap.
					It is a singleton; use Proto\\Config::getInstance() to access configurations.`),
				CodeBlock(
					`// The "Config" class can be accessed using a global function:
env('settingName');`
										)
			]),

			// Bootstrapping
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Bootstrapping'),
				P({ class: 'text-muted-foreground' },
					`Proto auto bootstraps when interfacing with an API, Controller, Model, Storage, or Routine.
					Simply include /proto/autoload.php and call the namespaced classes you need.`)
			]),

			// Global Data
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Global Data'),
				P({ class: 'text-muted-foreground' },
					`Proto implements a global data pattern. Use Common\\Data to get and set global data.
					This Data class is a singleton that uses getters and setters.`)
			])
		]
	)
);

export default IntroPage;