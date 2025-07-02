import { Div, Form, Span } from '@base-framework/atoms';
import { Button, Input } from "@base-framework/ui/atoms";
import { STEPS } from '../steps.js';

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
			Input( { type: 'text', placeholder: 'Username', required: true, 'aria-required': true } ),
		]),
		Div({ class: 'grid gap-4' }, [
			Input( { type: 'password', placeholder: 'Password', required: true, 'aria-required': true } ),
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
		Button({ type: 'submit' }, 'Login')
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
	// @ts-ignore
	Form({ class: 'flex flex-col p-6 pt-0', submit: () => app.signIn(), role: 'form' }, [
		Div({ class: 'grid gap-4' }, [
			CredentialsContainer(),
			SignInButton(),
			SignInWIthGoogleButton(),
			SignUpLink(),
		]),
	])
);