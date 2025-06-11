import{a2 as a,a0 as t,G as e,s as n,Z as d,a3 as o,u as i,N as c,o as u}from"./index-DM3KSgk2.js";import{D as m}from"./doc-page-DoAFO_jW.js";import"./sidebar-menu-page-D_2zNFuZ-DKHVgHxo.js";const s=n((r,l)=>i({...r,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${r.class}`},[c({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(l[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:u.clipboard.checked})}},l)])),f=()=>m({title:"Controllers",description:"Learn how to use controllers in the Proto framework to manage data, responses, and notifications."},[a({class:"space-y-4"},[t({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},`A controller is a class used to access models, integrations, or other controllers.
					Controllers can validate data, normalize data, set responses, and dispatch email, text,
					and web push notifications. The parent controller provides built-in CRUD methods so that
					child controllers don't need to implement these methods themselves.`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Naming"),e({class:"text-muted-foreground"},'The name of a controller should always be singular and followed by "Controller".'),s(`<?php declare(strict_types=1);
namespace Common\\Controllers;

use Common\\Models\\Example;
use Proto\\Controllers\\ModelController;

class ExampleController extends ModelController
{
}`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Custom Methods"),e({class:"text-muted-foreground"},`Controllers can have custom methods to extend their functionality. For instance, a method
					to reset a password might be implemented as follows:`),s(`public function resetPassword(object $data): object
{
    // Create a model instance with the provided data
    $model = $this->model($data);

    // Process the password reset action via the model
    $result = $model->resetPassword();

    // Wrap the result in a response object for API compatibility
    return $this->response($result);
}`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Controller Response"),e({class:"text-muted-foreground"},`Controllers return response objects that encapsulate the response data,
					a success flag, and error messages. This standardized response is used by the API system.
					For example, a controller method might look like this:`),s(`public function getByName(string $name)
{
    // Retrieve a user by name using the model
    $result = $this->model()->getBy(['name' => $name]);
    if ($result === false)
	{
        return $this->error('No user was found');
    }

    return $this->response($result);
}`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Route Resource Controllers"),e({class:"text-muted-foreground"},`Resource controllers are used to manage resources in a RESTful way. The ResourceController class provides full CRUD functionality
					for a model. To create a resource controller, extend the ResourceController class and specify the model class in the constructor.`),e({class:"text-muted-foreground"},"These classes are used with the router and can be passed as a resource for a route. The controller method receives the request object when the route is called."),e({class:"text-muted-foreground"},"For example, a resource controller might look like this:"),s(`<?php declare(strict_types=1);
namespace Modules\\User\\Controllers;

use Modules\\User\\Models\\User;
use Proto\\Controllers\\ResourceController;
use Proto\\Http\\Router\\Request;

class UserController extends ResourceController
{
	public function __construct(
		protected ?string $model = User::class
	)
	{
		parent::__construct();
	}

	public function add(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data) || empty($data->username))
		{
			return $this->error('No item provided.');
		}

		$isTaken = User::isUsernameTaken($data->username ?? '');
		if ($isTaken)
		{
			return $this->error('Username is already taken.');
		}

		return parent::add($request);
	}
}`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Pass-Through Responses"),e({class:"text-muted-foreground"},`Controllers automatically wraps the result of any undeclared method call in a Response object. This makes it faster to add new
					resources without rewriting response logic.`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Bypassing Pass-Through Responses"),e({class:"text-muted-foreground"},`To bypass the response wrapper and return the raw model result, call the undeclared controller
					method statically:`),s(`// Bypass response wrapping
$result = static::$controllerType::methodName();`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Request Item"),e({class:"text-muted-foreground"},'The request item property sets the key name that will be used to get the item value from the request params. By default, the item is set to "item." This property can be overridden to set a custom key name to get the requested item.'),s(`// in a resource controller
protected string $item = 'example';`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Get Request Item"),e({class:"text-muted-foreground"},"This will get the requested item and decode the value. It will also clean the value."),s(`// in a resource controller
public function addUser(Request $request): object
{
    $user = $this->getRequestItem($request);
    // do something with the user
}`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Validation & Sanitize"),e({class:"text-muted-foreground"},"The validator class can validate and sanitize data. The validator accepts an object to validate and the validation settings to document how to validate the data. The validator will validate and sanitize the data by specified data type."),s(`$item = (object)[
    'id' => 1,
    'name' => 'name'
];

$validator = Validator::create($item, [
    'id' => 'int|required',
    'name' => 'string'
]);

if ($validator->isValid() === false)
{
    echo $validator->getMessage();
}`),e({class:"text-muted-foreground"},"The validator will sanitize and validate the data by specified data type. The supported data types include:"),d({class:"list-disc pl-6 space-y-1 text-muted-foreground"},[o("int"),o("float"),o("string"),o("email"),o("ip"),o("phone"),o("mac"),o("bool"),o("url"),o("domain")]),e({class:"text-muted-foreground"},"Fields marked as required will be required to submit the requested item."),s(`[
    'id' => 'int|required'
]`),e({class:"text-muted-foreground"},"A limit can be set to limit the length of a string. The limit can be set by using the :number rule."),s(`[
    'name' => 'string:255|required'
]`),t({class:"text-lg font-bold"},"Validate Method"),e({class:"text-muted-foreground"},'The validate method can be used to set the validating settings for adding and updating a row. The model "id" field is automatically set to "int" and "required" for the update method. The validate method can be overridden to set custom validation settings.'),s(`/**
 * Validates the request data.
 *
 * This method can be overridden in subclasses to provide specific validation logic.
 *
 * @return array An array of validation errors, if any.
 */
protected function validate(): array
{
	return [
		'id' => 'int|required',
		'name' => 'string:255|required',
		'email' => 'email|required',
		'phone' => 'phone',
		'status' => 'int'
	];
}`),t({class:"text-lg font-bold"},"Custom Validation"),e({class:"text-muted-foreground"},"The validateRules method can access a data object and an array or rules to validate the data."),s(`/**
 * Validates the request data.
 *
 * This method can be overridden in subclasses to provide specific validation logic.
 *
 * @return array An array of validation errors, if any.
 */
public function addData(Request $request): array
{
	$data = $this->getRequestItem($request);
	$this->validateRules($data, [
		'id' => 'int|required',
		'name' => 'string:255|required',
		'email' => 'email|required',
		'phone' => 'phone',
		'status' => 'int'
	]);

	// do something with the data
}`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Resource Id Parameter"),e({class:"text-muted-foreground"},"The resource controller provides a method to get the resource ID from the request."),s(`/**
 * Updates model item status.
 *
 * @param Request $request The request object.
 * @return object The response.
 */
public function updateStatus(Request $request): object
{
	$id = $this->getResourceId($request);
	$status = $request->input('status') ?? null;
	if ($id === null || $status === null)
	{
		return $this->error('The ID and status are required.');
	}

	return $this->response(
		$this->model((object) [
			'id' => $id,
			'status' => $status
		])->updateStatus()
	);
}`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Access Model"),e({class:"text-muted-foreground"},"Controllers can instantiate their associated model by invoking the `model` method with model data:"),s(`// Create a new model instance with provided data
$model = $this->model($data);`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Access Model Storage"),e({class:"text-muted-foreground"},`Models map database rows to camelCase properties, which is helpful when interacting with the model.
					To access raw data, use the storage property; to automatically convert results to camelCase,
					use the controller storage proxy:`),s(`// Access raw storage data (snake_case properties)
$data = $this->model()->storage->getBy(['name' => $name]);

// Access converted storage data (camelCase properties)
$data = $this->storage()->getBy(['name' => $name]);`)]),a({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Storage Find and Find All"),e({class:"text-muted-foreground"},"Controllers can use the `find` and `findAll` methods to create ad-hoc, complex queries without\n					adding new methods to the model's storage class. For example:"),s(`// Retrieve all rows matching a custom query
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
});`)])]);export{f as ControllersPage,f as default};
