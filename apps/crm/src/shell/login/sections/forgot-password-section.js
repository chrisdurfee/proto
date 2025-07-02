import { Div, H1, Header, OnState, P, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Icons } from "@base-framework/ui/icons";
import { Alert } from "@base-framework/ui/molecules";
import { ForgotPasswordForm } from '../forms/forgot-password-form.js';

/**
 * This will create a header for the documentation.
 *
 * @param {object} props
 * @returns {object}
 */
const PasswordHeader = Atom(({ title, description}) => (
	Header({ class: 'flex flex-col space-y-1.5 p-6' }, [
		H1({ class: 'scroll-m-20 text-3xl font-bold tracking-tight' }, title),
		description && P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, description),
	])
));

/**
 * This will create a success message.
 *
 * @returns {object}
 */
const SuccessMessage = () => (
	Alert({
		title: 'Success!',
		description: 'Check your email for instructions to reset your password.',
		icon: Icons.circleCheck
	})
);

/**
 * This will create a form wrapper.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const FormWrapper = Atom((props, children) => (
	Div({
		class: 'rounded-xl sm:border sm:shadow-lg bg-card text-card-foreground shadow w-full mx-auto max-w-sm',
		addState()
		{
			return {
				showMessage: false
			};
		}
	}, children)
));

/**
 * This will create the forgot password section.
 *
 * @returns {object}
 */
export const ForgotPasswordSection = () => (
	Section({ class: 'flex flex-auto flex-col justify-center items-center' }, [
		FormWrapper([
			OnState('showMessage', (state) => (state)
				? SuccessMessage()
				: [
					PasswordHeader({ title: 'Forgot Password', description: 'Please enter your email address.' }),
					ForgotPasswordForm(),
				]
			)
		])
	])
);