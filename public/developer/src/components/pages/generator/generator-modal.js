import { Div } from "@base-framework/atoms";
import { Checkbox, Fieldset, Input, Select, Textarea } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Modal } from "@base-framework/ui/molecules";

/**
 * GeneratorModal
 *
 * A single modal that displays different fields depending on the resource type.
 *
 * @param {object} props
 * @param {string} props.resourceType - The type of resource (e.g. "API", "Model", "Full Resource", etc.)
 * @returns {object}
 */
export const GeneratorModal = ({ resourceType = 'Full Resource' }) =>
(
	new Modal({
		title: `Add ${resourceType}`,
		icon: Icons.document.add,
		description: `Let's add a new ${resourceType}.`,
		size: 'md',		// you can adjust size to 'sm', 'md', 'lg', etc.
		type: 'right',
		onSubmit: () => app.notify({
			type: "success",
			title: `${resourceType} Added`,
			description: `The ${resourceType} has been added.`,
			icon: Icons.check
		})
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, getResourceForm(resourceType))
		])
	]).open()
);

/**
 * getResourceForm
 *
 * Returns an array of fieldsets for each resource type.
 *
 * @param {string} type
 * @returns {Array}
 */
function getResourceForm(type)
{
	switch (type)
	{
		case "API":
			return [
				Fieldset({ legend: "API Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The class name for the API." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the API." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub" })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this API extends." }, [
						Input({ type: "text", value: "BaseAPI", required: true })
					]),
					new FormField({ name: "policy", label: "Policy", description: "Optional policy for this API." }, [
						Input({ type: "text", placeholder: "e.g. Policy" })
					])
				])
			];

		case "Controller":
			return [
				Fieldset({ legend: "Controller Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The class name for the controller." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the controller." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub" })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this controller extends." }, [
						Input({ type: "text", value: "Controller", required: true })
					])
				])
			];

		case "Model":
			return [
				Fieldset({ legend: "Model Settings" }, [
					new FormField({ name: "connection", label: "Connection", description: "Database connection name." }, [
						Input({ type: "text", placeholder: "e.g. dashr" })
					]),
					new FormField({ name: "className", label: "Class Name", description: "The class name for the model." }, [
						Input({ type: "text", placeholder: "e.g. ModelName", required: true })
					]),
					new FormField({ name: "tableName", label: "Table Name", description: "The database table name." }, [
						Input({ type: "text", placeholder: "e.g. table_name" })
					]),
					new FormField({ name: "alias", label: "Alias", description: "An alias used in queries." }, [
						Input({ type: "text", placeholder: "e.g. a" })
					]),
					new FormField({ name: "fields", label: "Fields", description: "Define fields for the model." }, [
						Textarea({ placeholder: "e.g.\nid:\ncreatedAt:\nupdatedAt:", rows: 4 })
					]),
					new FormField({ name: "joins", label: "Joins", description: "Define joins for the model." }, [
						Textarea({ placeholder: "e.g.\n$builder->left('test_table', 't')->on('id', 'client_id');", rows: 4 })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this model extends." }, [
						Input({ type: "text", value: "Model", required: true })
					]),
					new FormField({ name: "storage", label: "Storage", description: "Whether to attach a storage layer." }, [
						new Checkbox({ checked: false })
					])
				])
			];

		case "Storage":
			return [
				Fieldset({ legend: "Storage Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The class name for storage." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for storage." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub" })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this storage extends." }, [
						Input({ type: "text", value: "Storage", required: true })
					]),
					new FormField({ name: "connection", label: "Connection", description: "The database/storage connection name." }, [
						Input({ type: "text", placeholder: "e.g. prod" })
					])
				])
			];

		case "Policy":
			return [
				Fieldset({ legend: "Policy Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The class name for the policy." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the policy." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub" })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this policy extends." }, [
						Input({ type: "text", value: "Policy", required: true })
					])
				])
			];

		case "Table":
			return [
				Fieldset({ legend: "Table Settings" }, [
					new FormField({ name: "connection", label: "Connection", description: "The database connection name." }, [
						Input({ type: "text", placeholder: "e.g. dashr" })
					]),
					new FormField({ name: "tableName", label: "Table Name", description: "The table name." }, [
						Input({ type: "text", placeholder: "e.g. table_name", required: true })
					]),
					new FormField({ name: "callback", label: "Call Back", description: "The table creation or modification logic." }, [
						Textarea({ placeholder: "e.g.\n$table->id();\n$table->createdAt();\n...", rows: 6 })
					])
				])
			];

		case "Migration":
			return [
				Fieldset({ legend: "Migration Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The migration class name." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true })
					])
				])
			];

		case "Unit Test":
			return [
				Fieldset({ legend: "Unit Test Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The class name for the test." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the test." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub" })
					]),
					new FormField({ name: "type", label: "Type", description: "The type of test." }, [
						Select({
							options: [
								{ label: "Unit", value: "Unit" },
								{ label: "Feature", value: "Feature" }
							],
							value: "Unit"
						})
					])
				])
			];

		case "Full Resource":
			// Combine all child form groups into one big array:
			return [
				...getResourceForm("API"),
				...getResourceForm("Controller"),
				...getResourceForm("Model"),
				...getResourceForm("Storage"),
				...getResourceForm("Policy"),
				...getResourceForm("Table"),
				...getResourceForm("Migration"),
				...getResourceForm("Unit Test")
			];

		default:
			// Fallback if unknown resourceType is passed
			return [
				Div("No form fields are available for this resource type.")
			];
	}
}
