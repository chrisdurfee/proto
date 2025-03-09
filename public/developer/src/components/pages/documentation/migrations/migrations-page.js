import { Code, H4, Li, P, Pre, Section, Ul } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
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
							icon: Icons.clipboard.checked
						});
					}
				},
				children
			)
		]
	)
));

/**
 * MigrationsPage
 *
 * This page documents Proto's migration system. Migrations are classes that update
 * or revert database changes so that all changes can be tracked in Git. They allow you
 * to create new tables, modify columns, add indices, create views, and more.
 *
 * @returns {DocPage}
 */
export const MigrationsPage = () =>
	DocPage(
		{
			title: 'Migrations',
			description: 'Learn how to create and manage migrations to update or revert database changes in Proto.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`Migrations are classes used to update or revert database changes.
					They allow database changes to be tracked in Git and support operations such as
					creating tables, altering columns, renaming or dropping columns, adding indices, creating views, adding foreign keys, and more.`
				)
			]),

			// Naming
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming'),
				P({ class: 'text-muted-foreground' },
					`The name of a migration should always be singular and followed by "Migration". For example:`
				),
				CodeBlock(
`<?php
use Proto\\Database\\Migrations\\Migration;

class ExampleMigration extends Migration
{
    protected $connection = 'proto';

    public function up()
    {
        // Code to update the database.
    }

    public function down()
    {
        // Code to revert the changes.
    }
}`
				),
				P({ class: 'text-muted-foreground' },
					`The $connection property should match the database handle name defined in your common/Config .env file.`
				)
			]),

			// Schema Builder
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Schema Builder'),
				P({ class: 'text-muted-foreground' },
					`Proto includes a schema query builder to simplify common database tasks.
					This fluent interface allows you to chain methods for creating and altering tables.
					Available methods include:`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("engine()"),
					Li("myisam()"),
					Li("create()"),
					Li("id()"),
					Li("createdAt()"),
					Li("updatedAt()"),
					Li("deletedAt()"),
					Li("removeField()"),
					Li("index()"),
					Li("foreign()")
				]),
				P({ class: 'text-muted-foreground' },
					`For example, in a migration method you might write:`
				),
				CodeBlock(
`$this->create('test_table', function($table) {
    $table->id();
    $table->createdAt();
    $table->updatedAt();
    $table->int('message_id', 20);
    $table->varchar('subject', 160);
    $table->text('message')->null();
    $table->dateTime('read_at');
    $table->dateTime('forwarded_at');

    // Indices
    $table->index('email_read')->fields('id', 'read_at');
    $table->index('created')->fields('created_at');

    // Foreign keys
    // $table->foreign('message_id')->references('id')->on('messages');
});`
				)
			]),

			// Up Method
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Up Method'),
				P({ class: 'text-muted-foreground' },
					`The up() method should include all the commands to update the database.
					For example:`
				),
				CodeBlock(
`public function up()
{
    // Create a table.
    $this->create('test_table', function($table) {
        $table->id();
        $table->createdAt();
        $table->updatedAt();
        $table->int('message_id', 20);
        $table->varchar('subject', 160);
        $table->text('message')->null();
        $table->dateTime('read_at');
        $table->dateTime('forwarded_at');
        $table->index('email_read')->fields('id', 'read_at');
        $table->index('created')->fields('created_at');
    });

    // Create or replace a view using the query builder.
    $this->createView('vw_test')
         ->table('test_table', 't')
         ->select('id', 'created_at')
         ->where('id > 1');

    // Create or replace a view using an SQL string.
    $this->createView('vw_test_query')
         ->query('SELECT * FROM test_table');

    // Alter the table.
    $this->alter('test_table', function($table) {
        $table->add('status')->int(20);
        $table->alter('subject')->varchar(180);
        $table->drop('read_at');
    });
}`
				)
			]),

			// Down Method
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Down Method'),
				P({ class: 'text-muted-foreground' },
					`The down() method should revert all changes made in the up() method.
					For example:`
				),
				CodeBlock(
`public function down()
{
    // Revert changes to the table.
    $this->alter('test_table', function($table) {
        $table->drop('status');
        $table->alter('subject')->varchar(160);
        $table->add('read_at')->dateTime();
    });

    // Drop a view.
    $this->dropView('vw_test');

    // Drop the table.
    $this->drop('test_table');
}`
				)
			]),

			// Creating a Migration
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Creating a Migration'),
				P({ class: 'text-muted-foreground' },
					`Migrations can be generated using the built-in generator. For example:`
				),
				CodeBlock(
`$generator = new Proto\\Generators\\Generator();
$generator->createMigration((object)[
    'className' => 'Example'
]);`
				)
			]),

			// Migration Guide
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Migration Guide'),
				P({ class: 'text-muted-foreground' },
					`The migration guide can run or revert migrations. For example:`
				),
				CodeBlock(
`use Proto\\Database\\Migrations\\Guide;

$handler = new Guide();
// Run migrations.
$handler->run();

// Revert migrations.
$handler->revert();`
				)
			])
		]
	);

export default MigrationsPage;