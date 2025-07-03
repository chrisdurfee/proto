import { Div, OnState, Span } from '@base-framework/atoms';
import { LoadingButton } from '@base-framework/ui';
import { Button, Input } from "@base-framework/ui/atoms";
import { Icons } from '@base-framework/ui/icons';
import { Form } from '@base-framework/ui/molecules';
import { Panel } from '@base-framework/ui/organisms';
import { STEPS } from '../steps.js';

/**
 * This will create a sign up link.
 * @returns {object}
 */
const SignUpLink = () => ([
	Div({ class: '' }, [
		Span({ class: 'text-sm text-muted-foreground mt-8 mb-0' }, 'Forgot your password? '),
		Span({ class: 'text-sm font-medium text-primary underline cursor-pointer', click: (e, parent) => parent.parent.showStep(STEPS.FORGOT_PASSWORD) }, 'Reset it'),
	]),
	// Div({ class: '' }, [
	// 	Span({ class: 'text-sm text-muted-foreground mt-8 mb-0' }, 'Don\'t have an account? '),
	// 	Span({ class: 'text-sm font-medium text-primary underline' }, 'Sign up'),
	// ])
]);

/**
 * This will create the credentials container.
 *
 * @returns {object}
 */
const CredentialsContainer = () => (
	Div({ class: 'grid gap-4' }, [
		Div({ class: 'grid gap-4' }, [
			Input({
				type: 'text',
				placeholder: 'Username',
				required: true,
				bind: "username",
				'aria-required': true
			}),
		]),
		Div({ class: 'grid gap-4' }, [
			Input({
				type: 'password',
				placeholder: 'Password',
				required: true,
				bind: 'password',
				//pattern: '^(?=.*[A-Z])(?=.*[a-z])(?=.*\\d)(?=.*\\W).{12,}$',
				title: 'Password must be at least 12 characters long and include uppercase, lowercase, number, and special character.',
				'aria-required': true
			}),
		])
	])
);

/**
 * This will create the sign in button.
 *
 * @returns {object}
 */
const SignInButton = () => (
	Div({ class: 'grid gap-4' }, [
		OnState('loading', (state) => (state)
			? LoadingButton({ disabled: true })
			: Button({ type: 'submit' }, 'Login')
		)
	])
);

/**
 * This will create the sign in with google button.
 *
 * @returns {object}
 */
const SignInWIthGoogleButton = () => (
	Div({ class: 'grid gap-4' }, [
		Button({ variant: 'outline', 'aria-label': 'Login with Google' }, 'Login with Google')
	])
);

/**
 * This will create the submit handler for the form.
 *
 * @returns {void}
 */
const submit = (e, parent) =>
{
	parent.state.loading = true;
	parent.data.xhr.login('', (response) =>
	{
		parent.state.loading = false;
		if (!response || !response.success)
		{
			app.notify({
				title: 'Error!',
				description: response.message ?? 'Something went wrong. Please try again later.',
				icon: Icons.warning,
				type: 'destructive'
			});
			return;
		}

		if (response.multiFactor === true)
		{
			const page = parent.parent;
			const data = page.context.data;
			data.multiFactor = true;
			data.options = response.options ?? [];

			page.showStep(STEPS.MULTI_FACTOR_METHOD);
			return;
		}

		if (response.allowAccess === true)
		{
			app.signIn();
			return;
		}
	});
};

/**
 * This will create the login form.
 *
 * @returns {Panel}
 */
export const LoginForm = () => (
	Form({ class: 'flex flex-col p-6 pt-0', submit, role: 'form' }, [
		Div({ class: 'grid gap-4' }, [
			CredentialsContainer(),
			SignInButton(),
			SignInWIthGoogleButton(),
			SignUpLink(),
		]),
	])
);