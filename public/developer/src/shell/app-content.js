import { Div } from '@base-framework/atoms';
import { openInstallPrompt } from './installation/install.js';
import { MainContent } from './main-content.js';

/**
 * This will create the app content.
 *
 * @param {object} props
 * @returns {object}
 */
export const AppContent = (props) => (
	Div({
		class: 'app-content flex flex-auto flex-col will-change-contents',

		/**
		 * This will open the install prompt when the app is created.
		 *
		 * @returns {void}
		 */
		onCreated()
		{
			const WAIT_TIME = 1000;
			window.setTimeout(() => openInstallPrompt(), WAIT_TIME);
		}
	}, [
		MainContent(props)
	])
);