<?php declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Proto\Controllers\ResourceController as Controller;
use Modules\Auth\Models\Multifactor\UserAuthedDevice;

/**
 * UserAuthedDeviceController
 *
 * @package Modules\Auth\Controllers
 */
class UserAuthedDeviceController extends Controller
{
	/**
	 * Initializes the model class.
	 *
	 * @param string|null $model The model class reference using ::class.
	 */
	public function __construct(protected ?string $model = UserAuthedDevice::class)
	{
		parent::__construct();
	}
}