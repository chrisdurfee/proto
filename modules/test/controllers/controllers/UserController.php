<?php declare(strict_types=1);

namespace Common\Controllers;

use Common\Models\User;

/**
 * UserController
 * 
 * @package Common\Controllers
 */
class UserController extends Controller
{

		/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = User::class)
	{
		parent::__construct($modelClass);
	}
}