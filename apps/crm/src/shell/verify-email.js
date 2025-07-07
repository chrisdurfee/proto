import { Strings } from "@base-framework/base";
import { Icons } from '@base-framework/ui/icons';
import { Confirmation } from '@base-framework/ui/molecules';

/**
 * Request email verification.
 *
 * @param {object} data - The request data.
 * @param {function} callBack - The callback function.
 */
const request = (data, callBack) =>
{
	const DELAY = 1000;
	setTimeout(() =>
	{
		app.data.user.xhr.verifyEmail(data, callBack);
	}, DELAY);
};

/**
 * Verify the user's email address.
 *
 * @returns {void}
 */
export const verifyEmail = () =>
{
	const urlParams = Strings.parseQueryString();
	const data = {
		// @ts-ignore
		requestId: urlParams.requestId
	};

	request(data, (response) =>
	{
		if (response.success)
		{
			showDialog();
			return;
		}

		app.notify({
			icon: Icons.circleX,
			title: 'Invalid Code',
			description: response.message ?? 'The provided code is incorrect.',
			type: 'destructive'
		});
	});
};

/**
 * Show the email verification dialog.
 *
 * @returns {void}
 */
const showDialog = () =>
{
	new Confirmation({
		icon: Icons.circleCheck,
		type: 'success',
		title: 'Email Verified',
		description: 'Your email has been verified successfully.',
		confirmTextLabel: 'Continue',
		//onClose: () => app.navigate('/', null, true)
	}).open();
};