import { AuthModel } from '../../../../../../common/models/auth-model.js';

/**
 * Requests a verification code for the selected multi-factor authentication option.
 *
 * @param {object} parent - The parent component.
 */
export const requestPasswordReset = (parent) =>
{
	parent.state.loading = true;
	const model = new AuthModel({
		email: parent.email.value
	});

	model.xhr.requestPasswordReset('', (response) =>
	{
		parent.state.loading = false;
		parent.state.showMessage = true;
	});
};