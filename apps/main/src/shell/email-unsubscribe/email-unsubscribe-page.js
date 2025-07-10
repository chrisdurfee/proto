import { Strings } from '@base-framework/base';
import { FullscreenPage } from '@base-framework/ui/pages';
import { UserData } from '../models/user-data.js';
import { PageStepContainer } from './page-step-container.js';
import { STEPS } from './steps.js';

/**
 * @constant PageProps
 * @type {object}
 */
const PageProps =
{
	/**
	 * @function setupStates
	 *
	 * @returns {object}
	 */
	setupStates()
	{
		return {
			step: STEPS.VERIFYING
		};
	},

	/**
	 * @function showStep
	 *
	 * @param {string} step - One of the STEPS constants.
	 */
	showStep(step)
	{
		// @ts-ignore
		this.state.step = step;
	},

	/**
	 * Unsubscribes the user from email notifications.
	 *
	 * @returns {void}
	 */
	unsubscribe()
	{
		const params = Strings.parseQueryString();
		const model = new UserData({
			// @ts-ignore
			email: params.email
		});

		model.xhr.unsubscribe(params, (response) =>
		{
			if (!response || !response.success)
			{
				this.showStep(STEPS.ERROR);
				return;
			}

			this.showStep(STEPS.SUCCESS);
		});
	},

	/**
	 * Calls the unsubscribe method after setup.
	 *
	 * @returns {void}
	 */
	afterSetup()
	{
		this.unsubscribe();
	}
};

/**
 * Constructs a FullscreenPage using our page settings and
 * the PageStepContainer, which renders all step UI.
 *
 * @returns {object} A FullscreenPage instance.
 */
export const EmailUnsubscribePage = () =>
(
	new FullscreenPage(PageProps, [
		PageStepContainer()
	])
);

export default EmailUnsubscribePage;