import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
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
 * ControllersPage
 *
 * This page documents Proto's controller system. Controllers are used to access models,
 * integrations, and other controllers. They can validate and normalize data, set responses,
 * and dispatch notifications. Child controllers inherit all CRUD methods from a parent controller,
 * reducing repetitive code.
 *
 * @returns {DocPage}
 */
export const ControllersPage = () =>
	DocPage(
		{
			title: 'Controllers',
			description: 'Learn how to use controllers in the Proto framework to manage data, responses, and notifications.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`A controller is a class used to access models, integrations, or other controllers.
					Controllers can validate data, normalize data, set responses, and dispatch email, text,
					and web push notifications. The parent controller provides built-in CRUD methods so that
					child controllers don't need to implement these methods themselves.`
				)
			]),

			// Naming
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming'),
				P({ class: 'text-muted-foreground' },
					`The name of a controller should always be singular and followed by "Controller".`
				),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Common\\Controllers;

use Common\\Models\\Example;
use Proto\\Controllers\\ModelController;

class ExampleController extends ModelController
{
}`
				)
			]),

			// Custom Methods
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Custom Methods'),
				P({ class: 'text-muted-foreground' },
					`Controllers can have custom methods to extend their functionality. For instance, a method
					to reset a password might be implemented as follows:`
				),
				CodeBlock(
`public function resetPassword(object $data): object
{
    // Create a model instance with the provided data
    $model = $this->model($data);

    // Process the password reset action via the model
    $result = $model->resetPassword();

    // Wrap the result in a response object for API compatibility
    return $this->response($result);
}`
				)
			]),

			// Controller Response
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Controller Response'),
				P({ class: 'text-muted-foreground' },
					`Controllers return response objects that encapsulate the response data,
					a success flag, and error messages. This standardized response is used by the API system.
					For example, a controller method might look like this:`
				),
				CodeBlock(
`public function getByName(string $name)
{
    // Retrieve a user by name using the model
    $result = $this->model()->getBy(['name' => $name]);

    if ($result === false) {
        return $this->error('No user was found');
    }

    return $this->response($result);
}`
				)
			]),

			// Pass-Through Responses
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Pass-Through Responses'),
				P({ class: 'text-muted-foreground' },
					`Controllers automatically wraps the result of any undeclared method call in a Response object. This makes it faster to add new
					resources without rewriting response logic.`
				)
			]),

			// Bypassing Pass-Through Responses
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Bypassing Pass-Through Responses'),
				P({ class: 'text-muted-foreground' },
					`To bypass the response wrapper and return the raw model result, call the undeclared controller
					method statically:`
				),
				CodeBlock(
`// Bypass response wrapping
$result = static::$controllerType::methodName();`
				)
			]),

			// Access Model
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Access Model'),
				P({ class: 'text-muted-foreground' },
					`Controllers can instantiate their associated model by invoking the \`model\` method with model data:`
				),
				CodeBlock(
`// Create a new model instance with provided data
$model = $this->model($data);`
				)
			]),

			// Access Model Storage
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Access Model Storage'),
				P({ class: 'text-muted-foreground' },
					`Models map database rows to camelCase properties, which is helpful when interacting with the model.
					To access raw data, use the storage property; to automatically convert results to camelCase,
					use the controller storage proxy:`
				),
				CodeBlock(
`// Access raw storage data (snake_case properties)
$data = $this->model()->storage->getBy(['name' => $name]);

// Access converted storage data (camelCase properties)
$data = $this->storage()->getBy(['name' => $name]);`
				)
			]),

			// Storage Find and Find All
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Storage Find and Find All'),
				P({ class: 'text-muted-foreground' },
					`Controllers can use the \`find\` and \`findAll\` methods to create ad-hoc, complex queries without
					adding new methods to the model's storage class. For example:`
				),
				CodeBlock(
`// Retrieve all rows matching a custom query
$this->storage()->findAll(function($sql, &$params) {
    $params[] = 'active';
    $sql->where('status = ?')
        ->orderBy('status DESC')
        ->groupBy('user_id');
});

// Retrieve a single row using a custom query
$this->storage()->find(function($sql, &$params) {
    $params[] = 'active';
    $sql->where('status = ?')
        ->limit(1);
});`
				)
			])
		]
	);

export default ControllersPage;