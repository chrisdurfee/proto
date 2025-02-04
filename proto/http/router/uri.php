<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * Uri
 *
 * Uri is an abstract class responsible for managing
 * routes and their associated functionality.
 *
 * @package Proto\Http\Router
 * @abstract
 */
abstract class Uri
{
    use MiddlewareTrait;

    /**
     * @var object|null $params The route parameters.
     */
    protected ?object $params = null;

    /**
     * @var array $paramNames The names of the route parameters.
     */
    protected array $paramNames = [];

    /**
     * @var string $uri The route URI.
     */
    protected string $uri;

    /**
     * @var string $method The HTTP method associated with the route.
     */
    protected string $method;

    /**
     * @var string $uriQuery The URI pattern used for matching against request URIs.
     */
    protected string $uriQuery = '';

    /**
     * This will set up the route.
     *
     * @param string $uri The route URI.
     * @return void
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;

        $this->setupParamKeys($uri);
        $this->setupUriQuery($uri);
    }

    /**
     * Set up the URI query pattern for matching against request URIs.
     *
     * @param string $uri The route URI.
     * @return void
     */
    protected function setupUriQuery(string $uri = ''): void
    {
        if (empty($uri))
        {
            $this->uriQuery = '/.*/';
            return;
        }

        $uriQuery = '/^';
        // escape slashes
        $uriQuery .= preg_replace('/\//', '\/', $uri);
        // add slash before optional param
        $uriQuery = preg_replace('/(?:(\\*\/):[^\/(]*?\?)/', '(?:$|\/)', $uriQuery);
        // add slash after optional param
        $uriQuery = preg_replace('/(\?\\\\\/+\*?)/', '?\/*', $uriQuery);

        // params
        $paramCallBack = function($matches)
        {
            if(strpos($matches[0], '.') === false)
            {
                return '([^\/|?]+)';
            }

            return '([^\/|?]+.*)';
        };

        $uriQuery = preg_replace_callback('/(:[^\/?&$\\\]+)/', $paramCallBack, $uriQuery);

        // wild card to allow all
        $uriQuery = preg_replace('/(?<!\.)(\*)/', '.*', $uriQuery);

        // this will only add the end string char if the uri has no wild cards
        $uriQuery .= (strpos($uriQuery, '*') === false)? '$/' : '/';

        $this->uriQuery = $uriQuery;
    }

    /**
     * Set up the names of the route parameters.
     *
     * @param string $uri The route URI.
     * @return void
     */
    protected function setupParamKeys(string $uri): void
    {
        if (empty($uri))
        {
            return;
        }

        $keys = [];
        $uri = str_replace('[\*?]', '', $uri);

        // this will get the param names
        preg_match_all('/(?::(.[^.\/?&($]+)\?*)/', $uri, $matches);
        if (!empty($matches))
        {
            foreach($matches[1] as $match)
            {
                array_push($keys, $match);
            }
        }

        $this->paramNames = $keys;
    }

    /**
     * Set the route parameters based on the provided matches.
     *
     * @param array $matches The parameter matches from the URI.
     * @return void
     */
    protected function setParams(array $matches): void
    {
        if (!empty($matches))
        {
            $names = $this->paramNames;
            if (empty($names) || count($names) < 1)
            {
                return;
            }

            array_shift($matches);

            $params = (object)[];
            for ($i = 0, $length = count($names); $i < $length; $i++)
            {
                $value = $matches[$i] ?? null;
                if ($value !== null)
                {
                    $params->{$names[$i]} = $value;
                }
            }
            $this->params = $params;
        }
    }

    /**
     * Get the route parameters.
     *
     * @return object|null
     */
    protected function getParams(): ?object
    {
        return $this->params;
    }

    /**
     * Initialize the route, applying any middleware and handling the request.
     *
     * @param array $globalMiddleWare The global middleware to apply.
     * @param string $request The request URI.
     * @return mixed
     */
    public function initialize(array $globalMiddleWare, string $request): mixed
    {
        $middleware = array_merge($globalMiddleWare, $this->middleware);
        if (count($middleware) < 1)
        {
            return $this->activate($request);
        }

        /**
         * This is the first callback that will call
         * the route activate to be passed to the
         * first middleware.
         */
        $self = $this;
        $next = function(string $request) use($self)
        {
            return $self->activate($request);
        };

        /**
         * This will reverse the array to se the last
         * middleware to call the route activate.
         */
        $middleware = array_reverse($middleware);
        foreach ($middleware as $item)
        {
            $next = $this->setupMiddlewareCallback($item, $next);
        }

        return $next($request);
    }

    /**
     * Activate the route, performing the associated action.
     *
     * @param string $request The request URI.
     */
    abstract public function activate(string $request): mixed;

    /**
     * Check if the given method matches the route's method.
     *
     * @param string $method The HTTP method to check.
     * @return bool
     */
    protected function checkMethod(string $method): bool
    {
        if (isset($this->method) === false)
        {
            return true;
        }

        $uriMethod = strtolower($this->method);
        if ($uriMethod === 'all')
        {
            return true;
        }

        $method = strtolower($method);
        return ($uriMethod === $method);
    }

    /**
     * Check if a given URI and method match the route's URI and method.
     *
     * @param string $uri The request URI.
     * @param string $method The HTTP method.
     * @return bool
     */
    public function match(string $uri, string $method): bool
    {
        if ($this->checkMethod($method) === false)
        {
            return false;
        }

        $result = preg_match($this->uriQuery, $uri, $matches);
        if ($result)
        {
            $this->setParams($matches);
        }
        return ($result === 1);
    }
}