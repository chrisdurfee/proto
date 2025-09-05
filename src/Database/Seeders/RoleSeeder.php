<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

/**
 * RoleSeeder
 *
 * Seeds the roles table with basic role data.
 *
 * @package Proto\Database\Seeders
 */
class RoleSeeder extends Seeder
{
	/**
	 * Run the database seeder.
	 *
	 * @return void
	 */
	public function run(): void
	{
		// Only seed if the table is empty
		if (!$this->isEmpty('roles'))
		{
			return;
		}

		$roles = [
			[
				'name' => 'Administrator',
				'slug' => 'administrator',
				'description' => 'System administrator with full access',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			],
			[
				'name' => 'User',
				'slug' => 'user',
				'description' => 'Regular user with basic access',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			],
			[
				'name' => 'Moderator',
				'slug' => 'moderator',
				'description' => 'Content moderator with limited admin access',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			]
		];

		$this->insert('roles', $roles);
	}
}