<?php declare(strict_types=1);
namespace App\Http\Requests;

use Proto\Http\ItemRequest;

/**
 * Requests
 *
 * This will setup the request validate rules.
 *
 * @package App\Http\Requests
 */
abstract class Request extends ItemRequest
{
    /**
     * This will setup the request validate rules.
     *
     * @param object|null $item
     * @return array
     */
    protected function rules(?object $item): array
    {
        return [];
    }
}