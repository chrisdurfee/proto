<?php declare(strict_types=1);
namespace Proto\Tests\Seeders;

use Proto\Database\Seeders\Seeder;

/**
 * TestUserSeeder
 *
 * Seeds test users for testing purposes.
 *
 * @package Proto\Tests\Seeders
 */
class TestUserSeeder extends Seeder
{
	/**
	 * Run the database seeder.
	 *
	 * @return void
	 */
	public function run(): void
	{
		$testUsers = [
			[
				'name' => 'Test User 1',
				'email' => 'test1@example.com',
				'password' => password_hash('testpass', PASSWORD_DEFAULT),
				'status' => 'active',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			],
			[
				'name' => 'Test User 2',
				'email' => 'test2@example.com',
				'password' => password_hash('testpass', PASSWORD_DEFAULT),
				'status' => 'active',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			],
			[
				'name' => 'Inactive Test User',
				'email' => 'inactive@example.com',
				'password' => password_hash('testpass', PASSWORD_DEFAULT),
				'status' => 'inactive',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			]
		];

		$this->insert('users', $testUsers);
	}
}