<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * RolesPermissionsSeed
 *
 * This migration seeds the database with initial roles and permissions.
 * @suppresswarnings PHP6609
 */
class RolesPermissionsSeed extends Migration
{
	/**
	 * @var string $connection
	 */
	protected string $connection = 'default';

	/**
	 * Runs the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{

	}

	/**
	 * Rolls back the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{

	}

	/**
	 * Seed the database with roles and permissions.
	 *
	 * @return void
	 */
	public function seed(): void
	{
		$roles = [
			[
				'name' => 'Guest',
				'slug' => 'guest',
				'description' => 'Limited access for guests'
			]
		];

		foreach ($roles as $role)
		{
			$this->insert('roles', $role);
		}

		$permissions = [
			[
				'name' => 'View Content',
				'slug' => 'content.view',
				'description' => 'Can view content',
				'module' => 'content'
			],
			[
				'name' => 'Create Content',
				'slug' => 'content.create',
				'description' => 'Can create content',
				'module' => 'content'
			],
			[
				'name' => 'Edit Content',
				'slug' => 'content.edit',
				'description' => 'Can edit content',
				'module' => 'content'
			],
			[
				'name' => 'Delete Content',
				'slug' => 'content.delete',
				'description' => 'Can delete content',
				'module' => 'content'
			],
			[
				'name' => 'Publish Content',
				'slug' => 'content.publish',
				'description' => 'Can publish content',
				'module' => 'content'
			]
		];

		foreach ($permissions as $permission)
		{
			$this->insert('permissions', $permission);
		}

		// Assign permissions to roles
		$adminRoleId = $this->first('SELECT id FROM roles WHERE slug = ?', ['admin'])->id;
		$allPermissions = $this->fetch('SELECT id FROM permissions');

		foreach ($allPermissions as $permission)
		{
			$this->insert('role_permissions', [
				'role_id' => $adminRoleId,
				'permission_id' => $permission->id
			]);
		}

		$managerRoleId = $this->first('SELECT id FROM roles WHERE slug = ?', ['manager'])->id;

		foreach ($allPermissions as $permission)
		{
			$this->insert('role_permissions', [
				'role_id' => $managerRoleId,
				'permission_id' => $permission->id
			]);
		}

		$editorRoleId = $this->first('SELECT id FROM roles WHERE slug = ?', ['editor'])->id;
		$editorPermissions = $this->fetch('SELECT id FROM permissions WHERE slug LIKE "%.view" OR slug LIKE "%.edit"');

		foreach ($editorPermissions as $permission)
		{
			$this->insert('role_permissions', [
				'role_id' => $editorRoleId,
				'permission_id' => $permission->id
			]);
		}
	}
}