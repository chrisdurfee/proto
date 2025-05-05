<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\NotificationPreference;

/**
 * NotificationPreferenceController
 *
 * This is the controller class for the model "NotificationPreference".
 *
 * @package Modules\User\Controllers
 */
class NotificationPreferenceController extends Controller
{
	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = NotificationPreference::class)
	{
		parent::__construct();
	}
}