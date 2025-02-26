<?php declare(strict_types=1);
namespace Modules\User\User\Api;

use Proto\Controllers\ModelController;

router()->resource('user', ModelController::class);