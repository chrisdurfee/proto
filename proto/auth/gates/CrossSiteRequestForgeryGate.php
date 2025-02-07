<?php declare(strict_types=1);
namespace Proto\Auth\Gates;

use Proto\Http\Request;

/**
 * CrossSiteRequestForgeryGate
 *
 * This will create a CSRF gate.
 *
 * @package Proto\Auth
 */
class CrossSiteRequestForgeryGate extends Gate
{
    /**
     * This is the session key name.
     */
    const CSRF_TOKEN = 'csrf-token';

    /**
     * This is the token length.
     */
    const TOKEN_LENGTH = 128;

    /**
     * This will create the token.
     *
     * @return string
     */
    protected function createToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * This will create the request token.
     *
     * @return string
     */
    public function setToken(): string
    {
        // this will check to resume previous token
        $token = $this->getToken();
        if (isset($token))
        {
            return $token;
        }

        $token = $this->createToken();
        $this->set(self::CSRF_TOKEN, $token);
        return $token;
    }

    /**
     * This will get the request token.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->get(self::CSRF_TOKEN);
    }

    /**
     * This will reset the token.
     *
     * @return void
     */
    protected function reset(): void
    {
        $this->set(self::CSRF_TOKEN, null);
    }

    /**
     * This will check if the token is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $csrfHeader = Request::header(self::CSRF_TOKEN);
        if (empty($csrfHeader))
        {
            return false;
        }

        return $this->validateToken($csrfHeader);
    }

    /**
     * This will validate
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        $storedToken = $this->get(self::CSRF_TOKEN);
        if (empty($storedToken))
        {
            return true;
        }

        return ($storedToken === $token);
    }
}