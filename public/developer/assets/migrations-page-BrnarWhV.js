import{a2 as a,a0 as s,G as e,s as n,Z as d,a3 as t,u as l,N as c,o as m}from"./index-DD5ZlTX4.js";import{D as u}from"./doc-page-DFkqm2Y0.js";import"./sidebar-menu-page-D_2zNFuZ-DJNCgvAz.js";const i=n((o,r)=>l({...o,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${o.class}`},[c({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(r[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:m.clipboard.checked})}},r)])),h=()=>u({title:"Migrations",description:"Learn how to create and manage migrations to update or revert database changes in Proto."},[a({class:"space-y-4"},[s({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},`Migrations are classes used to update or revert database changes.
					They allow database changes to be tracked in Git and support operations such as
					creating tables, altering columns, renaming or dropping columns, adding indices, creating views, adding foreign keys, and more.`)]),a({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Naming"),e({class:"text-muted-foreground"},'The name of a migration should always be singular and followed by "Migration". For example:'),i(`<?php
use Proto\\Database\\Migrations\\Migration;

class ExampleMigration extends Migration
{
    protected string $connection = 'default';

    public function up(): void
    {
        // Code to update the database.
    }

	public function seed(): void
    {
        // Code to ssed the database.
    }

    public function down(): void
    {
        // Code to revert the changes.
    }
}`),e({class:"text-muted-foreground"},"The $connection property should match the database handle name defined in your common/Config .env file.")]),a({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Schema Builder"),e({class:"text-muted-foreground"},`Proto includes a schema query builder to simplify common database tasks.
					This fluent interface allows you to chain methods for creating and altering tables.
					Available methods include:`),d({class:"list-disc pl-6 space-y-1 text-muted-foreground"},[t("engine()"),t("myisam()"),t("create()"),t("id()"),t("createdAt()"),t("updatedAt()"),t("deletedAt()"),t("removeField()"),t("index()"),t("foreign()")]),e({class:"text-muted-foreground"},"For example, in a migration method you might write:"),i(`$this->create('test_table', function($table) {
    $table->id();
    $table->createdAt();
    $table->updatedAt();
    $table->int('message_id', 20);
    $table->varchar('subject', 160);
    $table->text('message')->nullable();
    $table->datetime('read_at');
    $table->datetime('forwarded_at');

    // Indices
    $table->index('email_read')->fields('id', 'read_at');
    $table->index('created')->fields('created_at');

    // Foreign keys
    // $table->foreign('message_id')->references('id')->on('messages');
});`)]),a({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Up Method"),e({class:"text-muted-foreground"},`The up() method should include all the commands to update the database.
					For example:`),i(`public function up(): void
{
    // Create a table.
    $this->create('test_table', function($table) {
        $table->id();
        $table->createdAt();
        $table->updatedAt();
        $table->int('message_id', 20);
        $table->varchar('subject', 160);
        $table->text('message')->nullable();
        $table->datetime('read_at');
        $table->datetime('forwarded_at');
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
}`)]),a({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Down Method"),e({class:"text-muted-foreground"},`The down() method should revert all changes made in the up() method.
					For example:`),i(`public function down(): void
{
    // Revert changes to the table.
    $this->alter('test_table', function($table) {
        $table->drop('status');
        $table->alter('subject')->varchar(160);
        $table->add('read_at')->datetime();
    });

    // Drop a view.
    $this->dropView('vw_test');

    // Drop the table.
    $this->drop('test_table');
}`)]),a({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Seeding Data"),e({class:"text-muted-foreground"},"You can also seed data in the up() method. For example:"),i(`/**
 * Seed the database with roles and permissions.
 *
 * @return void
 */
public function seed(): void
{
	// Define basic roles
	$roles = [
		[
			'name' => 'Administrator',
			'slug' => 'admin',
			'description' => 'Full system access'
		]
	];

	// Insert roles
	foreach ($roles as $role)
	{
		$this->insert('roles', $role);
	}

	// Define basic permissions
	$permissions = [
		// User management permissions
		[
			'name' => 'View Users',
			'slug' => 'users.view',
			'description' => 'Can view users',
			'module' => 'user',
		]
	];

	// Insert permissions
	foreach ($permissions as $permission)
	{
		$this->insert('permissions', $permission);
	}

	// Get the role IDs
	$managerRoleId = $this->first('SELECT id FROM roles WHERE slug = ?', ['manager'])->id;
	$editorRoleId = $this->first('SELECT id FROM roles WHERE slug = ?', ['editor'])->id;

	// Assign all permissions to the manager role
	$allPermissions = $this->fetch('SELECT id FROM permissions');
	foreach ($allPermissions as $permission)
	{
		$this->insert('role_permissions', [
			'role_id' => $managerRoleId,
			'permission_id' => $permission->id,
		]);
	}

	// Assign only view and edit permissions to the editor role
	$editorPermissions = $this->fetch('SELECT id FROM permissions WHERE slug LIKE "%.view" OR slug LIKE "%.edit"');
	foreach ($editorPermissions as $permission)
	{
		$this->insert('role_permissions', [
			'role_id' => $editorRoleId,
			'permission_id' => $permission->id,
		]);
	}
}`)]),a({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Creating a Migration"),e({class:"text-muted-foreground"},"Migrations can be generated using the built-in generator. For example:"),i(`$generator = new Proto\\Generators\\Generator();
$generator->createMigration((object)[
    'className' => 'Example'
]);`)]),a({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Migration Guide"),e({class:"text-muted-foreground"},"The migration guide can run or revert migrations. For example:"),i(`use Proto\\Database\\Migrations\\Guide;

$handler = new Guide();
// Run migrations.
$handler->run();

// Revert migrations.
$handler->revert();`)])]);export{h as MigrationsPage,h as default};
