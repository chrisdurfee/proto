<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * SeedOrganizationPermission
 *
 * Seeds the organization-related permissions and assigns them
 * to both the global Administrator role and the Partner Admin role.
 */
class SeedOrganizationPermission extends Migration
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
     * Seed the database with partner roles.
	 *
	 * @return void
     */
    public function seed(): void
    {
        // 1) create the organization permissions
        $permissions = [
            [
                'name' => 'Create Organizations',
                'slug' => 'organization.create',
                'description' => 'Can create organizations',
                'module' => 'organization',
            ],
            [
                'name' => 'View Organizations',
                'slug' => 'organization.view',
                'description' => 'Can view organizations',
                'module' => 'organization',
            ],
            [
                'name' => 'Edit Organizations',
                'slug' => 'organization.edit',
                'description' => 'Can edit organizations',
                'module' => 'organization',
            ],
            [
                'name' => 'Delete Organizations',
                'slug' => 'organization.delete',
                'description' => 'Can delete organizations',
                'module' => 'organization',
            ],
        ];

        foreach ($permissions as $permission)
		{
            $this->insert('permissions', $permission);
        }

        // 2) fetch the role IDs for 'admin' and 'partner-admin'
        $adminRoleId = $this->first(
            'SELECT id FROM roles WHERE slug = ?',
            ['admin']
        )->id;

        $partnerAdminRoleId = $this->first(
            'SELECT id FROM roles WHERE slug = ?',
            ['partner-admin']
        )->id;

        // 3) assign every new org permission to both roles
        $slugs = array_column($permissions, 'slug');
        foreach ($slugs as $slug)
		{
            $permId = $this->first(
                'SELECT id FROM permissions WHERE slug = ?',
                [$slug]
            )->id;

            $this->insert('permission_roles', [
                'role_id' => $adminRoleId,
                'permission_id' => $permId,
            ]);

            $this->insert('permission_roles', [
                'role_id' => $partnerAdminRoleId,
                'permission_id' => $permId,
            ]);
        }
    }

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{

	}
}
