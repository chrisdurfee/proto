<?php declare(strict_types=1);
namespace Tests\Feature\Automation;

use Tests\Test;
use Proto\Config;
use Core\Models\Model as OldModel;
use App\Models\Model as NewModel;
use Core\Automation\Notifier;

/**
 * NotifierTestBase
 *
 * This is the base class for all notifier tests.
 *
 * @package Tests\Feature\Automation
 * @abstract
 */
abstract class NotifierTestBase extends Test
{
	/**
	 * This is the test time.
	 *
	 * @var string
	 */
	protected const TEST_TIME = '11:00:00';

	/**
	 * This is the test date.
	 *
	 * @var string
	 */
	protected const TEST_DATE = '2023-03-24';

	/**
	 * This is the class that will be tested.
	 *
	 * @var ?object $notifier // This is set to an object to prevent false flags from intelephense.
	 */
	protected ?Notifier $notifier;

	/**
	 * This is the model that will be used to
	 * create and/or delete test data.
	 *
	 * @var NewModel|OldModel|null $model // This is set to an object to prevent false flags from intelephense.
	 */
	protected NewModel|OldModel|null $model;

	/**
	 * This will be called before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		/**
		 * Setting the environment to 'dev' so
		 * that the service class can set the testing property.
		 */
		$config = Config::getInstance();
		$config->set('env', 'dev');

		$this->notifier = $this->getNotifier();
		$this->notifier->testing = true;

		$this->model = $this->getModel();
	}

	/**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	abstract protected function getNotifier(): Notifier;

	/**
	 * This will get the model.
	 *
	 * @return ?object
	 */
	abstract protected function getModel(): ?object;
}