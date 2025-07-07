import { Div } from '@base-framework/atoms';
import { Atom, Component, Jot } from '@base-framework/base';
import { NotificationContainer } from '@base-framework/ui/molecules';
import { verifyEmail } from './verify-email.js';

/**
 * This will create the app container.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const Shell = Atom((props, children) =>
{
	return Div({
			...props,
			class: 'shell flex flex-auto relative z-10'
		}, [
			/**
			 * This will set up the notification container so that it can be used
			 * throughout the app.
			 */
			new NotificationContainer({
				cache: 'notifications'
			}),
			...children
	]);
});

/**
 * AppShell
 *
 * This will create the app shell.
 *
 * @type {typeof Component} AppShell
 */
export const AppShell = Jot(
{
	/**
	 * This will render the component.
	 *
	 * @returns {object}
	 */
	render()
	{
		return Shell([
			Div({
				class: 'flex flex-auto flex-col',
				route: {
					uri: 'verify-email*',
					callBack: () => verifyEmail()
				},
				switch: [
					{
						uri: '/sign-up*',
						import: () => import('./sign-up/sign-up-page.js')
					},
					{
						uri: '/onboarding*',
						import: () => import('./onboarding/onboarding-page.js')
					},
					{
						import: () => import('./app-content.js'),
						preventScroll: true
					}
				]
			})
		]);
	}
});