<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the role_permissions pivot table.
 */
class RolePermission extends Migration
{
    /**
     * @var string $connection The database connection name.
     */
    protected string $connection = 'default';

    /**
     * Runs the migration.
     *
     * @return void
     */
    public function up(): void
    {
        $this->create('role_permissions', function($table)
        {
            $table->id();
            $table->int('role_id', 20);
            $table->int('permission_id', 20);
            $table->createdAt();
            $table->updatedAt();

            // Indexes
            $table->index('role_id')->fields('role_id');
            $table->index('permission_id')->fields('permission_id');
            $table->index('role_permission')->fields('role_id', 'permission_id')->unique();

            // Foreign keys
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverts the migration.
     *
     * @return void
     */
    public function down(): void
    {
        $this->drop('role_permissions');
    }
}