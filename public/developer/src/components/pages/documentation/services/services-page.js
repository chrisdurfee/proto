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
 * ServicesPage
 *
 * This page explains how Proto's services work. Services are self-contained classes that are registered
 * in your configuration (.env file under "services") and loaded right after the application bootstraps.
 * They can listen for storage layer actions and perform activation tasks.
 *
 * @returns {DocPage}
 */
export const ServicesPage = () =>
	DocPage(
		{
			title: 'Services',
			description: 'Learn how to create, register, and activate services in Proto.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P(
					{ class: 'text-muted-foreground' },
					`Services in Proto are self-contained and provide additional functionality that is loaded immediately after the framework boots.
					They are registered in your configuration file (typically within common/Config) under the "services" key, for example:`
				),
				CodeBlock(
`"services": [
    "Example\\ExampleService",
    "Example\\Parent\\ProductionService"
]`
				),
				P(
					{ class: 'text-muted-foreground' },
					`Once registered, services can listen for events, especially from the storage layer, and set up any global functionality your application needs.`
				)
			]),

			// Naming Conventions
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming'),
				P(
					{ class: 'text-muted-foreground' },
					`The name of a service should always be singular and followed by "Service". For example:`
				),
				CodeBlock(
`<?php
namespace Common\\Services;

class ExampleService extends Service
{
    protected function addEvents()
    {
        // Register events here
    }

    public function activate()
    {
        // Perform actions on framework activation
    }
}`
				)
			]),

			// Activation
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Activation'),
				P(
					{ class: 'text-muted-foreground' },
					`Services are activated when the framework boots. This allows services to register any actions or listeners they need to be available
					immediately as the application starts. For example:`
				),
				CodeBlock(
`// In a service class
public function activate()
{
    // Perform setup tasks, such as initializing components or registering listeners.
}`
				)
			]),

			// Events
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Events'),
				P(
					{ class: 'text-muted-foreground' },
					`Services can also register events to respond to various actions, such as storage events.
					Within your service, use the inherited event method to set up event listeners. For example:`
				),
				CodeBlock(
`// In a service class
protected function addEvents()
{
    $this->event('Ticket:add', function($ticket) {
        // Handle the event when a ticket is added.
    });
}`
				)
			])
		]
	);

export default ServicesPage;