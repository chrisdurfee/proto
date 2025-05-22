<?php declare(strict_types=1);
namespace Modules\User\Api\Organization;

use Modules\User\Controllers\OrganizationController;

/**
 * Organization API Routes
 *
 * This will handle the API routes for the Organizations.
 */
router()
	->resource('user/organization', OrganizationController::class);