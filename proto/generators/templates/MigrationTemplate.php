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

	}

	/**
	 * Reverts the migration.
	 *
	 * @return void
	 */
	public function down(): void
	{

	}
EOT;
	}
}