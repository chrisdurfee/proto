<?php declare(strict_types=1);
namespace Modules\User\Services;

use Modules\User\Models\Permission;
use Modules\User\Models\Role;
use Modules\User\Models\RolePermission;
use Modules\User\Models\User;
use Modules\User\Models\UserRole;

/**
 * PermissionService
 *
 * Service for handling roles and permissions.
 *
 * @package Modules\User\Services
 */
class PermissionService
{
    /**
     * Check if a user has a specific permission.
     *
     * @param int $userId The user ID to check permissions for
     * @param string $permissionSlug The permission slug to check
     * @return bool True if the user has the permission, false otherwise
     */
    public static function hasPermission(int $userId, string $permissionSlug): bool
    {
        // Get all roles for the user
        $roles = UserRole::select()
            ->join('roles', 'r', function($join) {
                $join->on(['role_id', 'id']);
            })
            ->where('user_id', $userId)
            ->get();

        if (empty($roles)) {
            return false;
        }

        // Check if any of the roles has the required permission
        foreach ($roles as $role) {
            // First check if we have permissions stored in the permissions JSON column
            if (!empty($role->permissions)) {
                $permissions = json_decode($role->permissions, true);
                if (is_array($permissions) && in_array($permissionSlug, $permissions)) {
                    return true;
                }
            }

            // If not found in the JSON column, check the role_permissions table
            $hasPermission = RolePermission::select()
                ->join('permissions', 'p', function($join) {
                    $join->on(['permission_id', 'id']);
                })
                ->where('role_id', $role->id)
                ->where('p.slug', $permissionSlug)
                ->exists();

            if ($hasPermission) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign a role to a user.
     *
     * @param int $userId The user ID
     * @param int $roleId The role ID
     * @return bool True if successful, false otherwise
     */
    public static function assignRoleToUser(int $userId, int $roleId): bool
    {
        // Check if the relationship already exists
        $exists = UserRole::select()
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->exists();

        if ($exists) {
            return true; // Already assigned
        }

        // Create the new relationship
        $userRole = new UserRole();
        $userRole->userId = $userId;
        $userRole->roleId = $roleId;

        return $userRole->save();
    }

    /**
     * Remove a role from a user.
     *
     * @param int $userId The user ID
     * @param int $roleId The role ID
     * @return bool True if successful, false otherwise
     */
    public static function removeRoleFromUser(int $userId, int $roleId): bool
    {
        return UserRole::delete()
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->execute();
    }

    /**
     * Assign a permission to a role.
     *
     * @param int $roleId The role ID
     * @param int $permissionId The permission ID
     * @return bool True if successful, false otherwise
     */
    public static function assignPermissionToRole(int $roleId, int $permissionId): bool
    {
        // Check if the relationship already exists
        $exists = RolePermission::select()
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if ($exists) {
            return true; // Already assigned
        }

        // Create the new relationship
        $rolePermission = new RolePermission();
        $rolePermission->roleId = $roleId;
        $rolePermission->permissionId = $permissionId;

        return $rolePermission->save();
    }

    /**
     * Remove a permission from a role.
     *
     * @param int $roleId The role ID
     * @param int $permissionId The permission ID
     * @return bool True if successful, false otherwise
     */
    public static function removePermissionFromRole(int $roleId, int $permissionId): bool
    {
        return RolePermission::delete()
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->execute();
    }

    /**
     * Get all permissions for a role.
     *
     * @param int $roleId The role ID
     * @return array The permissions associated with the role
     */
    public static function getRolePermissions(int $roleId): array
    {
        return Permission::select()
            ->join('role_permissions', 'rp', function($join) {
                $join->on(['id', 'permission_id']);
            })
            ->where('rp.role_id', $roleId)
            ->get();
    }

    /**
     * Get all roles for a user.
     *
     * @param int $userId The user ID
     * @return array The roles associated with the user
     */
    public static function getUserRoles(int $userId): array
    {
        return Role::select()
            ->join('user_roles', 'ur', function($join) {
                $join->on(['id', 'role_id']);
            })
            ->where('ur.user_id', $userId)
            ->get();
    }

    /**
     * Get all permissions for a user through their roles.
     *
     * @param int $userId The user ID
     * @return array The permissions associated with the user through their roles
     */
    public static function getUserPermissions(int $userId): array
    {
        // Get all roles for the user
        $roles = self::getUserRoles($userId);

        if (empty($roles)) {
            return [];
        }

        $roleIds = array_map(function($role) {
            return $role->id;
        }, $roles);

        // Get all permissions for these roles
        return Permission::select()
            ->join('role_permissions', 'rp', function($join) {
                $join->on(['id', 'permission_id']);
            })
            ->whereIn('rp.role_id', $roleIds)
            ->get();
    }
}