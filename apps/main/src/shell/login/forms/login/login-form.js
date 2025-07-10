import { Div, Span } from '@base-framework/atoms';
import { Button, Input } from '@base-framework/ui/atoms';
import { Form } from '@base-framework/ui/molecules';
import { STEPS } from '../../steps.js';
import { SignInButton } from './sign-in-button.js';
import { submit } from './submit.js';

/**
 * This will create a sign up link.
 * @returns {object}
 */
const SignUpLink = () => ([
	Div({ class: '' }, [
		Span({ class: 'text-sm text-muted-foreground mt-8 mb-0' }, 'Forgot your password? '),
		Span({ class: 'text-sm font-medium text-primary underline cursor-pointer', click: (e, parent) => parent.showStep(STEPS.FORGOT_PASSWORD) }, 'Reset it'),
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
				pattern: '^(?=.*[A-Z])(?=.*[a-z])(?=.*\\d)(?=.*\\W).{12,}$',
				title: 'Password must be at least 12 characters long and include uppercase, lowercase, number, and special character.',
				'aria-required': true
			}),
		])
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
 * This will create the login form.
 *
 * @returns {object}
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