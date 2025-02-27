<?php declare(strict_types=1);
namespace Modules\User\User\Api;

use Modules\User\Controllers\UserController;

router()->resource('user', UserController::class);