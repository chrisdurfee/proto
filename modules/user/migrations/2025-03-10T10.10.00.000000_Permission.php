<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

/**
 * Migration for the permissions table.
 */
class Permission extends Migration
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
        $this->create('permissions', function($table)
        {
            $table->id();
            $table->varchar('name', 100);
            $table->varchar('slug', 100);
            $table->text('description')->nullable();
            $table->varchar('module', 100)->nullable(); // Module this permission belongs to
            $table->createdAt();
            $table->updatedAt();

            // Indexes
            $table->index('name')->fields('name');
            $table->index('slug')->fields('slug');
            $table->index('module')->fields('module');
        });
    }

    /**
     * Reverts the migration.
     *
     * @return void
     */
    public function down(): void
    {
        $this->drop('permissions');
    }
}