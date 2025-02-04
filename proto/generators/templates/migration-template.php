<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * MigrationTemplate
 *
 * This will create a migration template generator.
 *
 * @package Proto\Generators\Templates
 */
class MigrationTemplate extends ClassTemplate
{
    /**
     * Thi swill set the clas namespace to empty.
     *
     * @return string
     */
    protected function getFileNamespace(): string
    {
        return '';
    }

    /**
     * This will get the extends string.
     *
     * @return string
     */
    protected function getExtends(): string
    {
        return 'extends Migration';
    }

    /**
     * This will get the use string.
     *
     * @return string
     */
    protected function getUse(): string
    {
        return <<<EOT
use Proto\\Database\\Migrations\\Migration;
EOT;
    }

    /**
     * This will get the class content.
     *
     * @return string
     */
    protected function getClassContent(): string
    {
        return <<<EOT
    /**
     * @var string \$connection
     */
    protected string \$connection = '';

    /**
     * Runs the migration.
     *
     * @return void
     */
    public function up()
    {

    }

    /**
     * Reverts the migration.
     *
     * @return void
     */
    public function down()
    {

    }
EOT;
    }
}
