<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * MigrationTemplate
 *
 * This class generates a migration template.
 *
 * @package Proto\Generators\Templates
 */
class MigrationTemplate extends ClassTemplate
{
	/**
	 * Sets the class namespace to empty.
	 *
	 * @return string
	 */
	protected function getFileNamespace(): string
	{
		return '';
	}

	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		return 'extends Migration';
	}

	/**
	 * Retrieves the use statement.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		return "use Proto\\Database\\Migrations\\Migration;";
	}

	/**
	 * Retrieves the up migration string.
	 *
	 * @return string
	 */
	protected function up(): string
	{
		$up = $this->get('up');
		if ($up)
		{
			// replace the \t and \n with proper indentation
			return str_replace(['\t', '\n'], ["\t\t", PHP_EOL . "\t\t"], $up);
		}

		return '';
	}

	/**
	 * Retrieves the down migration string.
	 *
	 * @return string
	 */
	protected function down(): string
	{
		$down = $this->get('down');
		if ($down)
		{
			// replace the \t and \n with proper indentation
			return str_replace(['\t', '\n'], ["\t\t", PHP_EOL . "\t\t"], $down);
		}

		return '';
	}

	/**
	 * This will get the package string.
	 *
	 * @return string
	 */
	protected function getPackage(): string
	{
		return " ";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$up = $this->up();
		$down = $this->down();

		return <<<EOT
	/**
	 * @var string \$connection
	 */
	protected string \$connection = 'default';

	/**
	 * Runs the migration.
	 *
	 * @return void
	 */
	public function up(): void
	{
		$up
	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{
		$down
	}
EOT;
	}
}