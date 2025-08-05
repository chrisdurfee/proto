<?php declare(strict_types=1);
namespace Proto\Http\Session;

use Proto\Http\Token;
use Proto\Http\Session\Models\UserSession;
use Proto\Utils\Format\JsonFormat;

/**
 * DatabaseSession
 *
 * Handles database-backed session management.
 *
 * @package Proto\Http\Session
 */
class DatabaseSession extends Adapter
{
	/**
	 * Session token.
	 *
	 * @var string|null
	 */
	protected static ?string $token = null;

	/**
	 * Session data.
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * User session model.
	 *
	 * @var UserSession
	 */
	protected UserSession $model;

	/**
	 * Initializes and starts a new session or resumes an existing one.
	 *
	 * @return static
	 */
	public static function init(): static
	{
		$instance = static::getInstance();
		$instance->start();
		return $instance;
	}

	/**
	 * Retrieves or generates the session token.
	 *
	 * @return string
	 */
	protected function getToken(): string
	{
		$cookie = Token::get();
		return $cookie ? $cookie : Token::create();
	}

	/**
	 * Sets up the session token.
	 *
	 * @return void
	 */
	protected function setupToken(): void
	{
		if (static::$token === null)
		{
			static::$token = $this->getToken();
		}
	}

	/**
	 * Retrieves the session ID.
	 *
	 * @return string
	 */
	public static function getId(): string
	{
		return static::$token ?? '';
	}

	/**
	 * Refreshes the session ID for security.
	 *
	 * @return string
	 */
	public function refreshId(): string
	{
		$old = static::$token ?? $this->getToken();
		$new = Token::create();
		static::$token = $new;

		$this->model->refreshId($old, $new);
		return $new;
	}

	/**
	 * Initializes the UserSession model.
	 *
	 * @return void
	 */
	protected function setupModel(): void
	{
		$model = UserSession::get(static::$token);
		if ($model)
		{
			$this->model = $model;
			return;
		}

		$model = new UserSession((object)['id' => static::$token]);
		$model->setup();

		$this->model = $model;
	}

	/**
	 * Loads session data from the database.
	 *
	 * @return void
	 */
	protected function loadData(): void
	{
		$data = $this->model->getData()->data ?? null;
		if ($data !== null)
		{
			$this->data = (array)JsonFormat::decode($data) ?: [];
		}
	}

	/**
	 * Saves the session data to the database.
	 *
	 * @return bool
	 */
	protected function saveData(): bool
	{
		$data = JsonFormat::encode($this->data);
		return $data !== false && $this->model->set('data', $data)->update();
	}

	/**
	 * Starts the session.
	 *
	 * @return void
	 */
	public function start(): void
	{
		if (static::$token !== null)
		{
			return;
		}

		$this->setupToken();
		$this->setupModel();
		$this->loadData();
	}

	/**
	 * Sets a session value.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $key, mixed $value): void
	{
		$this->data[$key] = $value;
		$this->saveData();
	}

	/**
	 * Gets a session value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		return $this->data[$key] ?? null;
	}

	/**
	 * Checks if a session key exists.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset(string $key): bool
	{
		return isset($this->data[$key]);
	}

	/**
	 * Unsets a session value.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void
	{
		unset($this->data[$key]);
		$this->saveData();
	}

	/**
	 * Destroys the session.
	 *
	 * @return bool
	 */
	public function destroy(): bool
	{
		Token::remove();
		$this->data = [];

		if ($this->model->delete())
		{
			static::$token = null;
			return true;
		}

		return false;
	}
}