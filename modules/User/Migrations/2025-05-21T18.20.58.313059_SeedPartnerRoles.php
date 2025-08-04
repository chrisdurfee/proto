<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * SeedPartnerRoles
 *
 */
class SeedPartnerRoles extends Migration
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
     */
    public function seed(): void
    {
        $roles = [
            [
                'name' => 'Partner Admin',
                'slug' => 'partner-admin',
                'description' => 'Full access to a partner organization',
            ],
            [
                'name' => 'Partner Manager',
                'slug' => 'partner-manager',
                'description' => 'Manage partner organization resources',
            ],
            [
                'name' => 'Partner Editor',
                'slug' => 'partner-editor',
                'description' => 'Edit partner organization content',
            ],
            [
                'name' => 'Partner User',
                'slug' => 'partner-user',
                'description' => 'Basic access for partner organization users',
            ],
        ];

        foreach ($roles as $role)
		{
            $this->insert('roles', $role);
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