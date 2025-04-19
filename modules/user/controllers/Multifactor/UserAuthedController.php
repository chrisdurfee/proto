<?php declare(strict_types=1);
namespace Modules\User\Controllers\Multifactor;

use Modules\User\Models\Multifactor\UserAuthedConnection;
use Modules\User\Models\Multifactor\UserAuthedDevice;
use Modules\User\Models\Multifactor\UserAuthedLocation;
use Proto\Controllers\ModelController as Controller;

/**
 * UserAuthedController
 *
 * This controller handles CRUD operations for the UserAuthedConnection model.
 *
 * @package Modules\User\Controllers\Methods
 */
class UserAuthedController extends Controller
{
	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = UserAuthedConnection::class)
	{
		parent::__construct($modelClass);
	}

	/**
	 * Checks if the connection is authenticated.
	 *
	 * @param string $userId The user ID.
	 * @param string $guid The GUID.
	 * @param string $ipAddress The IP address.
	 * @return bool True if authenticated, false otherwise.
	 */
	public function isAuthed(
		string $userId,
		string $guid,
		string $ipAddress
	): bool
	{
		$permitted = $this->isPermitted($userId, $guid, $ipAddress);
		if (!$permitted)
		{
			return $permitted;
		}

		$this->model()->updateAccessedAt($userId, $guid, $ipAddress);

		$model = new UserAuthedDevice();
		$model->updateAccessedAt($userId, $guid);
		return $permitted;
	}

	/**
	 * Checks if the connection is permitted.
	 *
	 * @param string $userId The user ID.
	 * @param string $guid The GUID.
	 * @param string $ipAddress The IP address.
	 * @return bool True if permitted, false otherwise.
	 */
	public function isPermitted(
        string $userId,
        string $guid,
        string $ipAddress
    ): bool
    {
        return $this->model()->isAuthed($userId, $guid, $ipAddress);
    }

	/**
	 * This will add or update the device for the user.
	 *
	 * @param object $data
	 * @return bool
	 */
	protected function setupDevice(object $data): bool
	{
		return UserAuthedDevice::put($data);
	}

	/**
	 * This will add or update the location for the user.
	 *
	 * @param string $ipAddress
	 * @return bool
	 */
	protected function setupLocation(string $ipAddress): bool
	{
        $result = $this->getLocation($ipAddress);
        if(empty($result))
        {
            return false;
        }

		return UserAuthedLocation::put($result);
	}
}
