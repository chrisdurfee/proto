<?php declare(strict_types=1);
namespace Modules\User\Controllers;

/**
 * HttpStatus Enum
 *
 * Defines standard HTTP status codes.
 */
enum HttpStatus: int
{
	case BAD_REQUEST = 400;
	case UNAUTHORIZED = 401;
	case FORBIDDEN = 403;
	case NOT_FOUND = 404;
	case TOO_MANY_REQUESTS = 429;
	case INTERNAL_SERVER_ERROR = 500;
}