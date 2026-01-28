<?php declare(strict_types=1);
namespace Proto\Database\Migrations;

use Proto\Utils\Strings;
use Proto\Database\Migrations\Models\Migration as MigrationModel;
use Proto\Database\Database;
use Proto\Database\Adapters\Adapter;
use Proto\Error\Error;

/**
 * Guide
 *
 * Handles the migration process.
 *
 * @package Proto\Database\Migrations
 */
class Guide
{
	/**
	 * @var array $migrationDirs Array of paths to migration directories.
	 */
	protected array $migrationDirs = [];

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->setMigrationDirs();
	}

	/**
	 * This will set the migration directories.
	 *
	 * @return void
	 */
	protected function setMigrationDirs(): void
	{
		$projectRoot = BASE_PATH;

		$this->migrationDirs = [
			$projectRoot . '/vendor/protoframework/proto/src/Migrations',
			$projectRoot . '/common/Migrations',
		];

		$modulesDir = $projectRoot . '/modules';
		if (is_dir($modulesDir))
		{
			$this->scanModulesForMigrations($modulesDir);
		}
	}

	/**
	 * Recursively scans modules directory for migration folders.
	 *
	 * Supports both flat and nested feature module structures:
	 * - modules/User/Migrations/
	 * - modules/Community/Group/Migrations/
	 * - modules/Community/Group/Forum/Migrations/
	 *
	 * @param string $dir The directory to scan.
	 * @param int $depth Current recursion depth (max 3 levels).
	 * @return void
	 */
	protected function scanModulesForMigrations(string $dir, int $depth = 0): void
	{
		// Limit recursion depth to prevent infinite loops
		// Level 0: modules/
		// Level 1: modules/Community/
		// Level 2: modules/Community/Group/
		// Level 3: modules/Community/Group/Forum/
		if ($depth > 3)
		{
			return;
		}

		$entries = array_diff(scandir($dir) ?: [], ['.', '..']);

		foreach ($entries as $entry)
		{
			$fullPath = $dir . '/' . $entry;

			// Skip if not a directory
			if (!is_dir($fullPath))
			{
				continue;
			}

			// Skip common framework directories that shouldn't contain migrations
			$skipDirs = ['Api', 'Controllers', 'Models', 'Services', 'Storage', 'Gateway', 'Auth', 'Tests', 'Factories', 'Seeders', 'Main'];
			if (in_array($entry, $skipDirs))
			{
				continue;
			}

			// Check if this directory has a Migrations folder
			$migrationsDir = $fullPath . '/Migrations';
			if (is_dir($migrationsDir))
			{
				$this->migrationDirs[] = $migrationsDir;
			}

			// Recursively scan for nested features (but not into Migrations folder itself)
			if ($entry !== 'Migrations')
			{
				$this->scanModulesForMigrations($fullPath, $depth + 1);
			}
		}
	}

	/**
	 * Gets a database connection.
	 *
	 * @param string $connection Database connection name.
	 * @return Adapter|null Database instance or null on failure.
	 */
	public function getConnection(string $connection) : ?Adapter
	{
		// Use static method with caching enabled for test isolation
		return Database::getConnection($connection, true);
	}

	/**
	 * Retrieves all previous migrations.
	 *
	 * @return array Previous migration records or an empty array if none exist.
	 */
	protected function getPreviousMigrations() : array
	{
		Error::disable();

		try
		{
			return MigrationModel::all()->rows;
		}
		catch (\Exception $e)
		{
			return [];
		}
		finally
		{
			Error::enable();
		}
	}

	/**
	 * Retrieves the last migration.
	 *
	 * @return array Last migration record.
	 */
	protected function getLastMigration() : array
	{
		$model = new MigrationModel();
		return $model->getLastMigration();
	}

	/**
	 * Retrieves all migration files from the configured directories.
	 *
	 * @return array List of migration file paths with directory context.
	 */
	protected function getMigrationFiles() : array
	{
		$migrationFiles = [];
		$dirOrder = ['proto' => 0, 'common' => 1, 'module' => 2];

		foreach ($this->migrationDirs as $index => $dir)
		{
			if (is_dir($dir))
			{
				// Determine directory type
				$dirType = 'module'; // default
				if (strpos($dir, '/vendor/protoframework/proto/') !== false || strpos($dir, '\\vendor\\protoframework\\proto\\') !== false)
				{
					$dirType = 'proto';
				}
				elseif (strpos($dir, '/common/Migrations') !== false || strpos($dir, '\\common\\Migrations') !== false)
				{
					$dirType = 'common';
				}

				$files = array_diff(scandir($dir) ?: [], ['.', '..']);
				foreach ($files as $file)
				{
					$fullPath = $dir . '/' . $file;
					if (is_file($fullPath) && pathinfo($file, PATHINFO_EXTENSION) === 'php')
					{
						$migrationFiles[] = [
							'fullPath' => $fullPath,
							'file' => $file,
							'dirType' => $dirType,
							'dirOrder' => $dirOrder[$dirType]
						];
					}
				}
			}
		}
		return $migrationFiles;
	}

	/**
	 * Retrieves new migration files that haven't been executed.
	 *
	 * @return array List of new migrations.
	 */
	protected function getNewMigrations() : array
	{
		$files = $this->getMigrationFiles();
		$prevMigrations = array_column($this->getPreviousMigrations(), 'migration');

		$newMigrations = [];
		foreach ($files as $fileData)
		{
			if (!in_array($fileData['file'], $prevMigrations, true))
			{
				$this->loadMigration($fileData['fullPath'], $newMigrations, null, $fileData['dirOrder']);
			}
		}

		// Sort by directory order first, then by timestamp
		uasort($newMigrations, function($a, $b)
		{
			if ($a['dirOrder'] !== $b['dirOrder'])
			{
				return $a['dirOrder'] <=> $b['dirOrder'];
			}
			return $a['timestamp'] <=> $b['timestamp'];
		});

		// Extract just the migration objects
		return array_column($newMigrations, 'migration');
	}

	/**
	 * Retrieves the last executed migrations.
	 *
	 * @return array Last executed migrations.
	 */
	protected function getLastMigrations() : array
	{
		$migrations = $this->getLastMigration();
		$lastMigrations = [];

		foreach ($migrations as $migration)
		{
			$fullPath = $this->getFileDirByFileName($migration->migration) . '/' . $migration->migration;
			$this->loadMigration($fullPath, $lastMigrations, (int) $migration->id);
		}

		ksort($lastMigrations);
		return $lastMigrations;
	}

	/**
	 * Retrieves the directory of a migration file by its name.
	 *
	 * @param string $fileName Migration file name.
	 * @return string Directory path.
	 */
	protected function getFileDirByFileName(string $fileName): string
	{
		foreach ($this->migrationDirs as $dir)
		{
			$fullPath = $dir . '/' . $fileName;
			if (file_exists($fullPath))
			{
				return $dir;
			}
		}
		return '';
	}

	/**
	 * Loads a migration file.
	 *
	 * @param string $filePath Full path to the migration file.
	 * @param array $migrations List of migration instances.
	 * @param int|null $id Migration ID (if applicable).
	 * @param int|null $dirOrder Directory order for sorting (proto=0, common=1, module=2).
	 * @return void
	 */
	protected function loadMigration(string $filePath, array &$migrations, ?int $id = null, ?int $dirOrder = null) : void
	{
		if (!file_exists($filePath))
		{
			return;
		}

		include_once realpath($filePath);

		$fileName = basename($filePath);
		$parts = explode('_', $fileName);
		$date = $this->formatDate($parts[0]);
		$className = $this->formatClassName($parts[1]);

		$migration = new $className();
		$migration->setFileName($fileName);

		if ($id !== null)
		{
			$migration->setId($id);
		}

		if ($dirOrder !== null)
		{
			// Store with metadata for sorting
			$migrations[] = [
				'migration' => $migration,
				'timestamp' => $date,
				'dirOrder' => $dirOrder
			];
		}
		else
		{
			// Backward compatibility for getLastMigrations
			$migrations[$date] = $migration;
		}
	}

	/**
	 * Formats migration file date.
	 *
	 * @param string $date Raw date string.
	 * @return string Formatted date.
	 */
	protected function formatDate(string $date) : string
	{
		return str_replace('.', ':', $date);
	}

	/**
	 * Converts a file name into a class name.
	 *
	 * @param string $fileName Migration file name.
	 * @return string Formatted class name.
	 */
	protected function formatClassName(string $fileName) : string
	{
		return str_replace('.php', '', Strings::pascalCase($fileName));
	}

	/**
	 * Executes a query on a specific connection.
	 *
	 * @param string $connection Database connection name.
	 * @param string|object $query SQL query string.
	 * @return bool Query execution success.
	 */
	protected function executeQuery(string $connection, string|object $query) : bool
	{
		$db = $this->getConnection($connection);
		return $db ? $db->execute((string)$query) : false;
	}

	/**
	 * Executes a batch of queries.
	 *
	 * @param string $connection Database connection name.
	 * @param array $queries List of SQL queries.
	 * @return bool Batch execution success.
	 */
	protected function executeBatch(string $connection, array $queries) : bool
	{
		if (empty($queries))
		{
			return false;
		}

		foreach ($queries as $query)
		{
			if (!$this->executeQuery($connection, $query))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Runs pending migrations.
	 *
	 * @return bool Migration execution success.
	 */
	public function run() : bool
	{
		$migrations = $this->getNewMigrations();
		if (empty($migrations))
		{
			return true;
		}

		$result = true;
		$groupId = (string) time();

		foreach ($migrations as $migration)
		{
			if (!$this->applyMigration($migration))
			{
				$result = false;
				continue;
			}

			$this->seedMigration($migration);
			$this->recordMigration($migration, $groupId);
		}

		return $result;
	}

	/**
	 * Seeds a migration.
	 *
	 * @param Migration $migration Migration instance.
	 * @return bool Seed success.
	 */
	public function seedMigration(Migration $migration) : bool
	{
		$migration->seed();
		return true;
	}

	/**
	 * Reverts the last executed migrations.
	 *
	 * @return bool Migration rollback success.
	 */
	public function revert() : bool
	{
		$migrations = $this->getLastMigrations();
		if (empty($migrations))
		{
			return false;
		}

		/**
		 * The migrations should be run in reverse order.
		 * This is because newer tables may have foreign key constraints
		 * that reference older tables.
		 */
		$migrations = array_reverse($migrations);

		$result = true;
		foreach ($migrations as $migration)
		{
			if (!$this->revertMigration($migration))
			{
				$result = false;
				continue;
			}

			$this->removeMigrationRecord($migration);
		}

		return $result;
	}

	/**
	 * Records a migration.
	 *
	 * @param object $migration Migration instance.
	 * @param string $groupId Group identifier.
	 * @return bool Record insertion success.
	 */
	protected function recordMigration(object $migration, string $groupId) : bool
	{
		$model = new MigrationModel((object) [
			'migration' => $migration->getFileName(),
			'group_id' => $groupId
		]);

		return $model->add();
	}

	/**
	 * Removes a migration record.
	 *
	 * @param object $migration Migration instance.
	 * @return bool Record deletion success.
	 */
	protected function removeMigrationRecord(object $migration) : bool
	{
		$model = new MigrationModel((object) [
			'id' => $migration->getId()
		]);

		return $model->delete();
	}

	/**
	 * Applies a migration.
	 *
	 * @param object $migration Migration instance.
	 * @return bool Migration success.
	 */
	public function applyMigration(object $migration) : bool
	{
		$migration->up();

		$queries = $migration->getQueries();
		if (empty($queries))
		{
			return true;
		}

		$connection = $migration->getConnection();
		return $this->executeBatch($connection, $queries);
	}

	/**
	 * Reverts a migration.
	 *
	 * @param object $migration Migration instance.
	 * @return bool Rollback success.
	 */
	public function revertMigration(object $migration) : bool
	{
		$migration->down();

		$queries = $migration->getQueries();
		if (empty($queries))
		{
			return true;
		}

		$connection = $migration->getConnection();
		return $this->executeBatch($connection, $queries);
	}
}