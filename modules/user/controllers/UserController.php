<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Models\User;
use Proto\Controllers\ModelController;

/*
 * UserController
 *
 * This is the controller class for the model "User".
 *
 * @package Modules\User\Controllers
 */
class UserController extends ModelController
{
	/**
	 * This will setup the model class.
	 *
	 * @param string|null $modelClass by using the magic constant ::class
	 */
	public function __construct(
		protected ?string $modelClass = User::class
	)
	{
		parent::__construct($modelClass);
	}

	/**
	 * Adds a model entry.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public function add(object $data): object
	{
		$isTaken = User::isUsernameTaken($data->username ?? '');
		if ($isTaken)
		{
			return $this->error('Username is already taken.');
		}

		return parent::add($data);
	}
}
