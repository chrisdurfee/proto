import { A, Code, H4, Li, P, Pre, Section, Ul } from "@base-framework/atoms";
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
 * DispatchPage
 *
 * This page documents Proto’s dispatch system for sending email, SMS, and web push notifications.
 * Dispatches can be sent immediately or enqueued for delayed sending.
 *
 * @returns {DocPage}
 */
export const DispatchPage = () =>
	DocPage(
		{
			title: 'Dispatch',
			description: 'Learn how to dispatch and enqueue messages for email, SMS, and web push notifications in Proto.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`Proto supports dispatching several default types of messages:
					email, SMS, and web push notifications. The Dispatch and Enqueue classes are located in the Proto\\Dispatch\\Dispatcher folder.
					Dispatching immediately sends the message during the current request (which may slow down the response),
					while enqueuing adds the message to a queue that processes messages every minute.`
				)
			]),

			// Email Dispatch
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Email'),
				P({ class: 'text-muted-foreground' },
					`To dispatch an email, add the email configurations to your Common\\Config .env file under the key "email".
					Email messages can use HTML templates that reside in the Common\\Email or module Email folder.`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Dispatch email immediately:"),
					Li("Enqueue email for later sending:")
				]),
				CodeBlock(
`$settings = (object)[
	'to' => 'email@email.com',
	'subject' => 'Subject',
	'fromName' => 'Sender Name', // optional
	'unsubscribeUrl' => '', // optional, it will set this by default in the EmailHelper class
	'template' => 'Common\\Email\\ExampleEmail',
	'attachments' => [
		'/path/to/file1.pdf',
	]
];

$data = (object)[];

// Dispatch email immediately:
Dispatcher::email($settings, $data);

// Enqueue email to send later:
Enqueuer::email($settings, $data);

// or set the queue option to true:
$settings->queue = true;
Dispatcher::email($settings, $data);
`
				),
				P({ class: 'text-muted-foreground' },
					`An API endpoint is available for testing email dispatch: /api/developer/email/test?to={email}.`
				)
			]),

			// SMS Dispatch
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'SMS'),
				P({ class: 'text-muted-foreground' },
					`To dispatch a text message, add SMS configurations to your Common\\Config .env file under "sms".
					The dispatch system uses Twilio settings, and text templates should be placed in the Common\\Text or module Text folder.`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Dispatch SMS immediately:"),
					Li("Enqueue SMS for later sending:")
				]),
				CodeBlock(
`$settings = (object)[
	'to' => '1112221111',
	'session' => 'session id', // if different than the default
	'template' => 'Common\\Text\\ExampleSms'
];

$data = (object)[];

// Dispatch SMS immediately:
Dispatcher::sms($settings, $data);

// Enqueue SMS to send later:
Enqueuer::sms($settings, $data);

// or set the queue option to true:
$settings->queue = true;
Dispatcher::sms($settings, $data);`
				),
				P({ class: 'text-muted-foreground' },
					`Test SMS sending via the API endpoint: /api/developer/sms/test?to={number}.`
				)
			]),

			// web push dispatch
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Web-Push'),
				P({ class: 'text-muted-foreground' },
					`To dispatch a push notification, add push configurations to your Common\\Config .env file under "push".
					The dispatch system uses these settings when sending notifications, and the push templates should be placed in the Common\\Push or module Push folder.`
				),
				P({ class: 'text-muted-foreground' },
					`New VAPID keys can be created at this link:`
				),
				A(
					{
						href: 'https://web-push-codelab.glitch.me/',
						target: '_blank'
					},
					'VAPID Key Generator'
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Dispatch web push immediately:"),
					Li("Enqueue web push for later sending:")
				]),
				CodeBlock(
`$settings = (object)[
	'subscriptions' => [
		[
			'id' => 'subscription id',
			'endpoint' => 'https://example.com/push/endpoint',
			'keys' => [
				'auth' => 'auth key',
				'p256dh' => 'p256dh key'
			]
		]
	],
	'template' => 'Common\\Push\\ExamplePush',
	'queue' => false, // optional
};

$data = (object)[];

// Dispatch Push immediately:
Dispatcher::push($settings, $data);

// Enqueue Push to send later:
Enqueuer::push($settings, $data);

// or set the queue option to true:
$settings->queue = true;
Dispatcher::push($settings, $data);`
				),
				P({ class: 'text-muted-foreground' },
					`Test Push sending via the API endpoint: /api/developer/push/test?userId={userId}.`
				),
				P({ class: 'text-muted-foreground' },
					`The User Module has a PushGateway to make sending push notifications easier to a user. This is a wrapper around the Dispatcher class. Here is an example on how to use it:`
				),
				CodeBlock(
`$settings = (object)[
	'template' => 'Common\\Push\\ExamplePush',
	'queue' => false, // optional
};

$userId = 1; // user id
$data = (object)[];

modules()->user()->push()->send(
	$userId,
	$settings,
	$data
);`
				)
			])
		]
	);

export default DispatchPage;