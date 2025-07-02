import { FullscreenPage } from '@base-framework/ui/pages';
import { PageStepContainer } from './page-step-container.js';
import { STEPS } from './steps.js';

/**
 * @typedef {object} PageSettings
 * @property {Function} setupStates - Defines initial state.
 * @property {Function} showStep    - Updates the "step" state.
 */

/**
 * @type {PageSettings}
 * @description
 *  Settings for configuring the sign-up page. Maintains
 *  the `step` in the component’s state and provides a
 *  method to show different steps.
 */
const PageProps =
{
	/**
	 * @function setupStates
	 * @description
	 *  Defines the initial state values for the sign-up page.
	 *
	 * @returns {object} The initial state (with step = WELCOME).
	 */
	setupStates()
	{
		return {
			step: STEPS.WELCOME
		};
	},

	/**
	 * @function showStep
	 * @description
	 *  Updates the `step` state to a new step key.
	 *
	 * @param {string} step - One of the STEPS constants.
	 */
	showStep(step)
	{
		// @ts-ignore
		this.state.step = step;
	}
};

/**
 * @function SignUpPage
 * @description
 *  Constructs a FullscreenPage using our page settings and
 *  the PageStepContainer, which renders all step UI.
 *
 * @returns {object} A FullscreenPage instance.
 */
export const SignUpPage = () =>
(
	new FullscreenPage(PageProps, [
		PageStepContainer()
	])
);

export default SignUpPage;