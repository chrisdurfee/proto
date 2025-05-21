<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\WebPushUser;

/**
 * WebPushUserController
 *
 * @package Modules\User\Controllers
 */
class WebPushUserController extends Controller
{
	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = WebPushUser::class)
	{
		parent::__construct();
	}
}