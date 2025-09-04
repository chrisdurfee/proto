<?php declare(strict_types=1);
namespace Proto\Tests;

/**
 * SimpleFaker
 *
 * Provides simple fake data generation for testing.
 *
 * @package Proto\Tests
 */
class SimpleFaker
{
	/**
	 * @var array $firstNames
	 */
	protected array $firstNames = [
		'John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana', 'Frank', 'Grace',
		'Henry', 'Ivy', 'Jack', 'Kate', 'Leo', 'Mia', 'Noah', 'Olivia'
	];

	/**
	 * @var array $lastNames
	 */
	protected array $lastNames = [
		'Smith', 'Johnson', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor',
		'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia'
	];

	/**
	 * @var array $domains
	 */
	protected array $domains = [
		'example.com', 'test.com', 'sample.org', 'demo.net', 'fake.co'
	];

	/**
	 * Generates a fake name.
	 *
	 * @return string
	 */
	public function name(): string
	{
		return $this->firstName() . ' ' . $this->lastName();
	}

	/**
	 * Generates a fake first name.
	 *
	 * @return string
	 */
	public function firstName(): string
	{
		return $this->arrayRandom($this->firstNames);
	}

	/**
	 * Generates a fake last name.
	 *
	 * @return string
	 */
	public function lastName(): string
	{
		return $this->arrayRandom($this->lastNames);
	}

	/**
	 * Generates a fake email address.
	 *
	 * @return string
	 */
	public function email(): string
	{
		$username = strtolower($this->firstName() . '.' . $this->lastName());
		$domain = $this->arrayRandom($this->domains);
		return $username . '@' . $domain;
	}

	/**
	 * Generates a fake phone number.
	 *
	 * @return string
	 */
	public function phoneNumber(): string
	{
		return sprintf('(%03d) %03d-%04d',
			rand(200, 999),
			rand(200, 999),
			rand(1000, 9999)
		);
	}

	/**
	 * Generates a fake address.
	 *
	 * @return string
	 */
	public function address(): string
	{
		$streetNumber = rand(1, 9999);
		$streetNames = ['Main St', 'Oak Ave', 'Pine Rd', 'Elm Dr', 'Cedar Ln'];
		$streetName = $this->arrayRandom($streetNames);

		return "{$streetNumber} {$streetName}";
	}

	/**
	 * Generates a fake city.
	 *
	 * @return string
	 */
	public function city(): string
	{
		$cities = ['Springfield', 'Franklin', 'Georgetown', 'Madison', 'Oakland', 'Bristol', 'Salem', 'Fairview'];
		return $this->arrayRandom($cities);
	}

	/**
	 * Generates a fake text of specified word count.
	 *
	 * @param int $wordCount
	 * @return string
	 */
	public function text(int $wordCount = 10): string
	{
		$words = [
			'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
			'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et',
			'dolore', 'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis'
		];

		$result = [];
		for ($i = 0; $i < $wordCount; $i++) {
			$result[] = $this->arrayRandom($words);
		}

		return implode(' ', $result);
	}

	/**
	 * Generates a random integer.
	 *
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	public function numberBetween(int $min = 0, int $max = 100): int
	{
		return rand($min, $max);
	}

	/**
	 * Generates a random float.
	 *
	 * @param float $min
	 * @param float $max
	 * @param int $decimals
	 * @return float
	 */
	public function floatBetween(float $min = 0.0, float $max = 100.0, int $decimals = 2): float
	{
		$random = ($min + ($max - $min) * (mt_rand() / mt_getrandmax()));
		return round($random, $decimals);
	}

	/**
	 * Generates a random boolean.
	 *
	 * @param int $chanceOfTrue Percentage chance of returning true (0-100)
	 * @return bool
	 */
	public function boolean(int $chanceOfTrue = 50): bool
	{
		return rand(1, 100) <= $chanceOfTrue;
	}

	/**
	 * Generates a random date between two dates.
	 *
	 * @param string $startDate
	 * @param string $endDate
	 * @return string
	 */
	public function dateTimeBetween(string $startDate = '-1 year', string $endDate = 'now'): string
	{
		$startTimestamp = strtotime($startDate);
		$endTimestamp = strtotime($endDate);

		$randomTimestamp = rand($startTimestamp, $endTimestamp);
		return date('Y-m-d H:i:s', $randomTimestamp);
	}

	/**
	 * Generates a random UUID.
	 *
	 * @return string
	 */
	public function uuid(): string
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	/**
	 * Selects a random element from an array.
	 *
	 * @param array $array
	 * @return mixed
	 */
	protected function arrayRandom(array $array): mixed
	{
		return $array[array_rand($array)];
	}
}