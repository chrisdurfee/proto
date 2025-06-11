import { Div } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { NotificationContainer } from "@base-framework/ui/molecules";
import { AppContent } from './app-content.js';
import { Links } from './links.js';
import { Routes } from './routes.js';

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
 * @returns {object}
 */
export const AppShell = () => (
	Shell([
		AppContent({
			options: Links(),
			routes: Routes()
		})
	])
);