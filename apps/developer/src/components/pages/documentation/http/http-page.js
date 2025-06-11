import { Code, H4, Li, P, Pre, Section, Ul } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { DocPage } from "../../doc-page.js";

/**
 * CodeBlock
 *
 * Creates a code block with copy-to-clipboard functionality.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const CodeBlock = Atom((props, children) => (
	Pre(
		{
			...props,
			class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${props.class}`
		},
		[
			Code(
				{
					class: 'font-mono flex-auto text-sm text-wrap',
					click: () => {
						navigator.clipboard.writeText(children[0].textContent);

						// @ts-ignore
						app.notify({
							title: "Code copied",
							description: "The code has been copied to your clipboard.",
							icon: Icons.clipboard.checked
						});
					}
				},
				children
			)
		]
	)
));

/**
 * HttpPage
 *
 * This page documents how to access and manipulate the current HTTP request in Proto
 * using the static methods of Proto\Http\Request.
 *
 * @returns {DocPage}
 */
export const HttpPage = () =>
	DocPage(
		{
			title: 'HTTP System',
			description: 'Learn how to work with the current HTTP request using Proto\'s request utilities.'
		},
		[
			// Introduction
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Introduction'),
				P(
					{ class: 'text-muted-foreground' },
					`Proto provides a static class, \`Proto\\Http\\Request\`, to access and manage
					the current HTTP request. These methods allow you to retrieve information
					like the path, full URL, headers, query parameters, and more.`
				)
			]),

			// Available Methods
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Available Methods'),
				P(
					{ class: 'text-muted-foreground' },
					`Below is a list of common methods available on \`Proto\\Http\\Request\`:
					they enable you to read data from the current HTTP request, including path,
					IP address, HTTP method, headers, request body, and uploaded files.`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("path() - Returns the current request path."),
					Li("fullUrl() - Returns the full request URL, including query parameters."),
					Li("fullUrlWithScheme() - Returns the full request URL, including query parameters and the scheme."),
					Li("ip() - Retrieves the client\'s IP address."),
					Li("method() - Returns the request\'s HTTP method (GET, POST, etc.)."),
					Li("isMethod() - Checks if the request method matches a given string."),
					Li("headers() - Returns all headers as an array or dictionary."),
					Li("header() - Retrieves a specific header by name."),
					Li("userAgent() - Returns the User-Agent header."),
					Li("mac() - Returns the MAC address of the client."),
					Li("input() - Gets a query or post parameter by key."),
					Li("getInt() - Retrieves an integer parameter."),
					Li("getBool() - Retrieves a boolean parameter."),
					Li("json() - Returns the request body parsed as JSON (if applicable)."),
					Li("sanitized() - Retrieves the sanitized request input."),
					Li("raw() - Retrieves the raw request body as a string."),
					Li("decodeUrl() - Decodes a URL-encoded string."),
					Li("has() - Checks if a given input parameter is present."),
					Li("all() - Returns all input data (query, post, etc.) as an object."),
					Li("body() - Retrieves the body content in the most suitable format."),
					Li("file() - Retrieves a single uploaded file by name."),
					Li("files() - Returns all uploaded files."),
					Li("params() - This is added by the router. This will return the parameters defined in the route."),
				])
			]),

			// Example Usage
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Example Usage'),
				P(
					{ class: 'text-muted-foreground' },
					`Here\'s a quick example showing how you might use some of these methods to
					read the current path, query parameters, and headers in a controller:`
				),
				CodeBlock(
`<?php declare(strict_types=1);

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
}`
				),
				P(
					{ class: 'text-muted-foreground' },
					`By using these methods, you can conveniently gather all the data
					you need from the request object without manual parsing or third-party libraries.`
				)
			])
		]
	);

export default HttpPage;