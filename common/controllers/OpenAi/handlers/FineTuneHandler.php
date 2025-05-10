<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

/**
 * FineTuneHandler
 *
 * This will handle the fine tune.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
class FineTuneHandler extends Handler
{
    /**
     * This will create a fine-tune.
     *
     * @param string $file
     * @return object
     */
    public function create(
        string $file
    ): object
    {
        $result = $this->api->createFineTune([
            'training_file' => $file
        ]);
        return decode($result);
    }

    /**
     * This will create a fine-tune.
     *
     * @return object
     */
    public function list(): object
    {
        $result = $this->api->listFineTunes();
        return decode($result);
    }

    /**
     * This will retrieve a fine-tune.
     *
     * @param string $file
     * @return object
     */
    public function retrieve(
        string $file
    ): object
    {
        $result = $this->api->retrieveFineTune($file);
        return decode($result);
    }

    /**
     * This will cancel a fine-tune.
     *
     * @param string $file
     * @return object
     */
    public function cancel(
        string $file
    ): object
    {
        $result = $this->api->cancelFineTune($file);
        return decode($result);
    }

    /**
     * This will retrieve a fine-tune.
     *
     * @param string $file
     * @return object
     */
    public function delete(
        string $file
    ): object
    {
        $result = $this->api->deleteFineTune($file);
        return decode($result);
    }
}