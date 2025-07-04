import { Icons } from '@base-framework/ui/icons';
import { AuthModel } from '../../../../../../common/models/auth-model.js';
import { STEPS } from '../../steps.js';

/**
 * This will create the notification.
 *
 * @param {string} title - The notification title.
 * @param {string} description - The notification description.
 * @param {string} icon - The notification icon.
 * @param {string} type - The notification type.
 * @returns {void}
 */
const notify = (title, description, icon, type) => (
	app.notify({
		title,
		description: description ?? 'Something went wrong. Please try again later.',
		icon,
		type
	})
);

/**
 * This will set the parent context data.
 *
 * @param {object} parent - The parent component.
 * @param {array} options - The multi-factor authentication options.
 * @returns {void}
 */
const setParentData = (parent, options) =>
{
	const data = parent.context.data;
	data.multiFactor = true;
	data.options = options ?? [];
};

/**
 * This will create the request for the login.
 *
 * @param {object} data
 * @param {function} callBack
 * @returns {void}
 */
const request = (data, callBack) =>
{
	const model = new AuthModel({
		username: data.username,
		password: data.password
	});

	model.xhr.login('', callBack);
};

/**
 * This will create the submit handler for the form.
 *
 * @returns {void}
 */
export const submit = (e, parent) =>
{
	parent.state.loading = true;

	const data = parent.context.data;
	request(data, (response) =>
	{
		parent.state.loading = false;

		/**
		 * Block any invalid responses immediately to prevent
		 * further processing.
		 */
		if (!response || !response.success)
		{
			notify(
				'Error!',
				response.message ?? 'Something went wrong. Please try again later.',
				Icons.warning,
				'destructive'
			);
			return;
		}

		/**
		 * Check if the user is using multi-factor authentication.
		 */
		if (response.multiFactor === true)
		{
			setParentData(parent, response.options || []);
			parent.showStep(STEPS.MULTI_FACTOR_METHOD);
			return;
		}

		/**
		 * Check if the user is allowed access.
		 */
		if (response.allowAccess === true)
		{
			app.signIn(response.user);
			return;
		}

		notify(
			'Invalid Credentials',
			response.message ?? 'The provided credentials are incorrect.',
			Icons.warning,
			'destructive'
		);
	});
};