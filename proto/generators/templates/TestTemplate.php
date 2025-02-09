<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * TestTemplate
 *
 * This class generates a test template.
 *
 * @package Proto\Generators\Templates
 */
class TestTemplate extends ClassTemplate
{
	/**
	 * Marks the class as final by default.
	 *
	 * @var bool
	 */
	protected bool $final = true;

	/**
	 * Retrieves the class directory.
	 *
	 * @return string
	 */
	protected function getDir(): string
	{
		return 'Tests\\' . $this->get('dir');
	}

	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'Test');
	}

	/**
	 * Retrieves the test class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Test';
	}

	/**
	 * Retrieves the use statement.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		return "use Tests\\Test;";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		return <<<EOT
	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		// do something on setup
	}

	/**
	 * Tears down the test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		// do something on tear down
	}
EOT;
	}
}