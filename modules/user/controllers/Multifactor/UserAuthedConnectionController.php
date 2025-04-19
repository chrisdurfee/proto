<?php declare(strict_types=1);
namespace Modules\User\Controllers\Multifactor;

use Modules\User\Models\Multifactor\UserAuthedConnection;
use Modules\User\Models\Multifactor\UserAuthedDevice;
use Modules\User\Models\Multifactor\UserAuthedLocation;
use Modules\User\Integrations\Location\IpApi;
use Proto\Controllers\ModelController as Controller;

/**
 * UserAuthedConnectionController
 *
 * This controller handles CRUD operations for the UserAuthedConnection model.
 *
 * @package Modules\User\Controllers\Methods
 */
class UserAuthedConnectionController extends Controller
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
	 * @return int|null
	 */
	protected function setupDevice(object $data): ?int
	{
		$model = new UserAuthedDevice($data);
		$result = $model->setup();
		return ($result)? $model->id : null;
	}

	/**
	 * This will get the ip address location.
	 *
	 * @param string $ipAddress
	 * @return object|null
	 */
	protected function getLocation(string $ipAddress): ?object
    {
        $api = new IpApi();
        $result = $api->getLocation($ipAddress);
        if (!$result || isset($result->error))
        {
            return null;
        }

        return (object)[
            'city' => $result->city,
            'region' => $result->region,
            'regionCode' => $result->region_code,
            'country' => $result->country,
            'countryCode' => $result->country_code,
            'postal' => $result->postal,
            'latitude' => $result->latitude,
            'longitude' => $result->longitude,
            'position' => $result->latitude . ' ' . $result->longitude,
            'timezone' => $result->timezone
        ];
    }

	/**
	 * This will add or update the location for the user.
	 *
	 * @param string $ipAddress
	 * @return int|null
	 */
	protected function setupLocation(string $ipAddress): ?int
	{
        $result = $this->getLocation($ipAddress);
        if (empty($result))
        {
            return null;
        }

		$model = new UserAuthedLocation($result);
		$result = $model->setup();
		return ($result) ? $model->id : null;
	}

	/**
	 * This will setup the user authed connection.
	 *
	 * @param object $data
	 * @return object
	 */
	public function setup(object $data): object
	{
		/**
		 * This will setup the device and location for the user
		 * before creating the connection.
		 */
		$deviceId = $this->setupDevice($data->device);
		$locationId = $this->setupLocation($data->ipAddress);

		if ($deviceId === null)
		{
			return $this->error('Unable to setup device');
		}

		$data->deviceId = $deviceId;
		$data->locationId = $locationId ?? null;
		return parent::setup($data);
	}
}
