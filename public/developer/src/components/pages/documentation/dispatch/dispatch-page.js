import { Code, H4, Li, P, Pre, Section, Ul } from "@base-framework/atoms";
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
    'template' => 'Common\\Email\\ExampleEmail'
];

// Dispatch email immediately:
Dispatcher::email($settings);

// Enqueue email to send later:
Enqueuer::email($settings);`
				),
				P({ class: 'text-muted-foreground' },
					`An API endpoint is available for testing email dispatch: /api/email?op=test&to={email}.`
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
    'session' => 'twilio session id',
    'template' => 'Common\\Text\\ExampleSms'
];

// Dispatch SMS immediately:
Dispatcher::sms($settings);

// Enqueue SMS to send later:
Enqueuer::sms($settings);`
				),
				P({ class: 'text-muted-foreground' },
					`Test SMS sending via the API endpoint: /api/text?op=test&to={number}.`
				)
			]),

			// Web-Push Dispatch
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Web-Push'),
				P({ class: 'text-muted-foreground' },
					`For web push notifications, add push configurations to your Common\\Config .env file under "push".
					The dispatch system uses these settings when sending notifications, and the push templates should be placed in the Common\\Push or module Push folder.
					To simplify web push notifications, use the Common\\Controllers\\Push\\WebPushController which automatically retrieves
					user push settings.`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Dispatch web push immediately:"),
					Li("Enqueue web push for later sending:")
				]),
				CodeBlock(
`$userId = 1;
$data = (object)[ /* your data here */ ];

// Dispatch web push immediately:
WebPushController::dispatch($userId, 'Common\\Push\\PushTest', $data);

// Enqueue web push to send later:
WebPushController::enqueue($userId, 'Common\\Push\\PushTest', $data);`
				)
			])
		]
	);

export default DispatchPage;