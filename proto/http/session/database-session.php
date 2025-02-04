<?php declare(strict_types=1);
namespace Proto\Http\Session;

use Proto\Http\Token;
use Proto\Models\UserSession;
use Proto\Utils\Format\JsonFormat;

/**
 * DatabaseSession
 *
 * This class handles database sessions.
 *
 * @package Proto\Http\Session
 */
class DatabaseSession extends Adapter
{
	/**
	 * Session token.
	 *
	 * @var string $token
	 */
	protected static string $token;

	/**
	 * Session data.
	 *
	 * @var array $data
	 */
	protected array $data = [];

	/**
	 * User session model.
	 *
	 * @var UserSession $model
	 */
	protected UserSession $model;

	/**
	 * Initializes and starts a new session or resumes an existing session.
	 *
	 * @return DatabaseSession
	 */
	public static function init(): DatabaseSession
	{
		$instance = static::getInstance();
		$instance->start();
		return $instance;
	}

	/**
	 * Sets the session token.
	 *
	 * @param string $token
	 * @return void
	 */
	protected function setToken(string $token): void
	{
		static::$token = $token;
	}

	/**
	 * Retrieves the session token from the cookie.
	 *
	 * @return string
	 */
	protected function getToken(): string
	{
		$cookie = Token::get();
		return !$cookie ? Token::create() : $cookie->getValue();
	}

	/**
	 * Sets up the session token.
	 *
	 * @return void
	 */
	public function setupToken(): void
	{
		$token = $this->getToken();
		$this->setToken($token);
	}

	/**
	 * Retrieves the session ID.
	 *
	 * @return string
	 */
	public static function getId(): string
	{
		return self::$token;
	}

	/**
	 * Sets up the UserSession model.
	 *
	 * @return void
	 */
	protected function setupModel(): void
	{
		$result = UserSession::get(self::$token);
		if ($result)
		{
			$this->model = $result;
			return;
		}

		$this->model = new UserSession((object)[
			'id' => self::$token
		]);
		$this->model->add();
	}

	/**
	 * Retrieves the session data from the table.
	 *
	 * @return void
	 */
	protected function getData(): void
	{
		$data = $this->model->getData()->data ?? false;
		if (!$data)
		{
			return;
		}

		$data = JsonFormat::decode($data);
		if (!$data)
		{
			return;
		}

		$this->data = (array)$data;
	}

	/**
	 * Updates the session data in the table.
	 *
	 * @return bool
	 */
	protected function updateData(): bool
	{
		$data = JsonFormat::encode((array)$this->data);
		if (!$data)
		{
			return false;
		}

		return $this->model->set('data', $data)->update();
	}

	/**
	 * Starts the session.
	 *
	 * @return void
	 */
	public function start(): void
	{
		if (isset(self::$token))
		{
			return;
		}

		$this->setupToken();
		$this->setupModel();
		$this->getData();
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
		$this->updateData();
	}

	/**
	 * Gets a session value.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		return $this->data[$key] ?? null;
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
		$this->updateData();
	}

	/**
	 * Destroys the session.
	 *
	 * @return bool
	 */
	public function destroy(): bool
	{
		Token::remove();
		return $this->model->delete();
	}
}