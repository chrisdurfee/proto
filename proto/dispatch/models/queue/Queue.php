<?php declare(strict_types=1);
namespace Proto\Models\Queue;

use Proto\Models\Model;

/**
 * Queue
 *
 * This will be the base class for all queue models.
 *
 * @package Proto\Models\Queue
 */
abstract class Queue extends Model
{
    protected $passModel = true;
}