<?php declare(strict_types=1);
namespace Proto\Generators;

use Proto\Base;
use Proto\Config;
use Proto\Http\Response;
use Proto\Utils\Files\File;
use Proto\Utils\Strings;
use Proto\Generators\Templates;
use Proto\Database\QueryBuilder\Create;
use Proto\Database\Database;

/**
 * Generator
 *
 * This will generate new resources.
 *
 * @package Proto\Generators
 */
class Generator extends Base
{
	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->checkEnv();
	}

	/**
	 * Checks that the env is set to dev.
	 *
	 * @return void
	 */
	protected function checkEnv(): void
	{
		$env = Config::access('env');
		if ($env !== 'dev')
		{
			new Response([
				'message' => 'Unable to generate new resources when the env is not set to dev.',
				'success' => false
			], 403);
			die;
		}
	}

	/**
	 * Saves a file.
	 *
	 * @param string $dir The directory path.
	 * @param string $fileName The file name.
	 * @param mixed $content The file content.
	 * @return bool True on success, false if the file already exists.
	 */
	protected function saveFile(string $dir, string $fileName, mixed $content): bool
	{
		$path = $this->convertSlashes($dir . '/' . $fileName);
		if (\file_exists($path))
		{
			return false;
		}

		File::put($path, (string)$content);
		return true;
	}

	/**
	 * Gets a file name.
	 *
	 * @param string $str The base string.
	 * @return string The generated file name.
	 */
	protected function getFileName(string $str): string
	{
		return Strings::hyphen($str) . '.php';
	}

	/**
	 * Gets a file directory.
	 *
	 * @param string $dir The relative directory.
	 * @return string The full directory path.
	 */
	protected function getDir(string $dir): string
	{
		$dir = str_replace('\\', '/', $dir);
		return realpath(__DIR__ . '/../../app') . $this->convertSlashes('/' . strtolower(Strings::hyphen($dir)));
	}

	/**
	 * Converts slashes in a path.
	 *
	 * @param string $path The path to convert.
	 * @return string|string[] The converted path.
	 */
	protected function convertSlashes(string $path)
	{
		return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * Gets the class namespace.
	 *
	 * @param string $dir The base directory.
	 * @param string|null $namespace The namespace suffix.
	 * @return string The complete namespace.
	 */
	protected function getNamespace(string $dir, ?string $namespace): string
	{
		return !empty($namespace) ? $dir . '\\' . $namespace : $dir;
	}

	/**
	 * Creates a database table.
	 *
	 * @param object $settings The table settings.
	 * @return bool True on success.
	 */
	public function createTable(object $settings): bool
	{
		$query = new Create($settings->tableName, $settings->callBack);
		$connection = $settings->connection ?? null;
		$db = $this->getConnection($connection);
		return $db->execute($query);
	}

	/**
	 * Gets a database connection.
	 *
	 * @param string|null $connection The connection name.
	 * @return object The database connection.
	 */
	public function getConnection(?string $connection = null)
	{
		$db = new Database();
		return $db->connect($connection);
	}

	/**
	 * Creates a class file.
	 *
	 * @param object $settings The class settings.
	 * @param mixed $template The template instance.
	 * @param string $fileName The file name.
	 * @return bool True on success.
	 */
	public function createClass(object $settings, mixed $template, string $fileName): bool
	{
		$dir = $this->getDir($settings->dir);
		return $this->saveFile($dir, $fileName, $template);
	}

	/**
	 * Gets the class namespace from settings.
	 *
	 * @param object $settings The settings object (passed by reference).
	 * @param string|null $namespace An optional namespace.
	 * @return string|null The namespace.
	 */
	protected function getClassNamespace(object &$settings, ?string $namespace = null): ?string
	{
		if (!empty($settings->namespace))
		{
			return $settings->namespace;
		}

		if (!$namespace)
		{
			return null;
		}

		$settings->namespace = $namespace;
		return $namespace;
	}

	/**
	 * Sets up the class namespace in settings.
	 *
	 * @param object $settings The settings object (passed by reference).
	 * @param string $type The type directory.
	 * @param string|null $namespace An optional namespace.
	 * @return void
	 */
	protected function setupClassNamespace(object &$settings, string $type, ?string $namespace = null): void
	{
		$namespace = $this->getClassNamespace($settings, $namespace);
		$settings->dir = $this->getNamespace($type, $namespace);
	}

	/**
	 * Creates a model.
	 *
	 * @param object $settings The model settings.
	 * @param string|null $namespace An optional namespace.
	 * @return bool True on success.
	 */
	public function createModel(object $settings, ?string $namespace = null): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$this->setupClassNamespace($settings, "Models", $namespace);
		$fileName = $this->getFileName($settings->className);
		$template = new Templates\ModelTemplate($settings);
		return $this->createClass($settings, $template, $fileName);
	}

	/**
	 * Creates a controller.
	 *
	 * @param object $settings The controller settings.
	 * @param string|null $namespace An optional namespace.
	 * @return bool True on success.
	 */
	public function createController(object $settings, ?string $namespace = null): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$this->setupClassNamespace($settings, "Controllers", $namespace);
		$fileName = $this->getFileName($settings->className . 'Controller');
		$template = new Templates\ControllerTemplate($settings);
		return $this->createClass($settings, $template, $fileName);
	}

	/**
	 * Gets the test type.
	 *
	 * @param string|null $type The test type.
	 * @return string The formatted test type.
	 */
	protected function getTestType(?string $type = 'unit'): string
	{
		return empty($type) ? 'Unit' : ucfirst($type);
	}

	/**
	 * Creates a test.
	 *
	 * @param object $settings The test settings.
	 * @param string|null $namespace An optional namespace.
	 * @return bool True on success.
	 */
	public function createTest(object $settings, ?string $namespace = null): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$testType = $settings->type ?? null;
		$type = $this->getTestType($testType);
		$this->setupClassNamespace($settings, $type, $namespace);
		$fileName = $this->getFileName($settings->className . 'Test');
		$template = new Templates\TestTemplate($settings);
		return $this->createTestClass($settings, $template, $fileName);
	}

	/**
	 * Gets a test directory.
	 *
	 * @param string $dir The relative directory.
	 * @return string The full test directory path.
	 */
	protected function getTestDir(string $dir): string
	{
		$dir = str_replace('\\', '/', $dir);
		return realpath(__DIR__ . '/../../tests') . $this->convertSlashes('/' . strtolower(Strings::hyphen($dir)));
	}

	/**
	 * Creates a test class file.
	 *
	 * @param object $settings The class settings.
	 * @param mixed $template The template instance.
	 * @param string $fileName The file name.
	 * @return bool True on success.
	 */
	public function createTestClass(object $settings, mixed $template, string $fileName): bool
	{
		$dir = $this->getTestDir($settings->dir);
		// Convert the file name to PascalCase to support the PHPUnit CLI.
		$formatedFileName = Strings::pascalCase($fileName);
		return $this->saveFile($dir, $formatedFileName, $template);
	}

	/**
	 * Creates a migration.
	 *
	 * @param object $settings The migration settings.
	 * @return bool True on success.
	 */
	public function createMigration(object $settings): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$settings->dir = 'Database\Migrations';
		$dateTime = $this->getFileDate();
		$fileName = $dateTime . '_' . $this->getFileName($settings->className);
		$template = new Templates\MigrationTemplate($settings);
		return $this->createClass($settings, $template, $fileName);
	}

	/**
	 * Gets the file date.
	 *
	 * @return string The formatted date.
	 */
	protected function getFileDate(): string
	{
		$dateTime = new \DateTime();
		$dateTime = $dateTime->format('Y-m-d H.i.s.u');
		return str_replace(' ', 'T', $dateTime);
	}

	/**
	 * Creates an API resource.
	 *
	 * @param object $settings The API settings.
	 * @param object|null $policy The policy settings.
	 * @param string|null $namespace An optional namespace.
	 * @return bool True on success.
	 */
	public function createApi(object $settings, ?object $policy = null, ?string $namespace = null): bool
	{
		if (empty($settings))
		{
			return false;
		}

		if (empty($settings->policy) && !empty($policy))
		{
			$settings->policy = $settings->className . 'Policy';
		}

		$this->setupClassNamespace($settings, "API", $namespace);
		$fileName = $this->getFileName($settings->className . 'API');
		$template = new Templates\ApiTemplate($settings);
		return $this->createClass($settings, $template, $fileName);
	}

	/**
	 * Creates a policy.
	 *
	 * @param object $settings The policy settings.
	 * @param string|null $namespace An optional namespace.
	 * @return bool True on success.
	 */
	public function createPolicy(object $settings, ?string $namespace = null): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$this->setupClassNamespace($settings, "Auth\\Policies", $namespace);
		$fileName = $this->getFileName($settings->className . 'Policy');
		$template = new Templates\PolicyTemplate($settings);
		return $this->createClass($settings, $template, $fileName);
	}

	/**
	 * Creates a storage resource.
	 *
	 * @param object $settings The storage settings.
	 * @param string|null $namespace An optional namespace.
	 * @return bool True on success.
	 */
	public function createStorage(object $settings, ?string $namespace = null): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$this->setupClassNamespace($settings, "Storage", $namespace);
		$fileName = $this->getFileName($settings->className . 'Storage');
		$template = new Templates\StorageTemplate($settings);
		return $this->createClass($settings, $template, $fileName);
	}

	/**
	 * Gets an object from settings or creates it if it doesn't exist.
	 *
	 * @param object $settings The settings object.
	 * @param string $key The key to check.
	 * @return object The retrieved or new object.
	 */
	protected function getObject(object $settings, string $key): object
	{
		$data = $settings->{$key} = $settings->{$key} ?? (object)[];
		$data->className = $settings->model->className;
		return $data;
	}

	/**
	 * Creates a complete resource (model, controller, API, policy, and storage).
	 *
	 * @param object $settings The resource settings.
	 * @return bool True on success.
	 */
	public function createResource(object $settings): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$namespace = $settings->namespace ?? null;

		// Check and setup the database table if specified.
		if (isset($settings->table))
		{
			$result = $this->createTable($settings->table);
			if (!$result)
			{
				return false;
			}
		}

		// Create the model file.
		$result = $this->createModel($settings->model, $namespace);
		if (!$result)
		{
			return false;
		}

		// Create the controller.
		$controller = $this->getObject($settings, 'controller');
		$result = $this->createController($controller, $namespace);
		if (!$result)
		{
			return false;
		}

		// Create the API.
		$api = $this->getObject($settings, 'api');
		$policySettings = $settings->policy ?? null;
		$result = $this->createApi($api, $policySettings, $namespace);
		if (!$result)
		{
			return false;
		}

		// Create the policy if specified.
		if (!empty($policySettings))
		{
			$policy = $this->getObject($settings, 'policy');
			$result = $this->createPolicy($policy, $namespace);
			if (!$result)
			{
				return false;
			}
		}

		// If the model does not require its own storage, return.
		if ((bool)($settings->model->storage) === false)
		{
			return $result;
		}

		// Create the storage.
		$storage = $this->getObject($settings, 'storage');
		return $this->createStorage($storage, $namespace);
	}
}