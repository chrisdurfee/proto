import { Icons } from '@base-framework/ui/icons';
import { AuthModel } from '../../../../../../common/models/auth-model.js';
import { Configs } from '../../../../configs.js';

/**
 * This will send a request to reset the user's password.
 *
 * @param {object} data
 * @param {function} callBack
 * @returns {void}
 */
const request = (data, callBack) =>
{
    const model = new AuthModel({
		email: data.email
	});

	model.xhr.resetPassword('', callBack);
};

/**
 * Resets the user's password.
 *
 * @param {object} parent - The parent component.
 */
export const resetPassword = (parent) =>
{
	parent.state.loading = true;

	request({ email: parent.email.value }, (response) =>
	{
		parent.state.loading = false;
		parent.state.showMessage = true;

		app.notify({
			title: 'All Done!',
			description: 'You have successfully changed your password.',
			icon: Icons.circleCheck,
			type: 'success'
		});

		app.navigate(Configs.router.baseUrl);
	});
};