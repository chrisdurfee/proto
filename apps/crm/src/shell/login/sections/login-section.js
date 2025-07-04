import { Div, H1, Header, P, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { LoginForm } from '../forms/login-form.js';

/**
 * This will create a header for the documentation.
 *
 * @param {object} props
 * @returns {object}
 */
const LoginHeader = Atom(({ title, description}) => (
	Header({ class: 'flex flex-col space-y-1.5 p-6' }, [
		H1({ class: 'scroll-m-20 text-3xl font-bold tracking-tight' }, title),
		description && P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, description),
	])
));

/**
 * This will create a form wrapper.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const FormWrapper = Atom((props, children) => (
	Div({
		class: 'rounded-xl sm:border sm:shadow-lg bg-card text-card-foreground shadow w-full mx-auto max-w-sm'
	}, children)
));

/**
 * This will create the login section.
 *
 * @returns {object}
 */
export const LoginSection = () => (
	Section({ class: 'flex flex-auto flex-col justify-center items-center' }, [
		FormWrapper({ class: 'rounded-xl sm:border sm:shadow-lg bg-card text-card-foreground shadow w-full mx-auto max-w-sm' }, [
			Div([
				LoginHeader({ title: 'Login', description: 'Please enter your credentials to login.' }),
				LoginForm()
			])
		])
	])
);