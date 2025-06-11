import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
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
 * EventsPage
 *
 * This page documents Proto’s event system, detailing how to register and publish both
 * storage events (triggered automatically by the storage layer) and custom events.
 *
 * @returns {DocPage}
 */
export const EventsPage = () =>
	DocPage(
		{
			title: 'Events System',
			description: 'Learn how Proto supports server event listeners for storage actions and custom events.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`Proto supports server event listeners that can be set up and published to react to changes in your application.
					The events class is available as \`Proto\\Events\\Events\` and allows you to register callbacks for various events.`
				)
			]),

			// Storage Events
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Storage Events'),
				P({ class: 'text-muted-foreground' },
					`The storage layer automatically publishes events for all actions performed via the
					\`Proto\\Storages\\StorageProxy\` that models use to interface with the storage layer.
					This enables you to listen for storage events as they occur.`
				),
				P({ class: 'text-muted-foreground' },
					`To register an event, call the \`on\` method with the event name and a callback. The storage event name is formed by the model name and method name separated by a colon.`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::on('Ticket:add', function($payload) {
    /**
     * $payload includes:
     * - args: the arguments passed to the storage method.
     * - data: the data passed or retrieved from the database.
     */
});`
				),
				P({ class: 'text-muted-foreground' },
					`To manually publish an event, call the \`update\` method:`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::update('Ticket:add', (object)[
    'args'  => 'the args',
    'model' => 'the model data'
]);`
				),
				P({ class: 'text-muted-foreground' },
					`If you wish to listen to general storage events without specifying a model or method,
					Proto automatically publishes a "Storage" event on every update:`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::on('Storage', function($payload) {
    /**
     * $payload is an object containing:
     * - target: the model name,
     * - method: the method name,
     * - data: the model data.
     */
});`
				)
			]),

			// Custom Events
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Custom Events'),
				P({ class: 'text-muted-foreground' },
					`In addition to storage events, Proto supports custom events.
					You can register and publish custom events to allow your application to react to specific changes.`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::on('CustomEvent', function($payload) {
    // Handle custom event logic here.
});

Events::update('CustomEvent', (object)[
    'custom' => 'custom data'
]);`
				)
			])
		]
	);

export default EventsPage;