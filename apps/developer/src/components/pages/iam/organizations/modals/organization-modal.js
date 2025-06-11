import { Div } from "@base-framework/atoms";
import { Fieldset, Input } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Modal } from "@base-framework/ui/molecules";
import { OrganizationModel } from "../models/organization-model.js";

/**
 * getOrganizationForm
 *
 * Returns an array of form fields for creating a new organization.
 *
 * @returns {Array} - Array of form field components.
 */
const getOrganizationForm = () => ([
	Fieldset({ legend: "Organization Settings" }, [
		new FormField(
			{ name: "name", label: "Organization Name", description: "Enter the name of the organization." },
			[
				Input({ type: "text", placeholder: "e.g. Acme Corp", required: true, bind: "name" })
			]
		)
	])
]);

/**
 * Add a new organization.
 *
 * @param {object} data
 * @param {function} onClose
 * @returns {void}
 */
const add = (data, onClose) =>
{
	data.xhr.add('', (response) =>
	{
		if (!response || response.success === false)
		{
			app.notify({
				type: "destructive",
				title: "Error",
				description: "An error occurred while adding the permission.",
				icon: Icons.shield
			});
			return;
		}

		app.notify({
			type: "success",
			title: "Permission Added",
			description: "The permission has been added.",
			icon: Icons.check
		});

		onClose();
	});
};

/**
 * Update an existing organization.
 *
 * @param {object} data
 * @param {function} onClose
 * @returns {void}
 */
const update = (data, onClose) =>
{
	data.xhr.update('', (response) =>
	{
		if (!response || response.success === false)
		{
			app.notify({
				type: "destructive",
				title: "Error",
				description: "An error occurred while updating the permission.",
				icon: Icons.shield
			});
			return;
		}

		app.notify({
			type: "success",
			title: "Permission Updated",
			description: "The permission has been updated.",
			icon: Icons.check
		});

		onClose();
	});
};

/**
 * OrganizationModal
 *
 * A modal for creating a new Organization using OrganizationModel data.
 *
 * @param {object} props - The properties for the modal.
 * @returns {Modal} - A new instance of the Modal component.
 */
export const OrganizationModal = (props = {}) =>
{
	const item = props.item || {};
	const mode = item.id ? 'edit' : 'add';

	return new Modal({
		data: new OrganizationModel(item),
		title: mode === 'edit' ? 'Edit Organization' : 'Add Organization',
		icon: mode === 'edit' ? Icons.pencil.square : Icons.document.add,
		description: mode === 'edit' ? `Editing the '${item.name}' Organization` : 'Let\'s add a new Organization.',
		size: 'md',
		type: 'right',
		onSubmit: ({ data }) =>
		{
			const onClose = () => props.onClose && props.onClose(data);

			if (mode === 'edit')
			{
				update(data, onClose);
			}
			else
			{
				add(data, onClose);
			}
		}
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, getOrganizationForm())
		])
	]).open();
};