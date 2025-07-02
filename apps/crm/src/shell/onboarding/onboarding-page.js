import { FullscreenPage } from '@base-framework/ui/pages';
import { PageStepContainer } from './page-step-container.js';
import { STEPS } from './steps.js';

/**
 * This will get the step values.
 *
 * @returns {string[]}
 */
const getStepValues = () => Object.values(STEPS);

/**
 * @constant PageProps
 * @type {object}
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
			step: STEPS.WELCOME,
			next: true,
			previous: false
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
	},

	/**
	 * This will get the next step.
	 *
	 * @returns {string|null}
	 */
	getNextStep()
	{
		const values = getStepValues();
		// @ts-ignore
		const currentStep = this.state.step;
		const stepIndex = values.indexOf(currentStep);

		const result = values[stepIndex + 1];
		if (result)
		{
			this.state.previous = true;
			return result;

		}

		return null;
	},

	/**
	 * This will get the next step.
	 *
	 * @returns {void}
	 */
	nextStep()
	{
		const nextStep = this.getNextStep();
		if (!nextStep)
		{
			app.navigate('/');
			return;
		}

		this.showStep(nextStep);
	},

	/**
	 * This will get the previous step.
	 *
	 * @returns {string}
	 */
	getPrevStep()
	{
		const values = getStepValues();
		// @ts-ignore
		const currentStep = this.state.step;
		const stepIndex = values.indexOf(currentStep);
		if (stepIndex === 1)
		{
			this.state.previous = false;
			return values[0];
		}

		this.state.previous = true;
		return values[stepIndex - 1];
	},

	/**
	 * This will get the previous step.
	 *
	 * @returns {void}
	 */
	prevStep()
	{
		const prevStep = this.getPrevStep();
		this.showStep(prevStep);
	}
};

/**
 * Constructs a FullscreenPage using our page settings and
 * the PageStepContainer, which renders all step UI.
 *
 * @returns {object} A FullscreenPage instance.
 */
export const OnboardingPage = () =>
(
	new FullscreenPage(PageProps, [
		PageStepContainer()
	])
);

export default OnboardingPage;