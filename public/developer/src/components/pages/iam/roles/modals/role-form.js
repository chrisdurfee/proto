import { Fieldset, Input, Select, Textarea } from "@base-framework/ui/atoms";
import { FormField } from "@base-framework/ui/molecules";
import { RolePermissionFieldset } from "./role-permission-fieldset.js";

/**
 * RoleForm
 *
 * @param {object} props - The properties for the form. *
 * @returns {Array} - Array of form field components.
 */
export const RoleForm = ({ isEditing, role }) => ([
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
		),
		new FormField(
			{ name: "resource", label: "Resource", description: "Defines the scope of the role." },
			[
				Select({
					bind: "resource",
					options: [
						{ value: "global", label: "Global" },
						{ value: "organization", label: "Organization" },
						{ value: "group", label: "Group" }
					]
				})
			]
		)
	]),
    isEditing && new RolePermissionFieldset({
        role
    })
]);