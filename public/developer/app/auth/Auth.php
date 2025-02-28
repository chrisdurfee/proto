<?php declare(strict_types=1);
namespace Developer\App\Auth;

use Proto\Base;
use Proto\Http\Response;

/**
 * Auth
 *
 * Handles authentication and authorization for the developer app.
 *
 * @package Developer\App\Auth
 */
class Auth
{
    /**
     * Validates the environment for the developer app.
     *
     * @return void
     */
    public static function validate()
    {
        $base = new Base();

        if (env('env') !== 'dev')
        {
            self::response('The env is not set to dev.');
        }
    }

    /**
     * This will return the response.
     *
     * @param string $message
     * @return void
     */
    protected static function response(string $message)
    {
        new Response((object)[
            'message' => $message,
            'success' => false
        ], 403);
    }
}
