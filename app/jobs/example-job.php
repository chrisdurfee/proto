<?php declare(strict_types=1);
namespace App\Jobs;

/**
 * ExampleJob
 *
 * This is an example job.
 *
 * @package App\Jobs
 */
class ExampleJob
{
    /**
     * This will run the job.
     *
     * @param mixed $data
     * @return mixed
     */
    public function handle(mixed $data): mixed
    {
        // do something
        return false;
    }
}