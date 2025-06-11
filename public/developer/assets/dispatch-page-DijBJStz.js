import{a2 as s,a0 as a,G as e,Z as i,s as d,a3 as t,J as l,u as r,N as m,o as p}from"./index-DM3KSgk2.js";import{D as c}from"./doc-page-DoAFO_jW.js";import"./sidebar-menu-page-D_2zNFuZ-DKHVgHxo.js";const o=d((n,u)=>r({...n,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${n.class}`},[m({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(u[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:p.clipboard.checked})}},u)])),b=()=>c({title:"Dispatch",description:"Learn how to dispatch and enqueue messages for email, SMS, and web push notifications in Proto."},[s({class:"space-y-4"},[a({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},`Proto supports dispatching several default types of messages:
					email, SMS, and web push notifications. The Dispatch and Enqueue classes are located in the Proto\\Dispatch\\Dispatcher folder.
					Dispatching immediately sends the message during the current request (which may slow down the response),
					while enqueuing adds the message to a queue that processes messages every minute.`)]),s({class:"space-y-4 mt-12"},[a({class:"text-lg font-bold"},"Email"),e({class:"text-muted-foreground"},`To dispatch an email, add the email configurations to your Common\\Config .env file under the key "email".
					Email messages can use HTML templates that reside in the Common\\Email or module Email folder.`),i({class:"list-disc pl-6 space-y-1 text-muted-foreground"},[t("Dispatch email immediately:"),t("Enqueue email for later sending:")]),o(`$settings = (object)[
    'to' => 'email@email.com',
    'subject' => 'Subject',
	'fromName' => 'Sender Name', // optional
	'unsubscribeUrl' => '', // optional
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
`),e({class:"text-muted-foreground"},"An API endpoint is available for testing email dispatch: /api/developer/email/test?to={email}.")]),s({class:"space-y-4 mt-12"},[a({class:"text-lg font-bold"},"SMS"),e({class:"text-muted-foreground"},`To dispatch a text message, add SMS configurations to your Common\\Config .env file under "sms".
					The dispatch system uses Twilio settings, and text templates should be placed in the Common\\Text or module Text folder.`),i({class:"list-disc pl-6 space-y-1 text-muted-foreground"},[t("Dispatch SMS immediately:"),t("Enqueue SMS for later sending:")]),o(`$settings = (object)[
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
Dispatcher::sms($settings, $data);`),e({class:"text-muted-foreground"},"Test SMS sending via the API endpoint: /api/developer/sms/test?to={number}.")]),s({class:"space-y-4 mt-12"},[a({class:"text-lg font-bold"},"Web-Push"),e({class:"text-muted-foreground"},`To dispatch a push notification, add push configurations to your Common\\Config .env file under "push".
					The dispatch system uses these settings when sending notifications, and the push templates should be placed in the Common\\Push or module Push folder.`),e({class:"text-muted-foreground"},"New VAPID keys can be created at this link:"),l({href:"https://web-push-codelab.glitch.me/",target:"_blank"},"VAPID Key Generator"),i({class:"list-disc pl-6 space-y-1 text-muted-foreground"},[t("Dispatch web push immediately:"),t("Enqueue web push for later sending:")]),o(`$settings = (object)[
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
Dispatcher::push($settings, $data);`),e({class:"text-muted-foreground"},"Test Push sending via the API endpoint: /api/developer/push/test?userId={userId}."),e({class:"text-muted-foreground"},"The User Module has a PushGateway to make sending push notifications easier to a user. This is a wrapper around the Dispatcher class. Here is an example on how to use it:"),o(`$settings = (object)[
    'template' => 'Common\\Push\\ExamplePush',
	'queue' => false, // optional
};

$userId = 1; // user id
$data = (object)[];

modules()->user()->push()->send(
	$userId,
	$settings,
	$data
);`)])]);export{b as DispatchPage,b as default};
