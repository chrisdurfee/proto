<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

/**
 * UserStatus Enum
 *
 * This enum defines the possible user statuses.
 */
enum UserStatus: string
{
	case ONLINE = 'online';
	case OFFLINE = 'offline';
	case BUSY = 'busy';
	case AWAY = 'away';
}
