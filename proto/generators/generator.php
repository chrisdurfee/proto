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
	 * This will check that the env is set to dev before running.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->checkEnv();
	}

	/**
	 * This will check that the env is set to dev.
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
	 * This will save a file.
	 *
	 * @param string $dir
	 * @param string $fileName
	 * @param mixed $content
	 * @return bool
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
	 * This will get a file name.
	 *
	 * @param string $str
	 * @return string
	 */
	protected function getFileName(string $str): string
	{
		return Strings::hyphen($str) . '.php';
	}

    /**
     * This will get a file dir.
     *
     * @param string $dir
     * @return string
     */
	protected function getDir(string $dir): string
	{
		$dir = str_replace('\\', '/', $dir);
		return realpath(__DIR__ . '/../../app') . $this->convertSlashes('/'. strtolower(Strings::hyphen($dir)));
	}

	/**
	 * This will convert slashes.
	 *
	 * @param string $path
	 * @return string|array
	 */
	protected function convertSlashes(string $path)
	{
		return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * This will get the class namespace.
	 *
	 * @param string $dir
	 * @param string|null $namespace
	 * @return string
	 */
	protected function getNamespace(string $dir, ?string $namespace): string
	{
		return (!empty($namespace))? $dir . '\\' . $namespace : $dir;
	}

	/**
	 * This will create a database table.
	 *
	 * @param object $settings
	 * @return bool
	 */
	public function createTable(object $settings): bool
	{
		$query = new Create($settings->tableName, $settings->callBack);
		$connection = $settings->connection ?? null;

		$db = $this->getConnection($connection);
		return $db->execute($query);
	}

	/**
	 * This will get a database connection.
	 *
	 * @param string|null $connection
	 * @return object
	 */
	public function getConnection(?string $connection = null)
    {
        $db = new Database();
        return $db->connect($connection);
    }

	/**
	 * This will create a class.
	 *
	 * @param object $settings
	 * @param mixed $template
	 * @return bool
	 */
	public function createClass(object $settings, mixed $template, string $fileName): bool
	{
		$dir = $this->getDir($settings->dir);
		return $this->saveFile($dir, $fileName, $template);
	}

	/**
	 * This will get the class namespace.
	 *
	 * @param object $settings
	 * @param string|null $namespace
	 * @return string|null
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
	 * This will setup the class namespace.
	 *
	 * @param object $settings
	 * @param string $type
	 * @param string|null $namespace
	 * @return void
	 */
	protected function setupClassNamespace(
		object &$settings,
		string $type,
		?string $namespace = null
	): void
	{
		$namespace = $this->getClassNamespace($settings, $namespace);
		$settings->dir = $this->getNamespace($type, $namespace);
	}

	/**
	 * This will create a model.
	 *
	 * @param object $settings
	 * @param string|null $namespace
	 * @return bool
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
	 * This will create a controller.
	 *
	 * @param object $settings
	 * @param string|null $namespace
	 * @return bool
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
	 * This will get the test type.
	 *
	 * @param string|null $type
	 * @return string
	 */
	protected function getTestType(?string $type = 'unit'): string
	{
		if (empty($type))
		{
			return 'Unit';
		}

		return ucfirst($type);
	}

	/**
	 * This will create a test.
	 *
	 * @param object $settings
	 * @param string|null $namespace
	 * @return bool
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
     * This will get a file dir.
     *
     * @param string $dir
     * @return string
     */
	protected function getTestDir(string $dir): string
	{
		$dir = str_replace('\\', '/', $dir);
		return realpath(__DIR__ . '/../../tests') . $this->convertSlashes('/'. strtolower(Strings::hyphen($dir)));
	}

	/**
	 * This will create a class.
	 *
	 * @param object $settings
	 * @param mixed $template
	 * @return bool
	 */
	public function createTestClass(object $settings, mixed $template, string $fileName): bool
	{
		$dir = $this->getTestDir($settings->dir);

		/**
		 * This will convert the file name to pascal case
		 * to support the PHPUnit cli.
		 */
		$formatedFileName = Strings::pascalCase($fileName);
		return $this->saveFile($dir, $formatedFileName, $template);
	}

	/**
	 * This will create a migration.
	 *
	 * @param object $settings
	 * @return bool
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
	 * This will get the file date.
	 *
	 * @return string
	 */
	protected function getFileDate(): string
	{
		$dateTime = new \DateTime();
		$dateTime = $dateTime->format('Y-m-d H.i.s.u');
		$dateTime = str_replace(' ', 'T', $dateTime);
		return $dateTime;
	}

	/**
	 * This will create an api.
	 *
	 * @param object $settings
	 * @param object $policy
	 * @param string|null $namespace
	 * @return bool
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
	 * This will create a policy.
	 *
	 * @param object $settings
	 * @param string|null $namespace
	 * @return bool
	 */
	public function createPolicy(object $settings, ?string $namespace = null): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$this->setupClassNamespace($settings, "Auth\Policies", $namespace);
		$fileName = $this->getFileName($settings->className . 'Policy');
		$template = new Templates\PolicyTemplate($settings);
		return $this->createClass($settings, $template, $fileName);
	}

	/**
	 * This will create a storage.
	 *
	 * @param object $settings
	 * @param string|null $namespace
	 * @return bool
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
	 * This will get the object or create it if none is found.
	 *
	 * @param object $settings
	 * @param string $key
	 * @return object
	 */
	protected function getObject(object $settings, string $key): object
	{
		$data = $settings->{$key} = $settings->{$key} ?? (object)[];
		$data->className = $settings->model->className;
		return $data;
	}

	/**
	 * This will create a controller.
	 *
	 * @param string $dir
	 * @param object $settings
	 * @return bool
	 */
	public function createResource(object $settings): bool
	{
		if (empty($settings))
		{
			return false;
		}

		$namespace = $settings->namespace ?? null;

		// this will check to setup the database table
		if (isset($settings->table))
		{
			$result = $this->createTable($settings->table);
			if (!$result)
			{
				return false;
			}
		}

		// This will create the model file
		$result = $this->createModel($settings->model, $namespace);
		if (!$result)
		{
			return false;
		}

		// this will create the controller
		$controller = $this->getObject($settings, 'controller');
		$result = $this->createController($controller, $namespace);
		if (!$result)
		{
			return false;
		}

		// this will create the api
		$api = $this->getObject($settings, 'api');
		$policySettings = $settings->policy ?? null;
		$result = $this->createApi($api, $policySettings, $namespace);
		if (!$result)
		{
			return false;
		}

		if (!empty($policySettings))
		{
			$policy = $this->getObject($settings, 'policy');
			$result = $this->createPolicy($policy, $namespace);
			if (!$result)
			{
				return false;
			}
		}

		// this will check to stop if the model does not add its own storage
		if ((bool)($settings->model->storage) === false)
		{
			return $result;
		}

		// this will create the storage
		$storage = $this->getObject($settings, 'storage');
		return $this->createStorage($storage, $namespace);
	}
}