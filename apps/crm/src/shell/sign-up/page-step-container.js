import { Div, OnState } from '@base-framework/atoms';
import { CongratulationsSection } from './sections/congratulations-section.js';
import { ErrorSection } from './sections/error-section.js';
import { UserDetailsSection } from './sections/user-details-section.js';
import { WelcomeSection } from './sections/welcome-section.js';
import { STEPS } from './steps.js';

/**
 * @function PageStepContainer
 * @description
 *  Renders the correct page section based on current step.
 *
 * @returns {object} A Div that conditionally renders each step’s section.
 */
export const PageStepContainer = () =>
(
	Div({ class: 'flex flex-auto flex-col' }, [
		OnState('step', (step) =>
		{
			switch (step)
			{
				case STEPS.USER_DETAILS:
					return UserDetailsSection();

				case STEPS.ERROR:
					return ErrorSection();

				case STEPS.CONGRATULATIONS:
					return CongratulationsSection();

				case STEPS.WELCOME:
				default:
					return WelcomeSection();
			}
		})
	])
);