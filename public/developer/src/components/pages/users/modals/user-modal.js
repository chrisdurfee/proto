import { Div } from "@base-framework/atoms";
import { Fieldset, Input } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Modal } from "@base-framework/ui/molecules";
import { UserModel } from "../models/user-model.js"; // Assumes a JS UserModel exists

/**
 * getUserForm
 *
 * Returns an array of form fields for creating or editing a User.
 *
 * @returns {Array} - Array of form field components.
 */
const getUserForm = () => ([
	Fieldset({ legend: "User Information" }, [
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
			{ name: "password", label: "Password", description: "Enter a password." },
			[
				Input({
					type: "password",
					placeholder: "Enter password",
					required: true,
					bind: "password"
				})
			]
		),
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
		)
	])
]);

/**
 * Add a new user.
 *
 * @param {object} data
 * @returns {void}
 */
const add = (data) =>
{
	data.xhr.add('', (response) =>
    {
		if (!response || response.success === false)
        {
			app.notify({
				type: "destructive",
				title: "Error",
				description: "An error occurred while adding the user.",
				icon: Icons.shield
			});
			return;
		}

		app.notify({
			type: "success",
			title: "User Added",
			description: "The user has been added.",
			icon: Icons.check
		});
	});
};

/**
 * Update an existing user.
 *
 * @param {object} data
 * @returns {void}
 */
const update = (data) =>
{
	data.xhr.update('', (response) =>
    {
		if (!response || response.success === false)
        {
			app.notify({
				type: "destructive",
				title: "Error",
				description: "An error occurred while updating the user.",
				icon: Icons.shield
			});
			return;
		}

		app.notify({
			type: "success",
			title: "User Updated",
			description: "The user has been updated.",
			icon: Icons.check
		});
	});
};

/**
 * UserModal
 *
 * A modal for creating or editing a User using UserModel data.
 *
 * @param {object} props - The properties for the modal.
 * @returns {Modal} - A new instance of the Modal component.
 */
export const UserModal = (props = {}) =>
{
	const item = props.item || {};
	const mode = item.id ? 'edit' : 'add';

	return new Modal({
		data: new UserModel(item),
		title: mode === 'edit' ? 'Edit User' : 'Add User',
		icon: Icons.document.add,
		description: mode === 'edit' ? 'Update user details.' : 'Create a new user.',
		size: 'md',
		type: 'right',
		onSubmit: ({ data }) =>
        {
			if (mode === 'edit')
            {
				update(data);
			}
            else
            {
				add(data);
			}

			if (props.onClose)
            {
				props.onClose(data);
			}
		}
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, getUserForm())
		])
	]).open();
};