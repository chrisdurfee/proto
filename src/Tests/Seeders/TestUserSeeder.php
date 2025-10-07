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
		$testUsers = [
			[
				'username' => 'test.user1',
				'first_name' => 'Test',
				'last_name' => 'User 1',
				'email' => 'test1@example.com',
				'password' => password_hash('testpass', PASSWORD_DEFAULT),
				'status' => 'online'
			],
			[
				'username' => 'test.user2',
				'first_name' => 'Test',
				'last_name' => 'User 2',
				'email' => 'test2@example.com',
				'password' => password_hash('testpass', PASSWORD_DEFAULT),
				'status' => 'online'
			],
			[
				'username' => 'inactive.user',
				'first_name' => 'Inactive',
				'last_name' => 'Test User',
				'email' => 'inactive@example.com',
				'password' => password_hash('testpass', PASSWORD_DEFAULT),
				'status' => 'offline'
			]
		];

		$this->insert('users', $testUsers);
	}
}