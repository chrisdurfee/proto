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
            'firstName' => $faker->firstName(),
			'lastName' => $faker->lastName(),
			'username' => $faker->email(),
			'password' => password_hash('password', PASSWORD_BCRYPT),
            'email' => $faker->email(),
			'status' => 'offline'
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
			'status' => 'online'
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
			'status' => 'offline'
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
			'status' => 'busy'
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
