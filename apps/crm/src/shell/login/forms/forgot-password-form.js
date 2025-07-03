import { Div } from '@base-framework/atoms';
import { Button, EmailInput } from "@base-framework/ui/atoms";
import { Form } from '@base-framework/ui/molecules';
import { STEPS } from '../steps';

/**
 * This will create the email container.
 *
 * @returns {object}
 */
const EmailContainer = () => (
	Div({ class: 'grid gap-4' }, [
		Div({ class: 'grid gap-4' }, [
			EmailInput( { placeholder: 'Email Address', required: true, 'aria-required': true } ),
		]),
	])
);

/**
 * This will create the submit button.
 *
 * @returns {object}
 */
const SubmitButton = () => (
	Div({ class: 'grid gap-4' }, [
		Button({ type: 'submit' }, 'Submit')
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
	Form({ class: 'flex flex-col p-6 pt-0', submit: (e, parent) => parent.state.showMessage = true, role: 'form' }, [
		Div({ class: 'grid gap-4' }, [
			EmailContainer(),
			SubmitButton(),
			CancelButton(),
		])
	])
);