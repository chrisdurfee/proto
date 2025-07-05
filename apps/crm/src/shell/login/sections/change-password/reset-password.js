import { Strings } from '@base-framework/base';
import { Icons } from '@base-framework/ui/icons';
import { AuthModel } from '../../../../../../common/models/auth-model.js';

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
		password: data.password,
		requestId: data.requestId,
		userId: data.userId
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

	// @ts-ignore
	const params = Strings.parseQueryString();
	const data = {
		password: parent.password.value,
		// @ts-ignore
		requestId: params.requestId || '',
		// @ts-ignore
		userId: params.userId || ''
	};

	request(data, (response) =>
	{
		parent.state.loading = false;
		parent.state.showMessage = true;

		if (!response || !response.success)
		{
			app.notify({
				title: 'Error',
				description: response?.message || 'There was an error resetting your password.',
				icon: Icons.circleX,
				type: 'destructive'
			});
			return;
		}

		if (response.allowAccess === true)
		{
			app.notify({
				title: 'All Done!',
				description: 'You have successfully changed your password.',
				icon: Icons.circleCheck,
				type: 'success'
			});

			app.signIn(response.user);
			app.navigate('/');
			return;
		}

		app.notify({
			title: 'Error',
			description: response?.message || 'There was an error resetting your password.',
			icon: Icons.circleX,
			type: 'destructive'
		});
	});
};