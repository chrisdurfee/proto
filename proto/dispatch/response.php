<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Response
 *
 * This will create a response object.
 *
 * @package Proto\Dispatch
 */
class Response
{
    /**
     * @var string $sent
     */
    public string $sent = 'no';

    /**
     * @var bool $error
     */
    public bool $error;

    /**
     * @var bool $success
     */
    public bool $success;

    /**
     * @var string $message
     */
    public string $message;

    /**
     * @var mixed $data
     */
    protected $data;

    /**
     * This will create a response.
     *
     * @param bool $error
     * @param string $message
     * @return void
     */
    public function __construct(bool $error = false, string $message = '')
    {
        $this->error = $error;
        $this->success = !$error; // If error is true, success is false and vice versa.
        $this->message = $message;
    }

    /**
     * This will create a response.
     *
     * @param bool $error
     * @param string $message
     * @param mixed $data
     * @return Response
     */
    public static function create(bool $error = false, string $message = '', $data = null): Response
    {
        $result = new static($error, $message);
        $result->sent = ($error === true) ? 'no' : 'yes';

        if ($data)
        {
            $result->setData($data);
        }

        return $result;
    }

    /**
     * This will set the data.
     *
     * @param mixed $data
     * @return void
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * This will get the data.
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}