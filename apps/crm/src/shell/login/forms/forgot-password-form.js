import { Div, OnState } from '@base-framework/atoms';
import { Button, EmailInput, LoadingButton } from "@base-framework/ui/atoms";
import { Form } from '@base-framework/ui/molecules';
import { AuthModel } from '../../../../../common/models/auth-model.js';
import { STEPS } from '../steps.js';

/**
 * This will create the email container.
 *
 * @returns {object}
 */
const EmailContainer = () => (
	Div({ class: 'grid gap-4' }, [
		Div({ class: 'grid gap-4' }, [
			EmailInput({
				cache: 'email',
				placeholder: 'Email Address',
				required: true,
				'aria-required': true
			}),
		]),
	])
);

/**
 * Requests a verification code for the selected multi-factor authentication option.
 *
 * @param {object} parent - The parent component.
 */
const requestPasswordReset = (parent) =>
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

/**
 * This will create the submit button.
 *
 * @returns {object}
 */
const SubmitButton = () => (
	Div({ class: 'grid gap-4' }, [
		OnState('loading', (state) => (state)
			? LoadingButton({ disabled: true })
			: Button({ type: 'submit' }, 'Submit')
		)
	])
);

/**
 * This will create the cancel button.
 *
 * @returns {object}
 */
const CancelButton = () => (
	Div({ class: 'grid gap-4' }, [
		Button({ variant: 'outline', 'aria-label': 'Cancel', click: (e, parent) => parent.showStep(STEPS.LOGIN) }, 'Cancel')
	])
);

/**
 * This will create the forgot password form.
 *
 * @returns {object}
 */
export const ForgotPasswordForm = () => (
	Form({ class: 'flex flex-col p-6 pt-0', submit: (e, parent) => requestPasswordReset(parent), role: 'form' }, [
		Div({ class: 'grid gap-4' }, [
			EmailContainer(),
			SubmitButton(),
			CancelButton(),
		])
	])
);