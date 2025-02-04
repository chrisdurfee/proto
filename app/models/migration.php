<?php
namespace App\Models;

use App\Storage\MigrationStorage;

class Migration extends Model
{
    protected static $tableName = 'migrations';

    protected static $fields = [
        'id',
		'createdAt',
		'migration',
		'groupId'
    ];

    protected $passModel = true;
    protected static $storageType = MigrationStorage::class;

}