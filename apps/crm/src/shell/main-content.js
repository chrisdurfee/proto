import { Main } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { modules } from '../modules/modules.js';
import { AppControl } from './navigation/app-control.js';
import { MobileHeader } from './navigation/mobile-header.js';

/**
 * This will create the active panel container.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const ActivePanelContainer = Atom((props, children) =>
{
	return Main({
		class: 'active-panel-container flex flex-auto relative z-0 md:pb-0',
		...props,
		children
	});
});

/**
 * This will create the main content of the app shell.
 *
 * @returns {Array<object>}
 */
export const MainContent = () =>
{
	const { routes, links: options } = modules;

	return [
		MobileHeader(),

		/**
		 * This will add the desktop and mobile navigation.
		 */
		new AppControl({ options }),

		/**
		 * This will add the active panel container that will hold the main body.
		 */
		ActivePanelContainer({
			switch: routes,
			cache: 'mainBody'
		})
	];
};

export default MainContent;