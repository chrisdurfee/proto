<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

/**
 * DatabaseSeeder
 *
 * Main seeder that calls all other seeders.
 * This is typically the entry point for seeding operations.
 *
 * @package Proto\Database\Seeders
 */
class DatabaseSeeder extends Seeder
{
	/**
	 * Run the database seeders.
	 *
	 * @return void
	 */
	public function run(): void
	{
		// Call individual seeders in order
		$this->callMany([
			RoleSeeder::class,
			UserSeeder::class,
			// Add more seeders here as needed
			// ProductSeeder::class,
		]);
	}
}