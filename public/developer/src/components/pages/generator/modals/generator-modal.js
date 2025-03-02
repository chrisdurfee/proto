import { Div, UseParent } from "@base-framework/atoms";
import { Checkbox, Fieldset, Input, Select, Textarea } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Modal } from "@base-framework/ui/molecules";
import { GeneratorModel } from "../models/generator-model.js";
import { TableModel } from "../models/table-model.js";

/**
 * Formats the specified resource type by replacing spaces with hyphens and converting to lowercase.
 *
 * @param {string} type
 * @returns {string}
 */
const formatType = (type) =>
{
	if (!type)
	{
		return '';
	}

	return type.replace(' ', '-').toLowerCase();
};

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
		data: new GeneratorModel({
			type: formatType(resourceType),
		}),
		title: `Add ${resourceType}`,
		icon: Icons.document.add,
		description: `Let's add a new ${resourceType}.`,
		size: 'md',
		type: 'right',
		onSubmit: ({ data }) =>
		{
			data.xhr.add('', (response) =>
			{
				if (!response || response.success === false)
				{
					app.notify({
						type: "destructive",
						title: "Error",
						description: "An error occurred while adding the resource.",
						icon: Icons.shield
					});
					return;
				}

				app.notify({
					type: "success",
					title: `${resourceType} Added`,
					description: `The ${resourceType} has been added.`,
					icon: Icons.check
				});
			});
		}
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, getResourceForm(resourceType))
		])
	]).open()
);

/**
 * Sets the default fields for the specified columns.
 *
 * @param {Array} columns - The columns to set fields from.
 * @returns {string} - A formatted string of fields.
 */
const setDefaultFields = (columns) =>
{
	let fields = '';
	for	(var i = 0, length = columns.length; i < length; i++)
	{
		fields += columns[i];
		if (i !== length - 1)
		{
			fields += ":\n";
		}
	}
	return fields;
};

/**
 * getResourceForm
 *
 * Returns an array of fieldsets for each resource type.
 *
 * @param {string} type
 * @returns {Array}
 */
function getResourceForm(type, fullResource = false)
{
	switch (type)
	{
		case "API":
			return [
				Fieldset({ legend: "API Settings" }, [
					fullResource === false && new FormField({ name: "className", label: "Class Name", description: "The class name for the API." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true, bind: "api.className" })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the API." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub", bind: "api.namespace" })
					])
				])
			];

		case "Controller":
			return [
				Fieldset({ legend: "Controller Settings" }, [
					fullResource === false && new FormField({ name: "className", label: "Class Name", description: "The class name for the controller." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true, bind: "controller.className" })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the controller." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub", bind: "controller.namespace" })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this controller extends." }, [
						Input({ type: "text", value: "Controller", required: true, bind: "controller.extends" })
					])
				])
			];

		case "Model":
			return [
				UseParent(({ data }) =>
				{
					const model = new TableModel();
					data.link(model, 'connection', 'storage.connection');
					data.link(model, 'tableName', 'model.tableName');

					const getColumns = () =>
					{
						model.xhr.getColumns('', (response) =>
						{
							if (!response || response.length < 1)
							{
								return false;
							}

							data.set('model.fields', setDefaultFields(response));
						});
					};

					return Fieldset({ legend: "Model Settings" }, [
						new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace." }, [
							Input({ type: "text", placeholder: "e.g. ExampleSub", bind: "namespace" })
						]),
						new FormField({ name: "connection", label: "Connection", description: "Database connection name." }, [
							Input({ type: "text", placeholder: "e.g. default", bind: "storage.connection" })
						]),
						new FormField({ name: "className", label: "Class Name", description: "The class name for the model." }, [
							Input({ type: "text", placeholder: "e.g. ModelName", required: true, bind: "model.className" })
						]),
						new FormField({ name: "tableName", label: "Table Name", description: "The database table name." }, [
							Input({ type: "text", placeholder: "e.g. table_name", required: true, bind: "model.tableName", blur: () => getColumns() })
						]),
						new FormField({ name: "alias", label: "Alias", description: "An alias used in queries.", required: true }, [
							Input({ type: "text", placeholder: "e.g. a", bind: "model.alias" })
						]),
						new FormField({ name: "fields", label: "Fields", description: "Define fields for the model." }, [
							Textarea({ placeholder: "e.g.\nid:\ncreatedAt:\nupdatedAt:", rows: 4, required: true, bind: "model.fields" })
						]),
						new FormField({ name: "joins", label: "Joins", description: "Define joins for the model." }, [
							Textarea({ placeholder: "e.g.\n$builder->left('test_table', 't')->on('id', 'client_id');", rows: 4, bind: "model.joins" })
						]),
						new FormField({ name: "extends", label: "Extends", description: "Which class this model extends." }, [
							Input({ type: "text", value: "Model", required: true, bind: "model.extends" })
						]),
						fullResource === true && new FormField({ name: "storage", label: "Storage", description: "Whether to attach a storage layer." }, [
							new Checkbox({ checked: false, bind: "model.storage" })
						])
					]);
				})
			];

		case "Storage":
			return [
				Fieldset({ legend: "Storage Settings" }, [
					fullResource === false && new FormField({ name: "className", label: "Class Name", description: "The class name for storage." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true, bind: "storage.className" })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for storage." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub", bind: "storage.namespace" })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this storage extends." }, [
						Input({ type: "text", value: "Storage", required: true, bind: "storage.extends" })
					]),
					new FormField({ name: "connection", label: "Connection", description: "The database/storage connection name." }, [
						Input({ type: "text", placeholder: "e.g. default", bind: "storage.connection" })
					])
				])
			];

		case "Policy":
			return [
				Fieldset({ legend: "Policy Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The class name for the policy." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true, bind: "policy.className" })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the policy." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub", bind: "policy.namespace" })
					]),
					new FormField({ name: "extends", label: "Extends", description: "Which class this policy extends." }, [
						Input({ type: "text", value: "Policy", required: true, bind: "policy.extends" })
					])
				])
			];

		case "Table":
			return [
				Fieldset({ legend: "Table Settings" }, [
					new FormField({ name: "connection", label: "Connection", description: "The database connection name." }, [
						Input({ type: "text", placeholder: "e.g. dashr", bind: "table.connection" })
					]),
					new FormField({ name: "tableName", label: "Table Name", description: "The table name." }, [
						Input({ type: "text", placeholder: "e.g. table_name", required: true, bind: "table.tableName" })
					]),
					new FormField({ name: "callback", label: "Call Back", description: "The table creation or modification logic." }, [
						Textarea({ placeholder: `e.g. // fields
$table->id();
$table->createdAt();
$table->updatedAt();
$table->int('message_id', 20);
$table->varchar('subject', 160);
$table->text('message')->null();
$table->dateTime('read_at');
$table->dateTime('forwarded_at');

// indices
$table->index('email_read')->fields('id', 'read_at');
$table->index('created')->fields('created_at');

// foreign keys
$table->foreign('message_id')->references('id')->on('messages');`, rows: 6, bind: "table.callBack" })
					])
				])
			];

		case "Migration":
			return [
				Fieldset({ legend: "Migration Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The migration class name." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true, bind: "migration.className" })
					])
				])
			];

		case "Unit Test":
			return [
				Fieldset({ legend: "Unit Test Settings" }, [
					new FormField({ name: "className", label: "Class Name", description: "The class name for the test." }, [
						Input({ type: "text", placeholder: "e.g. Example", required: true, bind: "test.className" })
					]),
					new FormField({ name: "namespace", label: "Namespace", description: "Optional namespace for the test." }, [
						Input({ type: "text", placeholder: "e.g. ExampleSub", bind: "test.namespace" })
					]),
					new FormField({ name: "type", label: "Type", description: "The type of test." }, [
						Select({
							options: [
								{ label: "Unit", value: "Unit" },
								{ label: "Feature", value: "Feature" }
							],
							value: "Unit",
							bind: "test.type"
						})
					])
				])
			];

		case "Full Resource":
			return [
				...getResourceForm("Model", true),
				...getResourceForm("API", true),
				...getResourceForm("Controller", true),
				...getResourceForm("Storage", true)
			];

		default:
			return [
				Div("No form fields are available for this resource type.")
			];
	}
}
