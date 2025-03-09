import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { DocPage } from "../../doc-page.js";

/**
 * CodeBlock
 *
 * Creates a code block with copy-to-clipboard functionality.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const CodeBlock = Atom((props, children) => (
	Pre(
		{
			...props,
			class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
			          rounded-lg border bg-muted whitespace-break-spaces
			          break-all cursor-pointer mt-4 ${props.class}`
		},
		[
			Code(
				{
					class: 'font-mono flex-auto text-sm text-wrap',
					click: () => {
						navigator.clipboard.writeText(children[0].textContent);
						// @ts-ignore
						app.notify({
							title: "Code copied",
							description: "The code has been copied to your clipboard.",
							icon: null
						});
					}
				},
				children
			)
		]
	)
));

/**
 * ModelsPage
 *
 * This page explains how to create and use models in Proto.
 * Models are objects that map to database tables and provide a clean interface
 * for getting and setting data. They define columns, joins, table names, and aliases,
 * and they interact with storage layers to persist data without accessing the database directly.
 *
 * @returns {DocPage}
 */
export const ModelsPage = () =>
	DocPage(
		{
			title: 'Models',
			description: 'Learn how models in Proto map database tables to structured data and provide built-in CRUD functionality.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`A model is an object used to get and set data. Models outline the columns, joins, table name,
					and alias of the underlying database table. They act as data containers that map database records
					to a defined structure. Models use a storage layer (via storage proxies and objects) to interface with
					the database instead of accessing it directly.`
				),
				P({ class: 'text-muted-foreground' },
					`The parent model provides built-in CRUD methods (create, read, update, delete), so child models don't
					need to re-implement these methods.`
				)
			]),

			// Naming and Basic Setup
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming and Basic Setup'),
				P({ class: 'text-muted-foreground' },
					`Models should be named in singular form, matching the corresponding database table name.
					For example, a model for the "example" table should be named \`Example\`.`
				),
				CodeBlock(
`<?php
namespace Common\\Models;
use Common\\Storage\\ExampleStorage;

class Example extends Model
{
    protected static $tableName = 'example';
    protected static $alias = 'e';

    protected static $fields = [
        'id',
        'createdAt', // use camelCase for snake_case columns
        'updatedAt',

        // Raw SQL column with alias:
        [['(COUNT(*)'], 'total'],

        // Alias column:
        ['name', 'client'],
        'label',
        'description'
    ];
}`
				),
				P({ class: 'text-muted-foreground' },
					`Set <code>$tableName</code> to the exact name of the database table and <code>$alias</code> to a short alias for query building.`
				)
			]),

			// Model Pass-Through
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Model Pass-Through'),
				P({ class: 'text-muted-foreground' },
					`To reduce boilerplate, models can pass through undeclared methods directly to the storage layer.
					This means you only need to implement new logic in the resource API and storage class.
					By default, results are wrapped in a model object.`
				),
				P({ class: 'text-muted-foreground' },
					`If you don't want pass-through mapping, set the <code>$passModel</code> property to false.`
				),
				CodeBlock(
`// Disable pass-through mapping:
protected $passModel = false;`
				),
				P({ class: 'text-muted-foreground' },
					`Alternatively, you can bypass the wrapper by calling the method statically on the storage type:`
				),
				CodeBlock(
`// Bypass model pass-through:
$result = static::$storageType::methodName();`
				)
			]),

			// Fields and Blacklisting
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Fields and Blacklisting'),
				P({ class: 'text-muted-foreground' },
					`Models define the fields that map to database columns. Field names should use camelCase,
					and the model will only set values for declared fields or joins.`
				),
				P({ class: 'text-muted-foreground' },
					`To prevent sensitive data from being output, you can define a fields blacklist. For example:`
				),
				CodeBlock(
`protected static $fieldsBlacklist = [
    'password'
];`
				),
				P({ class: 'text-muted-foreground' },
					`The model ID key defaults to "id" unless otherwise specified:`
				),
				CodeBlock(
`protected static $idKeyName = 'id';`
				)
			]),

			// Field Formatting
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Field Formatting'),
				P({ class: 'text-muted-foreground' },
					`Models can augment or format fields before inserting or after retrieving data.
					The <code>augment</code> method allows you to modify data before it's stored,
					while the <code>format</code> method converts data before it's returned via the API.`
				),
				CodeBlock(
`protected static function augment($data = null)
{
    if (!$data) {
        return $data;
    }
    // Example: Clean up phone numbers.
    $data->phoneNumber = Strings::cleanPhone($data->phoneNumber);
    return $data;
}

protected static function format(?object $data): ?object
{
    if (!$data) {
        return $data;
    }
    // Example: Mask sensitive fields.
    $data->ssn = Strings::mask($data->ssn, 4);
    return $data;
}`
				)
			]),

			// Model Joins and Join Builder
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Model Joins'),
				P({ class: 'text-muted-foreground' },
					`Models can join data from other tables. Override the <code>joins</code> method to define joins.
					The joins method receives a JoinBuilder object that can be used to build one-to-one, one-to-many,
					or many-to-many relationships.`
				),
				CodeBlock(
`protected static function joins($builder)
{
    // Example: Join to the Role table.
    Role::one($builder)
        ->on(['id', 'userId'])
        ->fields('role');

    // Another join using an inner join.
    Role::one($builder, 'inner')
        ->fields('role');

    // Raw SQL join example:
    $builder->left('role', 'r')
        ->on(['id', 'userId'])
        ->fields('role');
}`
				)
			]),

			// Storage Type and Proxy
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Storage Type and Proxy'),
				P({ class: 'text-muted-foreground' },
					`Each model uses a default storage layer to perform CRUD operations.
					If custom actions are needed, create a custom storage class and override the <code>$storageType</code> property.`
				),
				CodeBlock(
`// Specify a custom storage class if needed:
protected static $storageType = ExampleStorage::class;`
				),
				P({ class: 'text-muted-foreground' },
					`When a model is instantiated, it sets up a storage object accessible via the <code>storage</code> property.
					This proxy automatically passes storage method calls to the events system.`
				),
				CodeBlock(
`// Example of accessing storage directly:
$result = $this->storage->get(1);`
				)
			]),

			// Getting, Setting, and Persistence
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Getting, Setting, and Persistence'),
				P({ class: 'text-muted-foreground' },
					`Models use PHP's magic methods (__get and __set) to simplify data access.
					For example, you can get and set properties directly on a model instance.`
				),
				CodeBlock(
`$model = new Example();

// Get a property
$value = $model->key;

// Set a property
$model->key = $value;

// Batch set properties
$model->set((object)[
    'key'  => $value,
    'name' => $name
]);`
				),
				P({ class: 'text-muted-foreground' },
					`The model also provides built-in CRUD methods. For instance, to add a new row:`
				),
				CodeBlock(
`$model = new Example();
$model->name = 'save';
$model->add();

// Or use static shortcut methods:
$result = Example::create((object)[
    'name' => 'save'
]);`
				)
			]),

			// JSON Encoding
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'JSON Encoding'),
				P({ class: 'text-muted-foreground' },
					`When encoding a model as JSON, only public, non-blacklisted fields are included.
					This ensures that sensitive data is not inadvertently exposed.`
				)
			])
		]
	);

export default ModelsPage;