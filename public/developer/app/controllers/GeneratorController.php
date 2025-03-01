<?php declare(strict_types=1);
namespace Developer\App\Controllers;

use Proto\Generators\Generator;

/**
 * Controller for handling generator resources.
 *
 * This controller provides endpoints for creating various resources such as APIs,
 * controllers, models, storage, policies, tables, migrations, and unit tests using
 * the underlying Generator service.
 *
 * @package Developer\App\Controllers
 */
class GeneratorController extends Controller
{
	/**
	 * Initializes the Generator service.
     *
     * @param Generator|null $generator The generator service instance.
	 * @return void
	 */
	public function __construct(
        protected ?Generator $generator = new Generator()
    )
	{
		parent::__construct();
	}

	/**
	 * Adds a resource based on its type.
	 *
	 * Supported types: 'full-resource', 'api', 'controller', 'model', 'storage',
	 * 'policy', 'table', 'migration', 'unit-test'.
	 *
	 * @param string $type Resource type.
	 * @param object $resource Resource object containing the necessary data.
	 * @return object Response object.
	 */
	public function addByType(string $type, object $resource): object
	{
		$result = false;

		switch ($type)
		{
			case 'full-resource':
				$result = $this->addResource($resource);
				break;
			case 'api':
				$result = $this->addApi($resource);
				break;
			case 'controller':
				$result = $this->addController($resource);
				break;
			case 'model':
				$result = $this->addModel($resource);
				break;
			case 'storage':
				$result = $this->addStorage($resource);
				break;
			case 'policy':
				$result = $this->addPolicy($resource);
				break;
			case 'table':
				$result = $this->addTable($resource);
				break;
			case 'migration':
				$result = $this->addMigration($resource);
				break;
			case 'unit-test':
				$result = $this->addUnitTest($resource);
				break;
		}

		return $this->response($result);
	}

	/**
	 * Checks model settings and adjusts resource properties accordingly.
	 *
	 * If the model's policy or storage properties are set to 'false', they are removed
	 * from the resource.
	 *
	 * @param object $resource Resource object containing a model property.
	 * @return void
	 */
	protected function checkModelSettings(object $resource): void
	{
		$model = $resource->model;
		$policy = $model->policy ?? null;
		if ($policy === 'false')
		{
			unset($resource->policy);
		}

		if ($model->storage === 'false')
		{
			$resource->model->storage = '';
			unset($resource->storage);
		}
	}

	/**
	 * Adds a full resource.
	 *
	 * Prepares the model and checks its settings before creating the resource.
	 *
	 * @param object $resource Resource object containing model data.
	 * @return bool
	 */
	public function addResource(object $resource): bool
	{
		$this->setupModel($resource->model);
		$this->checkModelSettings($resource);
		return $this->generator->createResource($resource);
	}

	/**
	 * Adds an API resource.
	 *
	 * @param object $resource Resource object containing API data.
	 * @return bool
	 */
	public function addApi(object $resource): bool
	{
		return $this->generator->createResourceType('api', 'Api', $resource->api);
	}

	/**
	 * Adds a controller resource.
	 *
	 * @param object $resource Resource object containing controller data.
	 * @return bool
	 */
	public function addController(object $resource): bool
	{
		return $this->generator->createResourceType('controller', 'Controllers', $resource->controller);
	}

	/**
	 * Formats and sets up model fields.
	 *
	 * Replaces ":\n" with ":" and splits the fields into an array.
	 *
	 * @param object $model Model object to setup (passed by reference).
	 * @return void
	 */
	protected function setupModel(object &$model): void
	{
		$fields = str_replace(":\n", ":", $model->fields);
		$model->fields = explode(':', $fields);
	}

	/**
	 * Adds a model resource.
	 *
	 * Sets up the model fields and passes an optional namespace.
	 *
	 * @param object $resource Resource object containing model data and optional namespace.
	 * @return bool
	 */
	public function addModel(object $resource): bool
	{
		$model = $resource->model;
		$this->setupModel($model);
		return $this->generator->createResourceType('model', 'Models', $model);
	}

	/**
	 * Adds a storage resource.
	 *
	 * @param object $resource Resource object containing storage data.
	 * @return bool
	 */
	public function addStorage(object $resource): bool
	{
		return $this->generator->createResourceType('storage', 'Storage', $resource->storage);
	}

	/**
	 * Adds a policy resource.
	 *
	 * @param object $resource Resource object containing policy data.
	 * @return bool
	 */
	public function addPolicy(object $resource): bool
	{
		return $this->generator->createResourceType('policy', 'Policies', $resource->policy);
	}

	/**
	 * Sets up a table callback for execution.
	 *
	 * Wraps the provided callback code in an anonymous function that evaluates the builder code.
	 *
	 * @param object $table Table object to setup (passed by reference).
	 * @return void
	 */
	protected function setupTable(object &$table): void
	{
		$builder = $table->callBack;
		$table->callBack = function ($table) use ($builder)
		{
			eval($builder);
		};
	}

	/**
	 * Adds a table resource.
	 *
	 * Sets up the table callback before creation.
	 *
	 * @param object $resource Resource object containing table data.
	 * @return bool
	 */
	public function addTable(object $resource): bool
	{
		$table = $resource->table;
		$this->setupTable($table);
		return $this->generator->createTable($table);
	}

	/**
	 * Adds a migration resource.
	 *
	 * @param object $resource Resource object containing migration data.
	 * @return bool
	 */
	public function addMigration(object $resource): bool
	{
		return $this->generator->createMigration($resource->migration);
	}

	/**
	 * Adds a unit test resource.
	 *
	 * @param object $resource Resource object containing test data.
	 * @return bool
	 */
	public function addUnitTest(object $resource): bool
	{
		return $this->generator->createTest($resource->test);
	}
}