import { FullscreenPage } from "@base-framework/ui/pages";
import { PageStepContainer } from './page-step-container.js';
import { STEPS } from './steps.js';

/**
 * This will setup the page settings.
 *
 * @type {object} PageSettings
 */
const PageProps =
{
	/**
	 * This will setup the states.
	 *
	 * @returns {object}
	 */
	setupStates()
	{
		return {
			step: STEPS.LOGIN
		};
	},

	/**
	 * This will show the step.
	 *
	 * @param {string} step
	 * @returns {void}
	 */
	showStep(step)
	{
		this.state.step = step;
	}
};

/**
 * LoginPage
 *
 * This will create a login page.
 *
 * @returns {FullscreenPage}
 */
export const LoginPage = () => (
	new FullscreenPage(PageProps, [
		PageStepContainer()
	])
);

export default LoginPage;