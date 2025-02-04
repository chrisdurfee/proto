<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Utils\Format\JsonFormat as Formatter;

/**
 * Response
 *
 * Represents an HTTP router response.
 *
 * @package Proto\Http\Router
 */
class Response
{
    /**
     * Content type of the response.
     *
     * @var string $contentType
     */
    protected string $contentType = 'application/json';

    /**
     * HTTP response codes and their messages.
     *
     * @var array $responseCodes
     */
    protected static array $responseCodes = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No content',
        205 => 'Reset Content',
        300 => 'Multiple Choice',
        301 => 'Moved Permamently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'HTTPS Required',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error'
    ];

    /**
     * Sets the content type for the response.
     *
     * @param string $contentType
     * @return self
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Gets the response message for a given response code.
     *
     * @param int $code
     * @return string
     */
    protected function getResponseMessage(int $code): string
    {
        return self::$responseCodes[$code] ?? 'Not Found';
    }

    /**
     * Sets the headers for the response.
     *
     * @param int $code
     * @param string|null $contentType
     * @return self
     */
    public function headers(int $code, string $contentType = null): self
    {
        $contentType = $contentType ?? $this->contentType;

        $message = $this->getResponseMessage($code);

        try
        {
            header("HTTP/2.0 {$code} {$message}");
            header("Content-Type: {$contentType}; charset=utf-8");
        }
        catch (\Exception $e)
        {

        }

        return $this;
    }

    /**
     * Renders the response headers based on the response code.
     *
     * @param int $code
     * @param string|null $contentType
     * @return self
     */
    public function render(int $code, string $contentType = null): self
    {
        $contentType = $contentType ?? $this->contentType;

        $this->headers($code, $contentType);
        return $this;
    }

    /**
     * JSON encodes and outputs the data.
     *
     * @param mixed $data
     * @return void
     */
    public function json(mixed $data = null): void
    {
        if (!isset($data))
        {
            return;
        }

        Formatter::encodeAndRender($data);
    }
}