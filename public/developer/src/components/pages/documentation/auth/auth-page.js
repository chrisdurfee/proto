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
 * AuthPage
 *
 * This page documents Proto's gate and policy system for identity and access management.
 * It covers how to create gates, interact with the session, and define policies to secure API endpoints.
 *
 * @returns {DocPage}
 */
export const AuthPage = () =>
	DocPage(
		{
			title: 'Authentication & Authorization',
			description: 'Learn how Proto uses gates and policies to manage identity and access control.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto provides extensible gates and policies to control identity and access management.
					 Gates determine access to specific resources, while policies can secure API endpoints
					 by validating requests before or after controllers access data.`
				)
			]),

			// Gates
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Gates Overview'),
				P(
					{ class: 'text-muted-foreground' },
					`Gates are classes responsible for authenticating a particular type of resource.
					 They should be placed in Common\\Auth, and typically extend the base Gate class.`
				),
				P(
					{ class: 'text-muted-foreground' },
					`Within a gate, you can use Proto\\Http\\Session and Common\\Data to access session
					 data or global application data. Gates are named in the singular form, followed by "Gate" (e.g., ExampleGate).`
				),
				CodeBlock(
`<?php
namespace Common\\Auth;

use Proto\\Auth\\Gate;

class ExampleGate extends Gate
{
    public function has(string $permission): bool
    {
        // Check if the current user has the given permission.
        return true;
    }
}`
				),
				P(
					{ class: 'text-muted-foreground' },
					`You can access the session via static::$session or static::get() methods inside the gate:`
				),
				CodeBlock(
`// in a gate method
$value = static::$session->key;

// or
$value = static::get('key');`
				),
				P(
					{ class: 'text-muted-foreground' },
					`All gates can be registered within Common\\Auth so they can be accessed globally. The framework has a global "auth" function that will return the singleton instance for the "Auth" class.
					 For instance, if you have a user gate, you might call:`
				),
				CodeBlock(
`$userGate = auth()->user;
$userGate->isUser(1);`
				),

				P(
					{ class: 'text-muted-foreground' },
					`You can register a gate to be globally accessible by setting it on the Auth class.`
				),
				CodeBlock(
`
// access the global Auth instance
$auth = auth();

// set the the user gate to the Auth instance
$auth->user = new UserGate();

// now you can access the user gate globally in any module
$auth->user->isUser(1);`
				)
			]),

			// Policies
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Policy Overview'),
				P(
					{ class: 'text-muted-foreground' },
					`Policies are another layer of access control. They validate requests before or after
					 a controller method runs, ensuring users have the right permissions or roles.
					 Policies should also be placed in Common\\Auth\\Policies, named in the singular form,
					 followed by "Policy" (e.g., ExamplePolicy).`
				),
				CodeBlock(
`<?php
namespace Common\\Auth\\Policies;

use Proto\\Auth\\Policies\\Policy;

class ExamplePolicy extends Policy
{
    public function default(): bool
    {
        // Return true to allow all non-standard methods, or false to deny
        return true;
    }

    public function get(int $id = 0): bool
    {
        // Check if a user can get a resource
        return true;
    }

    public function before(): bool
    {
        // Called before the policy method
        return true;
    }

    public function after($result): bool
    {
        // Called after the policy method
        return $result;
    }

    public function afterGet($result): bool
    {
        // Called after the get() method
        return $result;
    }
}`
				),
				P(
					{ class: 'text-muted-foreground' },
					`The default() method applies to any controller method that doesn't have an explicit policy method.
					 The before() method runs before the specific policy method,
					 while after() runs after. If you need a specific post-check for a method named get,
					 you can implement afterGet().`
				)
			]),

			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Controller Policy Usage'),
				P(
					{ class: 'text-muted-foreground' },
					`Controllers can use policies to secure their methods when called by an API. You can specify a policy for a controller by setting the policy property in the controller class.`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Module\\User\\Controllers;

use Proto\\Controllers\\ModelController;
use Modules\\User\\Auth\\Policies\\UserPolicy;

class UserController extends ModelController
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = UserPolicy::class;
}`
				),
				P(
					{ class: 'text-muted-foreground' },
					`The Router will use this policy when the controller is called if the controller is registered as a "resource.".`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Modules\\User\\Api;

use Modules\\User\\Controllers\\UserController;

/**
 * User API Routes for Accounts
 *
 * This file handles API routes for user accounts.
 */
router()
    ->resource('user/:userId/account', UserController::class);`
				),
				P(
					{ class: 'text-muted-foreground' },
					`This resource is being secured by the UserController policy which will be called and validated to make sure the API request is allowed.`
				)
			])
		]
	);

export default AuthPage;
