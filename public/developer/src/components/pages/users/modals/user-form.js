import { Fieldset, Input, TelInput } from "@base-framework/ui/atoms";
import { FormField } from "@base-framework/ui/molecules";
import { UserRoleFieldset } from "./user-role-fieldset.js";
import { validate } from "./validate.js";

/**
 * This will create the authentication fieldset.
 *
 * @returns {object}
 */
const AuthFieldset = () => (
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

/**
 * This will create the user fieldset.
 *
 * @param {boolean} isEditing - Whether the user is being edited or not.
 * @returns {object}
 */
const UserFieldset = (isEditing) => (
    Fieldset({ legend: "User Information" }, [

		new FormField(
			{ name: "firstName", label: "First Name", description: "Enter the user's first name." },
			[
				Input({
					type: "text",
					placeholder: "John",
					required: true,
					bind: "firstName"
				})
			]
		),
		new FormField(
			{ name: "lastName", label: "Last Name", description: "Enter the user's last name." },
			[
				Input({
					type: "text",
					placeholder: "Doe",
					required: true,
					bind: "lastName"
				})
			]
		),
		new FormField(
			{ name: "email", label: "Email", description: "Enter the user's email address." },
			[
				Input({
					type: "email",
					placeholder: "e.g. john@example.com",
					required: true,
					bind: "email"
				})
			]
		),
		new FormField(
			{ name: "mobile", label: "Mobile", description: "Enter the user's mobile number." },
			[
				TelInput({
					placeholder: "e.g. +1234567890",
					required: true,
					bind: "mobile"
				})
			]
		)
	])
);

/**
 * UserForm
 *
 * Returns an array of form fields for creating or editing a User.
 *
 * @param {object} props - The properties for the form.
 * @returns {Array} - Array of form field components.
 */
export const UserForm = ({ isEditing, user }) => ([
	(!isEditing) && AuthFieldset(),
	UserFieldset(isEditing),
	(isEditing) && new UserRoleFieldset({
		user
	})
]);