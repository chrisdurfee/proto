<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * RolePermission
 *
 * This is the model class for the pivot table "role_permissions".
 *
 * @package Modules\User\Models
 */
class RolePermission extends Model
{
    /**
     * @var string|null $tableName
     */
    protected static ?string $tableName = 'role_permissions';

    /**
     * @var string|null $alias
     */
    protected static ?string $alias = 'rp';

    /**
     * @var array $fields
     */
    protected static array $fields = [
        'id',
        'roleId',
        'permissionId',
        'createdAt',
        'updatedAt'
    ];
}