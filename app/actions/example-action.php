<?php declare(strict_types=1);
namespace App\Actions;

use App\Models\User;

/**
 * ExampleAction
 *
 * This is an example action.
 */
class ExampleAction
{
    /**
     * This will run the action.
     *
     * @param mixed $data
     * @return mixed
     */
    public function handle(mixed $data): mixed
    {
        return User::create($data);
    }
}