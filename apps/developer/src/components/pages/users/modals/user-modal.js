import { Div } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Modal } from "@base-framework/ui/molecules";
import { UserModel } from "../models/user-model.js";
import { UserForm } from "./user-form.js";
import { validate } from "./validate.js";

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
	const data = new UserModel(item);

	return new Modal({
		data,
		title: mode === 'edit' ? 'Edit User' : 'Add User',
		icon: mode === 'edit' ? Icons.pencil.square : Icons.user.plus,
		description: mode === 'edit' ? 'Update user details.' : 'Create a new user.',
		size: 'md',
		type: 'right',
		onClose: () => props.onClose && props.onClose(data),
		onSubmit: ({ data }) =>
		{
			if (mode === 'edit')
			{
				update(data);
			}
			else
			{
				const password = data.password;
				const confirmPassword = data.confirmPassword;
				if (!validate(password, confirmPassword))
				{
					return;
				}

				add(data);
			}
		}
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, UserForm({
				isEditing: mode === 'edit',
				user: data
			}))
		])
	]).open();
};