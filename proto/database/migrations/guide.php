<?php
namespace Proto\Database\Migrations;

use Proto\Utils\Strings;
use App\Models\Migration as MigrationModel;
use Proto\Database\Database;

/**
 * Guide
 *
 * This class will guide the migration process.
 *
 * @package Proto\Database\Migrations
 */
class Guide
{
    /**
     * @var string $migrationDir
     */
    protected $migrationDir = __DIR__ . '/../../../app/database/migrations';

    /**
	 * This will get a database connection.
	 *
     * @param string $connection
	 * @return object|false
	 */
	public function getConnection(string $connection)
    {
        $db = new Database();
        return $db->connect($connection);
    }

    /**
     * This will get the previous migrations.
     *
     * @return array
     */
    protected function getPreviousMigrations(): array
    {
        return MigrationModel::all()->rows;
    }

    /**
     * This will get the last migration.
     *
     * @return array
     */
    protected function getLastMigration(): array
    {
        $model = new MigrationModel();
        return $model->getLastMigration();
    }

    /**
     * This will get the migration files.
     *
     * @return array|bool
     */
    protected function getFiles()
    {
        $dir = $this->migrationDir;
        return scandir($dir);
    }

    /**
     * This will get the new migration files.
     *
     * @return array
     */
    protected function getNewMigrations(): array
    {
        $files = $this->getFiles();
        if (count($files) < 1)
        {
            return [];
        }

        $exclude = ['.', '..'];
        $prevMigrations = $this->getPreviousMigrations();
        $newMigrations = [];
        foreach ($files as $file)
        {
            if (in_array($file, $exclude))
            {
                continue;
            }

            $found = false;
            foreach ($prevMigrations as $migration)
            {
                if ($file === $migration->migration)
                {
                    $found = true;
                    break;
                }
            }

            if ($found === false)
            {
                $this->loadMigration($file, $newMigrations);
            }
        }

        ksort($newMigrations);

        return $newMigrations;
    }

    /**
     * This will get the last migrations.
     *
     * @return array
     */
    protected function getLastMigrations(): array
    {
        $migrations = $this->getLastMigration();
        if (count($migrations) < 1)
        {
            return [];
        }

        $lastMigrations = [];
        foreach ($migrations as $migration)
        {
            $this->loadMigration($migration->migration, $lastMigrations, $migration->id);
        }

        ksort($lastMigrations);

        return $lastMigrations;
    }

    /**
     * This will load a migration from file name.
     *
     * @param string $fileName
     * @param array $migrations
     * @return void
     */
    protected function loadMigration(string $fileName, array &$migrations, ?int $id = null): void
    {
        $path = $this->migrationDir . '/' . $fileName;
        if (\file_exists($path) === false)
        {
            return;
        }

        include_once realpath($path);

        $parts = explode('_', $fileName);
        $date = $this->formatDate($parts[0]);

        /**
         * @var object $className
         */
        $className = $this->formatClassName($parts[1]);

        $migration = new $className();
        $migration->setFileName($fileName);

        if (isset($id))
        {
            $migration->setId($id);
        }

        $migrations[$date] = $migration;
    }

    /**
     * This will format the file date.
     *
     * @param string $date
     * @return string
     */
    protected function formatDate(string $date): string
    {
        return str_replace('.', ':', $date);
    }

    /**
     * This will format a class name.
     *
     * @param string $fileName
     * @return string
     */
    protected function formatClassName(string $fileName): string
    {
        $fileName = Strings::pascalCase($fileName);
        return str_replace('.php', '', $fileName);
    }

    /**
     * This will execute a query.
     *
     * @param string $connection
     * @param string $query
     * @return bool
     */
    protected function execute(string $connection, string $query): bool
    {
        $db = $this->getConnection($connection);
		return $db->execute($query);
    }

    /**
     * This will batch all the queries.
     *
     * @param string $connection
     * @param array $queries
     * @return bool
     */
    protected function batch(string $connection, array $queries): bool
    {
        if (count($queries) < 1)
        {
            return false;
        }

        $result = true;
        foreach ($queries as $query)
        {
            $response = $this->execute($connection, $query);
            if ($response !== true)
            {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * This will run the migrations.
     *
     * @return bool
     */
    public function run(): bool
    {
        $migrations = $this->getNewMigrations();
        if (count($migrations) < 1)
        {
            return false;
        }

        $result = true;
        $groupId = strtotime('now');
        foreach ($migrations as $migration)
        {
            $response = $this->up($migration);
            if ($response !== true)
            {
                $result = false;
                continue;
            }

            $this->addMigration($migration, $groupId);
        }
        return $result;
    }

    /**
     * This will revert the last migration.
     *
     * @return bool
     */
    public function revert(): bool
    {
        $migrations = $this->getLastMigrations();
        if (count($migrations) < 1)
        {
            return false;
        }

        $result = true;
        foreach ($migrations as $migration)
        {
            $response = $this->down($migration);
            if ($response !== true)
            {
                $result = false;
                continue;
            }

            $this->deleteMigration($migration);
        }
        return $result;
    }

    /**
     * This will add the migration.
     *
     * @param object $migration
     * @param string $groupId
     * @return bool
     */
    protected function addMigration(object $migration, string $groupId): bool
    {
        $model = new MigrationModel((object)[
            'migration' => $migration->getFileName(),
            'group_id' => $groupId
        ]);

        return $model->add();
    }

    /**
     * This will delete a migration.
     *
     * @param object $migration
     * @return bool
     */
    protected function deleteMigration(object $migration): bool
    {
        $model = new MigrationModel((object)[
            'id' => $migration->getId()
        ]);
        return $model->delete();
    }

    /**
     * Run the migration.
     *
     * @return bool
     */
    public function up(object $migration): bool
    {
        $connection = $migration->getConnection();

        $migration->up();
        $queries = $migration->getQueries();
        return $this->batch($connection, $queries);
    }

    /**
     * Revert the migration.
     *
     * @return bool
     */
    public function down(object $migration): bool
    {
        $connection = $migration->getConnection();

        $migration->down();
        $queries = $migration->getQueries();
        return $this->batch($connection, $queries);
    }
}