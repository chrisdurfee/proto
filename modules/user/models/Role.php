<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * Role
 *
 * This is the model class for table "roles".
 *
 * @package Modules\User\Models
 */
class Role extends Model
{
    /**
     * @var string|null $tableName
     */
    protected static ?string $tableName = 'roles';

    /**
     * @var string|null $alias
     */
    protected static ?string $alias = 'r';

    /**
     * @var array $fields
     */
    protected static array $fields = [
        'id',
        'name',
        'slug',
        'description',
        'permissions',
        'createdAt',
        'updatedAt'
    ];
}