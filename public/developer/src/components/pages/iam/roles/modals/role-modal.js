import { Div } from "@base-framework/atoms";
import { Fieldset, Input, Textarea } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Modal } from "@base-framework/ui/molecules";
import { RoleModel } from "../models/role-model.js";

/**
 * getRoleForm
 *
 * Returns an array of form fields for creating a new role.
 *
 * @returns {Array} - Array of form field components.
 */
const getRoleForm = () => ([
	Fieldset({ legend: "Role Settings" }, [
		new FormField(
			{ name: "name", label: "Role Name", description: "Enter the name of the role." },
			[
				Input({ type: "text", placeholder: "e.g. Admin", required: true, bind: "name" })
			]
		),
		new FormField(
			{ name: "slug", label: "Slug", description: "URL-friendly version of the role name." },
			[
				Input({ type: "text", placeholder: "e.g. admin", required: true, bind: "slug" })
			]
		),
		new FormField(
			{ name: "description", label: "Description", description: "A brief description of the role." },
			[
				Textarea({ placeholder: "A short description...", rows: 3, bind: "description" })
			]
		)
	])
]);

/**
 * Add a new role.
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
				description: "An error occurred while adding the role.",
				icon: Icons.shield
			});
			return;
		}

		app.notify({
			type: "success",
			title: "Role Added",
			description: "The role has been added.",
			icon: Icons.check
		});
	});
};

/**
 * Update an existing role.
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
				description: "An error occurred while updating the role.",
				icon: Icons.shield
			});
			return;
		}

		app.notify({
			type: "success",
			title: "Role Updated",
			description: "The role has been updated.",
			icon: Icons.check
		});
	});
};

/**
 * RoleModal
 *
 * A modal for creating a new Role using RoleModel data.
 *
 * @param {object} props - The properties for the modal.
 * @returns {Modal} - A new instance of the Modal component.
 */
export const RoleModal = (props = {}) =>
{
	const item = props.item || {};
	const mode = item.id ? 'edit' : 'add';

	return new Modal({
		data: new RoleModel(item),
		title: mode === 'edit' ? 'Edit Role' : 'Add Role',
		icon: Icons.document.add,
		description: `Let's add a new role.`,
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
		}
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, getRoleForm())
		])
	]).open();
};