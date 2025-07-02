import { Div, Form, H2, Header, P, Span } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Icon } from '@base-framework/ui';
import { Button, Input } from '@base-framework/ui/atoms';
import { Icons } from "@base-framework/ui/icons";
import { STEPS } from '../steps';

/**
 * @function CredentialsContainer
 * @description
 *  Input fields for username and password.
 *
 * @returns {object} A Div containing the input fields.
 */
const CredentialsContainer = Atom(() =>
(
    Div({ class: 'grid gap-4' }, [
        Div({ class: 'grid gap-4' }, [
            Input({
                type: 'email',
                placeholder: 'name@example.com',
                required: true,
                'aria-required': true
            })
        ])
    ])
));

/**
 * @function SignUpButton
 * @description
 *  Primary button to submit the form and sign up.
 *
 * @returns {object} A Div containing the submit button.
 */
const SignUpButton = Atom(() =>
(
    Div({ class: 'grid gap-4' }, [
        Button({ type: 'submit' }, 'Sign Up')
    ])
));

/**
 * @function SignUpWithGoogleButton
 * @description
 *  A secondary button to sign up with Google OAuth.
 *
 * @returns {object} A Div containing the "Sign up with Google" button.
 */
const SignUpWithGoogleButton = Atom(() =>
(
    Button({
		variant: 'outline',
		class: "gap-2 w-full",
		click: () => console.log("Sign in with Google"),
		"aria-label": "Sign in with Google"
	}, [
		Icon(Icons.companies.google || ''),
		Span("Google")
	])
));

/**
 * This will create the card header.
 *
 * @param {object} props
 * @returns {object}
 */
export const CardHeader = ({ title, description }) => (
	Header({ class: 'text-center py-6 flex flex-auto flex-col space-y-1' }, [
		H2({ class: "font-semibold tracking-tight text-2xl" }, title),
		P({ class: "text-sm text-muted-foreground" }, description)
	])
);

/**
 * This will create a form wrapper.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const FormWrapper = Atom((props, children) => (
	Div({ class: 'w-full mx-auto max-w-sm p-6' }, children)
));

/**
 * @function SignUpForm
 * @description
 *  The main form for collecting user credentials. Mimics the login form structure.
 *
 * @returns {object} A Form containing the fields and buttons.
 */
export const SignUpForm = Atom(() =>
(
	FormWrapper([
		CardHeader({ title: "Create an account", description: "Enter your email below to create your account" }),
		Form({
			class: 'flex flex-col',
			role: 'form',
			submit: (e, parent) =>
			{
				e.preventDefault();
				parent.showStep(STEPS.USER_DETAILS);
			}
		}, [
			Div({ class: 'grid gap-4' }, [
				CredentialsContainer(),
				SignUpButton(),

				// Divider for "OR CONTINUE WITH"
				Div({ class: "relative" }, [
					Div({ class: "absolute inset-0 flex items-center" }, [
						Span({ class: "flex-grow border-t" })
					]),
					Div({ class: 'relative flex justify-center text-xs uppercase' }, [
						Span({ class: 'bg-background px-2 text-muted-foreground' }, "or continue with")
					]),
				]),

				SignUpWithGoogleButton(),
			])
		])
	])
));