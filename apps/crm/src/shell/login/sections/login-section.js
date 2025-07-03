import { Div, H1, Header, OnState, P, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Panel } from '@base-framework/ui';
import { AuthModel } from '../../models/auth-model.js';
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
 * This will create a loading message.
 *
 * @returns {object}
 */
const LoadingMessage = () => (
	null
);

/**
 * @typedef {object} Props
 */
const Props =
{
	class: 'flex flex-auto flex-col',

	/**
	 * Sets the data for the authentication model.
	 *
	 * @return {object}
	 */
	setData()
	{
		return new AuthModel();
	},

	/**
	 * Sets up the states for the authentication model.
	 *
	 * @return {object}
	 */
	setupStates()
	{
		return {
			loading: true
		};
	}
};

/**
 * This will create the login section.
 *
 * @returns {object}
 */
export const LoginSection = () => (
	new Panel(Props, [
		Section({ class: 'flex flex-auto flex-col justify-center items-center' }, [
			FormWrapper({ class: 'rounded-xl sm:border sm:shadow-lg bg-card text-card-foreground shadow w-full mx-auto max-w-sm' }, [
				OnState('loading', (state) => (!state)
					? LoadingMessage()
					: Div([
						LoginHeader({ title: 'Login', description: 'Please enter your credentials to login.' }),
						LoginForm()
					])
				)
			])
		])
	])
);