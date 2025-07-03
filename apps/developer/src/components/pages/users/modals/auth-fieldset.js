import { Fieldset, Input } from "@base-framework/ui/atoms";
import { FormField } from "@base-framework/ui/molecules";
import { validate } from "./validate.js";

/**
 * This will create the authentication fieldset.
 *
 * @returns {object}
 */
export const AuthFieldset = () => (
	Fieldset({ legend: "Authentication" }, [

		new FormField(
			{ name: "username", label: "Username", description: "Enter the user's username." },
			[
				Input({
					type: "text",
					placeholder: "e.g. john_doe",
					required: true,
					bind: "username"
				})
			]
		),
		new FormField(
			{ name: "password", label: "Password", description: "Password must be at least 12 characters long and include uppercase, lowercase, number, and special character." },
			[
				// New Password
				Input({
					type: 'password',
					placeholder: 'New Password',
					required: true,
					bind: 'password',
					pattern: '^(?=.*[A-Z])(?=.*[a-z])(?=.*\\d)(?=.*\\W).{12,}$',
					title: 'Password must be at least 12 characters long and include uppercase, lowercase, number, and special character.',
					'aria-required': true
				})
			]
		),
		new FormField(
			{ name: "confirmPassword", label: "Confirm Password", description: "Enter the password again." },
			[
				// Confirm New Password
				Input({
					type: 'password',
					placeholder: 'Confirm New Password',
					required: true,
					bind: 'confirmPassword',
					pattern: '^(?=.*[A-Z])(?=.*[a-z])(?=.*\\d)(?=.*\\W).{12,}$',
					title: 'Password must be at least 12 characters long and include uppercase, lowercase, number, and special character.',
					'aria-required': true,
					blur: (e, { parent }) => validate(parent.data.password, parent.data.confirmPassword)
				})
			]
		)
	])
);