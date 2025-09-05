<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

/**
 * UserSeeder
 *
 * Seeds the users table with test data.
 *
 * @package Proto\Database\Seeders
 */
class UserSeeder extends Seeder
{
	/**
	 * Run the database seeder.
	 *
	 * @return void
	 */
	public function run(): void
	{
		// Only seed if the table is empty
		if (!$this->isEmpty('users'))
		{
			return;
		}

		$users = [
			[
				'display_name' => 'John Doe',
				'first_name' => 'John',
				'last_name' => 'Doe',
				'email' => 'john@example.com',
				'password' => password_hash('password123', PASSWORD_DEFAULT),
				'enabled' => '1',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			],
			[
				'display_name' => 'Jane Smith',
				'first_name' => 'Jane',
				'last_name' => 'Smith',
				'email' => 'jane@example.com',
				'password' => password_hash('password123', PASSWORD_DEFAULT),
				'enabled' => '1',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			],
			[
				'display_name' => 'Bob Johnson',
				'first_name' => 'Bob',
				'last_name' => 'Johnson',
				'email' => 'bob@example.com',
				'password' => password_hash('password123', PASSWORD_DEFAULT),
				'enabled' => '0',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			],
			[
				'display_name' => 'Alice Wilson',
				'first_name' => 'Alice',
				'last_name' => 'Wilson',
				'email' => 'alice@example.com',
				'password' => password_hash('password123', PASSWORD_DEFAULT),
				'enabled' => '1',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			]
		];

		$this->insert('users', $users);
	}
}