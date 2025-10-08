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
	 * @var array $streetNames
	 */
	protected array $streetNames = [
		'Main St', 'Oak Ave', 'Pine Rd', 'Elm Dr', 'Cedar Ln', 'Maple St',
		'Washington Blvd', 'Park Ave', 'Lake Dr', 'River Rd', 'Hill St',
		'Forest Ln', 'Valley View', 'Sunset Blvd', 'Broadway', 'First Ave'
	];

	/**
	 * @var array $cities
	 */
	protected array $cities = [
		'Springfield', 'Franklin', 'Georgetown', 'Madison', 'Oakland',
		'Bristol', 'Salem', 'Fairview', 'Clinton', 'Arlington',
		'Manchester', 'Oxford', 'Cambridge', 'Portland', 'Austin'
	];

	/**
	 * @var array $states
	 */
	protected array $states = [
		'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado',
		'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho',
		'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
		'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
		'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada',
		'New Hampshire', 'New Jersey', 'New Mexico', 'New York',
		'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon',
		'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota',
		'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington',
		'West Virginia', 'Wisconsin', 'Wyoming'
	];

	/**
	 * @var array $stateAbbr
	 */
	protected array $stateAbbr = [
		'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
		'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
		'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
		'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
		'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
	];

	/**
	 * @var array $countries
	 */
	protected array $countries = [
		'United States', 'Canada', 'United Kingdom', 'Australia', 'Germany',
		'France', 'Italy', 'Spain', 'Japan', 'China', 'Brazil', 'Mexico',
		'India', 'Russia', 'South Korea', 'Netherlands', 'Sweden', 'Norway'
	];

	/**
	 * @var array $companySuffixes
	 */
	protected array $companySuffixes = [
		'Inc', 'LLC', 'Corp', 'Group', 'Ltd', 'Co', 'Associates', 'Partners'
	];

	/**
	 * @var array $loremWords
	 */
	protected array $loremWords = [
		'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
		'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et',
		'dolore', 'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis',
		'nostrud', 'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex',
		'ea', 'commodo', 'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit',
		'voluptate', 'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur'
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
	 * Generates a fake username.
	 *
	 * @return string
	 */
	public function username(): string
	{
		return strtolower($this->firstName() . $this->numberBetween(1, 999));
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
	 * Generates a safe email address (always from example.com).
	 *
	 * @return string
	 */
	public function safeEmail(): string
	{
		$username = strtolower($this->firstName() . '.' . $this->lastName());
		return $username . '@example.com';
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
	 * Generates a fake street address.
	 *
	 * @return string
	 */
	public function streetAddress(): string
	{
		$streetNumber = rand(1, 9999);
		$streetName = $this->arrayRandom($this->streetNames);
		return "{$streetNumber} {$streetName}";
	}

	/**
	 * Generates a fake address.
	 *
	 * @return string
	 */
	public function address(): string
	{
		return $this->streetAddress() . ', ' . $this->city() . ', ' . $this->stateAbbr() . ' ' . $this->postcode();
	}

	/**
	 * Generates a fake city.
	 *
	 * @return string
	 */
	public function city(): string
	{
		return $this->arrayRandom($this->cities);
	}

	/**
	 * Generates a fake state name.
	 *
	 * @return string
	 */
	public function state(): string
	{
		return $this->arrayRandom($this->states);
	}

	/**
	 * Generates a fake state abbreviation.
	 *
	 * @return string
	 */
	public function stateAbbr(): string
	{
		return $this->arrayRandom($this->stateAbbr);
	}

	/**
	 * Generates a fake country name.
	 *
	 * @return string
	 */
	public function country(): string
	{
		return $this->arrayRandom($this->countries);
	}

	/**
	 * Generates a fake postcode/zip code.
	 *
	 * @return string
	 */
	public function postcode(): string
	{
		return sprintf('%05d', rand(10000, 99999));
	}

	/**
	 * Alias for postcode.
	 *
	 * @return string
	 */
	public function zipCode(): string
	{
		return $this->postcode();
	}

	/**
	 * Generates a fake company name.
	 *
	 * @return string
	 */
	public function company(): string
	{
		$suffix = $this->arrayRandom($this->companySuffixes);
		return $this->lastName() . ' ' . $suffix;
	}

	/**
	 * Generates a fake company email.
	 *
	 * @return string
	 */
	public function companyEmail(): string
	{
		$username = strtolower($this->firstName() . '.' . $this->lastName());
		$company = strtolower(str_replace(' ', '', $this->lastName()));
		return $username . '@' . $company . '.com';
	}

	/**
	 * Generates a fake job title.
	 *
	 * @return string
	 */
	public function jobTitle(): string
	{
		$titles = [
			'Software Engineer', 'Product Manager', 'Designer', 'Data Analyst',
			'Marketing Manager', 'Sales Representative', 'Customer Support',
			'Project Manager', 'Business Analyst', 'Developer', 'Consultant'
		];
		return $this->arrayRandom($titles);
	}

	/**
	 * Generates a random word.
	 *
	 * @return string
	 */
	public function word(): string
	{
		return $this->arrayRandom($this->loremWords);
	}

	/**
	 * Generates multiple random words.
	 *
	 * @param int $count
	 * @param bool $asText
	 * @return string|array
	 */
	public function words(int $count = 3, bool $asText = false): string|array
	{
		$result = [];
		for ($i = 0; $i < $count; $i++)
		{
			$result[] = $this->arrayRandom($this->loremWords);
		}
		return $asText ? implode(' ', $result) : $result;
	}

	/**
	 * Generates a fake sentence.
	 *
	 * @param int $wordCount
	 * @return string
	 */
	public function sentence(int $wordCount = 6): string
	{
		$words = [];
		for ($i = 0; $i < $wordCount; $i++)
		{
			$words[] = $this->arrayRandom($this->loremWords);
		}
		$sentence = implode(' ', $words);
		return ucfirst($sentence) . '.';
	}

	/**
	 * Generates multiple fake sentences.
	 *
	 * @param int $count
	 * @param bool $asText
	 * @return string|array
	 */
	public function sentences(int $count = 3, bool $asText = false): string|array
	{
		$result = [];
		for ($i = 0; $i < $count; $i++)
		{
			$result[] = $this->sentence($this->numberBetween(4, 10));
		}
		return $asText ? implode(' ', $result) : $result;
	}

	/**
	 * Generates a fake paragraph.
	 *
	 * @param int $sentenceCount
	 * @return string
	 */
	public function paragraph(int $sentenceCount = 3): string
	{
		$sentences = [];
		for ($i = 0; $i < $sentenceCount; $i++)
		{
			$sentences[] = $this->sentence($this->numberBetween(4, 10));
		}
		return implode(' ', $sentences);
	}

	/**
	 * Generates multiple fake paragraphs.
	 *
	 * @param int $count
	 * @param bool $asText
	 * @return string|array
	 */
	public function paragraphs(int $count = 3, bool $asText = false): string|array
	{
		$result = [];
		for ($i = 0; $i < $count; $i++)
		{
			$result[] = $this->paragraph($this->numberBetween(3, 7));
		}
		return $asText ? implode("\n\n", $result) : $result;
	}

	/**
	 * Generates a fake text of specified word count.
	 *
	 * @param int $wordCount
	 * @return string
	 */
	public function text(int $wordCount = 10): string
	{
		$result = [];
		for ($i = 0; $i < $wordCount; $i++)
		{
			$result[] = $this->arrayRandom($this->loremWords);
		}

		return implode(' ', $result);
	}

	/**
	 * Generates a fake title.
	 *
	 * @param int $wordCount
	 * @return string
	 */
	public function title(int $wordCount = 3): string
	{
		$words = [];
		for ($i = 0; $i < $wordCount; $i++)
		{
			$words[] = ucfirst($this->arrayRandom($this->loremWords));
		}
		return implode(' ', $words);
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
	 * Generates a fake URL.
	 *
	 * @return string
	 */
	public function url(): string
	{
		$protocols = ['http', 'https'];
		$protocol = $this->arrayRandom($protocols);
		$domain = $this->arrayRandom($this->domains);
		return $protocol . '://' . $domain;
	}

	/**
	 * Generates a fake slug.
	 *
	 * @param int $wordCount
	 * @return string
	 */
	public function slug(int $wordCount = 3): string
	{
		$words = [];
		for ($i = 0; $i < $wordCount; $i++)
		{
			$words[] = $this->arrayRandom($this->loremWords);
		}
		return implode('-', $words);
	}

	/**
	 * Generates a fake IP address.
	 *
	 * @return string
	 */
	public function ipv4(): string
	{
		return sprintf('%d.%d.%d.%d',
			rand(1, 255),
			rand(0, 255),
			rand(0, 255),
			rand(1, 255)
		);
	}

	/**
	 * Generates a fake MAC address.
	 *
	 * @return string
	 */
	public function macAddress(): string
	{
		return sprintf('%02X:%02X:%02X:%02X:%02X:%02X',
			rand(0, 255),
			rand(0, 255),
			rand(0, 255),
			rand(0, 255),
			rand(0, 255),
			rand(0, 255)
		);
	}

	/**
	 * Generates a random hex color.
	 *
	 * @return string
	 */
	public function hexColor(): string
	{
		return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
	}

	/**
	 * Generates a random RGB color.
	 *
	 * @return string
	 */
	public function rgbColor(): string
	{
		return sprintf('rgb(%d, %d, %d)',
			rand(0, 255),
			rand(0, 255),
			rand(0, 255)
		);
	}

	/**
	 * Generates a random date.
	 *
	 * @param string $format
	 * @param string $max
	 * @return string
	 */
	public function date(string $format = 'Y-m-d', string $max = 'now'): string
	{
		$timestamp = strtotime($this->dateTimeBetween('-1 year', $max));
		return date($format, $timestamp);
	}

	/**
	 * Generates a random time.
	 *
	 * @param string $format
	 * @param string $max
	 * @return string
	 */
	public function time(string $format = 'H:i:s', string $max = 'now'): string
	{
		$timestamp = strtotime($this->dateTimeBetween('-1 day', $max));
		return date($format, $timestamp);
	}

	/**
	 * Generates a random Unix timestamp.
	 *
	 * @param string $max
	 * @return int
	 */
	public function unixTime(string $max = 'now'): int
	{
		return strtotime($this->dateTimeBetween('-1 year', $max));
	}

	/**
	 * Picks a random element from an array.
	 *
	 * @param array $array
	 * @return mixed
	 */
	public function randomElement(array $array): mixed
	{
		return $this->arrayRandom($array);
	}

	/**
	 * Generates random elements from an array.
	 *
	 * @param array $array
	 * @param int $count
	 * @return array
	 */
	public function randomElements(array $array, int $count = 1): array
	{
		$result = [];
		$keys = array_rand($array, min($count, count($array)));
		if (!is_array($keys))
		{
			$keys = [$keys];
		}

		foreach ($keys as $key)
		{
			$result[] = $array[$key];
		}
		return $result;
	}

	/**
	 * Shuffles an array and returns it.
	 *
	 * @param array $array
	 * @return array
	 */
	public function shuffleArray(array $array): array
	{
		shuffle($array);
		return $array;
	}

	/**
	 * Generates a unique value using a callback.
	 *
	 * @param callable $callback
	 * @param int $maxRetries
	 * @return mixed
	 */
	public function unique(callable $callback, int $maxRetries = 10000): mixed
	{
		static $seen = [];
		$tries = 0;

		do {
			$value = $callback($this);
			$key = serialize($value);

			if (!isset($seen[$key])) {
				$seen[$key] = true;
				return $value;
			}

			$tries++;
		} while ($tries < $maxRetries);

		throw new \RuntimeException('Could not generate unique value after ' . $maxRetries . ' attempts');
	}

	/**
	 * Generates a random digit (0-9).
	 *
	 * @return int
	 */
	public function randomDigit(): int
	{
		return rand(0, 9);
	}

	/**
	 * Generates a random digit not zero (1-9).
	 *
	 * @return int
	 */
	public function randomDigitNotZero(): int
	{
		return rand(1, 9);
	}

	/**
	 * Generates a random letter (a-z).
	 *
	 * @return string
	 */
	public function randomLetter(): string
	{
		return chr(rand(97, 122));
	}

	/**
	 * Generates random ASCII characters.
	 *
	 * @param int $length
	 * @return string
	 */
	public function randomAscii(int $length = 10): string
	{
		$result = '';
		for ($i = 0; $i < $length; $i++)
		{
			$result .= chr(rand(33, 126));
		}
		return $result;
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