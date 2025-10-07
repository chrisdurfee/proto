<?php declare(strict_types=1);
namespace Proto\Tests\Examples;

use Proto\Models\Factory;

/**
 * UserFactory (Example)
 *
 * Factory for generating User model instances with fake data.
 *
 * @package Proto\Tests\Examples
 */
class UserFactory extends Factory
{
	/**
	 * Get the model class.
	 *
	 * @return string
	 */
	protected function model(): string
	{
		return User::class;
	}

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition(): array
	{
		$faker = $this->faker();

		return [
			'name' => $faker->name(),
			'email' => $faker->email(),
			'password' => password_hash('password', PASSWORD_BCRYPT),
			'status' => 'active',
			'role' => 'user',
			'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
			'updated_at' => $faker->dateTimeBetween('-1 year', 'now')
		];
	}

	/**
	 * State: Admin user
	 *
	 * @return array
	 */
	public function stateAdmin(): array
	{
		return [
			'role' => 'admin',
			'status' => 'active'
		];
	}

	/**
	 * State: Inactive user
	 *
	 * @return array
	 */
	public function stateInactive(): array
	{
		return [
			'status' => 'inactive'
		];
	}

	/**
	 * State: Suspended user
	 *
	 * @return array
	 */
	public function stateSuspended(): array
	{
		return [
			'status' => 'suspended'
		];
	}

	/**
	 * State: Verified user with email_verified_at timestamp
	 *
	 * @return array
	 */
	public function stateVerified(): array
	{
		return [
			'email_verified_at' => $this->faker()->dateTimeBetween('-1 year', 'now')
		];
	}

	/**
	 * State: User with custom email domain
	 *
	 * @param string $domain
	 * @return array
	 */
	public function stateWithDomain(string $domain): array
	{
		$faker = $this->faker();
		$username = strtolower($faker->firstName() . '.' . $faker->lastName());

		return [
			'email' => $username . '@' . $domain
		];
	}
}
