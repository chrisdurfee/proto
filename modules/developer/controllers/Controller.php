<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Controllers\ApiController as BaseController;
use Modules\Developer\Auth\Policies\DeveloperPolicy;

/**
 * Controller
 *
 * This class will be the base class for all controllers.
 *
 * @package Modules\Developer\Controllers
 * @abstract
 */
abstract class Controller extends BaseController
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = DeveloperPolicy::class;
}