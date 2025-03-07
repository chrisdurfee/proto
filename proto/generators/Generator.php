<?php declare(strict_types=1);
namespace Proto\Generators;

use Proto\Http\Response;
use Proto\Generators\FileGeneratorInterface;
use Proto\Database\QueryBuilder\Create;
use Proto\Database\Database;

/**
 * Generator
 *
 * This class is responsible for generating resources based on the provided settings.
 * It uses the Strategy pattern to delegate the creation of individual file resources
 * (such as models, controllers, tests, migrations, API, policy, and storage) to the corresponding
 * file-type generator classes.
 *
 * Additionally, the createResource() method can generate multiple related resources in one go.
 *
 * @package Proto\Generators
 */
class Generator
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->checkEnv();
	}

	/**
	 * Checks that the environment is set to dev.
	 *
	 * @return void
	 */
	protected function checkEnv(): void
	{
		$env = env('env'); // Alternatively, use Config::access('env') if preferred.
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
	 * Delegates resource generation to the provided file generator.
	 *
	 * @param FileGeneratorInterface $generator The file generator strategy.
	 * @param object $settings The settings for file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generateResource(FileGeneratorInterface $generator, object $settings): bool
	{
		return $generator->generate($settings);
	}

	/**
	 * Creates an instance of a file-type generator based on the given type.
	 *
	 * @param string $type The file type (e.g., 'model', 'controller', 'test', etc.).
	 * @return FileGeneratorInterface|null The file generator instance or null if unknown.
	 */
	protected function createFileTypeGenerator(string $type): ?FileGeneratorInterface
	{
		switch (strtolower($type))
		{
			case 'model':
				return new \Proto\Generators\FileTypes\ModelGenerator();
			case 'controller':
				return new \Proto\Generators\FileTypes\ControllerGenerator();
			case 'test':
				return new \Proto\Generators\FileTypes\TestGenerator();
			case 'migration':
				return new \Proto\Generators\FileTypes\MigrationGenerator();
			case 'api':
				return new \Proto\Generators\FileTypes\ApiGenerator();
			case 'policy':
				return new \Proto\Generators\FileTypes\PolicyGenerator();
			case 'storage':
				return new \Proto\Generators\FileTypes\StorageGenerator();
			case 'gateway':
				return new \Proto\Generators\FileTypes\GatewayGenerator();
			case 'module':
				return new \Proto\Generators\FileTypes\ModuleGenerator();
			default:
				return null;
		}
	}

	/**
	 * Creates the appropriate file-type generator and delegates generation.
	 *
	 * @param string $type The file type to generate.
	 * @param string $namespaceDir The base directory or namespace (e.g., "Models", "Controllers").
	 * @param object $settings The settings for file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function createResourceType(string $type, string $namespaceDir, object $settings): bool
	{
		$namespace = $settings->namespace ?? null;
		$this->setupClassNamespace($settings, $namespaceDir, $namespace);
		return $this->generateFileResource($type, $settings);
	}

	/**
	 * Creates a migration.
	 *
	 * @param object $settings The settings for file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function createMigration(object $settings): bool
	{
		return $this->generateFileResource('migration', $settings);
	}

	/**
	 * Creates a unit test.
	 *
	 * @param object $settings The settings for file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function createTest(object $settings): bool
	{
		$type = $settings->type ?? 'Unit';
		$namespaceDir = ($type === 'Feature') ? 'Feature' : 'Unit';
		$namespace = $settings->namespace ?? null;

		$this->setupClassNamespace($settings, $namespaceDir, $namespace);
		return $this->generateFileResource('test', $settings);
	}

	/**
	 * Creates the appropriate file-type generator and delegates generation.
	 *
	 * @param string $type The file type to generate.
	 * @param object $settings The settings for file generation.
	 * @return bool True on success, false otherwise.
	 */
	public function generateFileResource(string $type, object $settings): bool
	{
		$generator = $this->createFileTypeGenerator($type);
		if ($generator === null)
		{
			return false;
		}
		return $this->generateResource($generator, $settings);
	}

	/**
	 * Creates a database table.
	 *
	 * @param object $settings The table settings.
	 * @return bool True on success, false otherwise.
	 */
	public function createTable(object $settings): bool
	{
		$query = new Create($settings->tableName, $settings->callBack);
		$connection = $settings->connection ?? null;

		$db = (new Database())->connect($connection);
		return $db->execute((string)$query);
	}

	/**
	 * Retrieves an object from settings or creates it if not present.
	 *
	 * This helper method ensures that the specific resource settings exist.
	 *
	 * @param object $settings The settings object.
	 * @param string $key The key to check.
	 * @return object The retrieved or new object.
	 */
	protected function getObject(object $settings, string $key): object
	{
		$data = $settings->{$key} = $settings->{$key} ?? (object)[];
		// If a model exists, use its className as a default value for related resources.
		if (isset($settings->model) && !isset($data->className))
		{
			$data->className = $settings->model->className;
		}
		return $data;
	}

	/**
	 * Returns the complete namespace by combining a base and an optional additional namespace.
	 *
	 * @param string $base The base namespace (e.g., "Models", "Controllers", etc.).
	 * @param string|null $namespace An optional additional namespace.
	 * @return string The complete namespace.
	 */
	protected function getNamespace(string $base, ?string $namespace): string
	{
		return $namespace ? $base . '\\' . $namespace : $base;
	}

	/**
	 * Sets up the class namespace in the provided settings.
	 *
	 * This method updates the "dir" property of the settings so that the file generator
	 * can use the correct path. It also stores the namespace in the settings if not already present.
	 *
	 * @param object &$settings The settings object (passed by reference).
	 * @param string $base The base directory or namespace (e.g., "Models", "Controllers").
	 * @param string|null $namespace The optional additional namespace.
	 * @return void
	 */
	protected function setupClassNamespace(object &$settings, string $base, ?string $namespace = null): void
	{
		$settings->dir = $this->getNamespace($base, $namespace);
		if (empty($settings->namespace))
		{
			$settings->namespace = $namespace;
		}
	}

	/**
	 * Creates multiple related resources based on the provided settings.
	 *
	 * This method sequentially generates a database table (if specified), model, controller,
	 * API, policy, and storage files by delegating to the appropriate file-type generators.
	 *
	 * @param object $settings The settings for the resource generation.
	 *                         Expected properties may include:
	 *                         - namespace: Optional namespace for generated files.
	 *                         - table: Settings for creating a database table.
	 *                         - model: Settings for the model file.
	 *                         - controller: Settings for the controller file.
	 *                         - api: Settings for the API file.
	 *                         - policy: Settings for the policy file.
	 *                         - storage: Settings for the storage file.
	 *                         The "model" property is required.
	 * @return bool True on success, false otherwise.
	 */
	public function createResource(object $settings): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$namespace = $settings->namespace ?? null;
		$moduleName = $settings->moduleName ?? null;

		// Create database table if specified.
		if (isset($settings->table))
		{
			$result = $this->createTable($settings->table);
			if (!$result)
			{
				return false;
			}
		}

		// Setup and generate the model file.
		if (!isset($settings->model))
		{
			return false;
		}

		$settings->model->moduleName = $moduleName;
		$this->setupClassNamespace($settings->model, "Models", $namespace);
		$result = $this->generateFileResource('model', $settings->model);
		if (!$result)
		{
			return false;
		}

		// Setup and generate the controller file.
		$controller = $this->getObject($settings, 'controller');
		$controller->moduleName = $moduleName;
		$this->setupClassNamespace($controller, "Controllers", $namespace);
		$result = $this->generateFileResource('controller', $controller);
		if (!$result)
		{
			return false;
		}

		// Setup and generate the API file.
		$api = $this->getObject($settings, 'api');
		$api->moduleName = $moduleName;
		$this->setupClassNamespace($api, "API", $namespace);
		$result = $this->generateFileResource('api', $api);
		if (!$result)
		{
			return false;
		}

		// Setup and generate the policy file if policy settings exist.
		$policySettings = $settings->policy ?? null;
		$policySettings->moduleName = $moduleName;
		if (!empty($policySettings))
		{
			$policy = $this->getObject($settings, 'policy');
			$this->setupClassNamespace($policy, "Auth\\Policies", $namespace);
			$result = $this->generateFileResource('policy', $policy);
			if (!$result)
			{
				return false;
			}
		}

		// If the model does not require its own storage, return the current result.
		if (isset($settings->model->storage) && (bool)$settings->model->storage === false)
		{
			return $result;
		}

		// Setup and generate the storage file.
		$storage = $this->getObject($settings, 'storage');
		$storage->moduleName = $moduleName;
		$this->setupClassNamespace($storage, "Storage", $namespace);
		return $this->generateFileResource('storage', $storage);
	}
}