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
	 * Connection key.
	 * @var string
	 */
	protected string $connection = 'testing';

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
				'resource' => 'global'
			],
			[
				'name' => 'User',
				'slug' => 'user',
				'description' => 'Regular user with basic access',
				'resource' => 'global'
			],
			[
				'name' => 'Moderator',
				'slug' => 'moderator',
				'description' => 'Content moderator with limited admin access',
				'resource' => 'global'
			]
		];

		$this->insert('roles', $roles);
	}
}