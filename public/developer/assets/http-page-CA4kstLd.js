import{a2 as r,a0 as s,G as t,Z as i,a3 as e,s as n,u as l,N as u,o as d}from"./index-DM3KSgk2.js";import{D as c}from"./doc-page-DoAFO_jW.js";import"./sidebar-menu-page-D_2zNFuZ-DKHVgHxo.js";const h=n((a,o)=>l({...a,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${a.class}`},[u({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(o[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:d.clipboard.checked})}},o)])),g=()=>c({title:"HTTP System",description:"Learn how to work with the current HTTP request using Proto's request utilities."},[r({class:"space-y-4"},[s({class:"text-lg font-bold"},"Introduction"),t({class:"text-muted-foreground"},"Proto provides a static class, `Proto\\Http\\Request`, to access and manage\n					the current HTTP request. These methods allow you to retrieve information\n					like the path, full URL, headers, query parameters, and more.")]),r({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Available Methods"),t({class:"text-muted-foreground"},"Below is a list of common methods available on `Proto\\Http\\Request`:\n					they enable you to read data from the current HTTP request, including path,\n					IP address, HTTP method, headers, request body, and uploaded files."),i({class:"list-disc pl-6 space-y-1 text-muted-foreground"},[e("path() - Returns the current request path."),e("fullUrl() - Returns the full request URL, including query parameters."),e("fullUrlWithScheme() - Returns the full request URL, including query parameters and the scheme."),e("ip() - Retrieves the client's IP address."),e("method() - Returns the request's HTTP method (GET, POST, etc.)."),e("isMethod() - Checks if the request method matches a given string."),e("headers() - Returns all headers as an array or dictionary."),e("header() - Retrieves a specific header by name."),e("userAgent() - Returns the User-Agent header."),e("mac() - Returns the MAC address of the client."),e("input() - Gets a query or post parameter by key."),e("getInt() - Retrieves an integer parameter."),e("getBool() - Retrieves a boolean parameter."),e("json() - Returns the request body parsed as JSON (if applicable)."),e("sanitized() - Retrieves the sanitized request input."),e("raw() - Retrieves the raw request body as a string."),e("decodeUrl() - Decodes a URL-encoded string."),e("has() - Checks if a given input parameter is present."),e("all() - Returns all input data (query, post, etc.) as an object."),e("body() - Retrieves the body content in the most suitable format."),e("file() - Retrieves a single uploaded file by name."),e("files() - Returns all uploaded files."),e("params() - This is added by the router. This will return the parameters defined in the route.")])]),r({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Example Usage"),t({class:"text-muted-foreground"},`Here's a quick example showing how you might use some of these methods to
					read the current path, query parameters, and headers in a controller:`),h(`<?php declare(strict_types=1);

namespace Modules\\Example\\Controllers;

use Proto\\Http\\Request;
use Proto\\Controllers\\ResourceController;

class ExampleController extends ResourceController
{
	/**
	 * Show details of the current request.
	 *
	 * @param Request $request
	 * @return void
	 */
	public function showDetails(Request $request): void
	{
		// Get the current request path
		$path = $request->path();

		// Check if the request method is POST
		if ($request->isMethod('POST'))
		{
			// Get a form field value
			$username = $request->input('username');
			// Retrieve an integer parameter
			$age = $request->getInt('age', 0);
		}

		// Get the client IP
		$clientIp = $request->ip();

		// Get a specific header
		$authHeader = $request->header('Authorization');

		// ...
	}
}`),t({class:"text-muted-foreground"},`By using these methods, you can conveniently gather all the data
					you need from the request object without manual parsing or third-party libraries.`)])]);export{g as HttpPage,g as default};
