import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
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
							icon: null
						});
					}
				},
				children
			)
		]
	)
));

/**
 * ModelsPage
 *
 * This page explains how to create and use models in Proto.
 * Models are objects that map to database tables and provide a clean interface
 * for getting and setting data. They define columns, joins, table names, and aliases,
 * and they interact with storage layers to persist data without accessing the database directly.
 *
 * @returns {DocPage}
 */
export const ModelsPage = () =>
	DocPage(
		{
			title: 'Models',
			description: 'Learn how models in Proto map database tables to structured data and provide built-in CRUD functionality.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`A model is an object used to get and set data. Models outline the columns, joins, table name,
					and alias of the underlying database table. They act as data containers that map database records
					to a defined structure. Models use a storage layer (via storage proxies and objects) to interface with
					the database instead of accessing it directly.`
				),
				P({ class: 'text-muted-foreground' },
					`The parent model provides built-in CRUD methods (create, read, update, delete), so child models don't
					need to re-implement these methods.`
				)
			]),

			// Naming and Basic Setup
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming and Basic Setup'),
				P({ class: 'text-muted-foreground' },
					`Models should be named in singular form, matching the corresponding database table name.
					For example, a model for the "example" table should be named \`Example\`.`
				),
				CodeBlock(
`class Example extends Model
{

}`
				),
				P({ class: 'text-muted-foreground' },
					`Set $tableName to the exact name of the database table and $alias to a short alias for query building.`
				)
			]),

			// Model Pass-Through
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Model Pass-Through'),
				P({ class: 'text-muted-foreground' },
					`To reduce boilerplate, models can pass through undeclared methods directly to the storage layer.
					This means you only need to implement new logic in the resource API and storage class.
					By default, results are wrapped in a model object.`
				),
				P({ class: 'text-muted-foreground' },
					`Alternatively, you can bypass the wrapper by calling the method statically on the storage type:`
				),
				CodeBlock(
`// Bypass model pass-through:
$result = static::$storageType::methodName();`
				)
			]),

			// Fields and Blacklisting
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Fields and Blacklisting'),
				P({ class: 'text-muted-foreground' },
					`Models define the fields that map to database columns. Field names should use camelCase,
					and the model will only set values for declared fields or joins.`
				),
				P({ class: 'text-muted-foreground' },
					`To prevent sensitive data from being output, you can define a fields blacklist. For example:`
				),
				CodeBlock(
`protected static array $fieldsBlacklist = [
	'password'
];`
				),
				P({ class: 'text-muted-foreground' },
					`The model ID key defaults to "id" unless otherwise specified:`
				),
				CodeBlock(
`protected static string $idKeyName = 'id';`
				)
			]),

			// Field Formatting
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Field Formatting'),
				P({ class: 'text-muted-foreground' },
					`Models can augment or format fields before inserting or after retrieving data.
					The augment method allows you to modify data before it's stored,
					while the format method converts data before it's returned via the API.`
				),
				CodeBlock(
`protected static function augment(mixed $data = null): mixed
{
	if (!$data) {
		return $data;
	}
	// Example: Clean up phone numbers.
	$data->phoneNumber = Strings::cleanPhone($data->phoneNumber);
	return $data;
}

protected static function format(?object $data): ?object
{
	if (!$data) {
		return $data;
	}
	// Example: Mask sensitive fields.
	$data->ssn = Strings::mask($data->ssn, 4);
	return $data;
}`
				)
			]),

			// Model Joins: Original (Join Builder) vs. New (Lazy Relationships)
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Model Joins'),

				// Intro paragraph
				P({ class: 'text-muted-foreground' },
					`Proto supports two different ways to relate models:`
				),
				P({ class: 'text-muted-foreground' },
					`1. **Eager Joins** using the JoinBuilder (original style). You explicitly define table joins in the \`joins()\` method.` +
					`\n2. **Lazy Relationships**, inspired by Laravel, where you declare methods like \`hasMany\`, \`belongsTo\`, or \`hasOne\`.`
				),

				// Subsection: Eager Joins (JoinBuilder)
				H4({ class: 'text-base font-semibold mt-6' }, 'Eager Joins (JoinBuilder)'),
				P({ class: 'text-muted-foreground' },
					`Override the \`joins()\` method in your model to define SQL joins. The JoinBuilder API allows one-to-one, one-to-many, and many-to-many relationships.`
				),
				CodeBlock(
`protected static function joins($builder): void
{
	// Example: One-to-one join to Role
	Role::one($builder)                // JoinBuilder, default left join
		->on(['id', 'userId'])        // parent column, child column (camelCase)
		->fields('role');             // fields to pull from the joined table

	// Example: Many-to-many through a bridge table (user_roles → roles)
	UserRole::bridge($builder)        // specify bridge model
		->many(Role::class)           // join from bridge to Role
		->on(['roleId', 'id'])
		->fields(
			'id',
			'name',
			'slug',
			'description',
			'permissions'
		);

	// You can also join raw tables:
	$builder->left('permission', 'p')
		->on(['id', 'permissionId'])
		->fields('name');
}`
				),

				// Subsection: Lazy Relationships (hasMany / belongsTo / hasOne)
				H4({ class: 'text-base font-semibold mt-6' }, 'Lazy Relationships (hasMany, belongsTo, hasOne)'),
				P({ class: 'text-muted-foreground' },
					`Instead of defining SQL joins upfront, you can declare relationship methods in your model:
					\`hasMany\` for one-to-many, \`hasOne\` for one-to-one, and \`belongsTo\` for inverse relations.
					When you access \`$model->relationName\`, Proto will automatically issue a separate query to load the related data.`
				),
				P({ class: 'text-muted-foreground' },
					`Below are example models for \`User\`, \`Post\`, and \`Profile\`, demonstrating both styles:`
				),

				// Code example: User model (with both JoinBuilder and Lazy Relationship)
				CodeBlock(
`class User extends Model
{
	protected static ?string $tableName = 'users';
	protected static array $fields = ['id', 'name', 'email'];

	/**
	 * Eager join example: join user → role
	 */
	protected static function joins($builder): void
	{
		Role::one($builder)
			->on(['id', 'userId'])
			->fields('role');
	}

	/**
	 * Lazy one-to-many: User → Posts
	 */
	public function posts(): \\Proto\\Models\\Relations\\HasMany
	{
		return $this->hasMany(Post::class);
	}

	/**
	 * Lazy one-to-one: User → Profile
	 */
	public function profile(): \\Proto\\Models\\Relations\\HasOne
	{
		return $this->hasOne(Profile::class);
	}
}`
				),

				// Code example: Post model
				CodeBlock(
`class Post extends Model
{
	protected static ?string $tableName = 'posts';
	protected static array $fields = ['id', 'user_id', 'title', 'body'];

	/**
	 * Eager join example: join post → category
	 */
	protected static function joins($builder): void
	{
		Category::one($builder)
			->on(['categoryId', 'id'])
			->fields('name');
	}

	/**
	 * Lazy inverse: Post → User
	 */
	public function user(): \\Proto\\Models\\Relations\\BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}`
				),

				// Code example: Profile model
				CodeBlock(
`class Profile extends Model
{
	protected static ?string $tableName = 'profiles';
	protected static array $fields = ['id', 'user_id', 'bio', 'twitter_handle'];

	/**
	 * Lazy inverse: Profile → User
	 */
	public function user(): \\Proto\\Models\\Relations\\BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}`
				),

				P({ class: 'text-muted-foreground' },
					`**Usage examples:**`
				),
				CodeBlock(
`// Eagerly fetch users with roles in a single query:
$userWithRoles = User::get(1);

// Lazily load posts for a user:
$user = User::get(1);
// Issues: SELECT * FROM posts WHERE user_id = 1
$allPosts = $user->posts;

// Lazily load profile for a user:
$profile = $user->profile;

// Inverse lazy load from post to its author:
$post = Post::get(5);
// Issues: SELECT * FROM users WHERE id = $post->user_id
$author = $post->user;`
				)
			]),

			// Storage Type and Proxy
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Storage Type and Proxy'),
				P({ class: 'text-muted-foreground' },
					`Each model uses a default storage layer to perform CRUD operations.
					If custom actions are needed, create a custom storage class and override the $storageType property.`
				),
				CodeBlock(
`// Specify a custom storage class if needed:
protected static string $storageType = ExampleStorage::class;`
				),
				P({ class: 'text-muted-foreground' },
					`When a model is instantiated, it sets up a storage object accessible via the storage property.
					This proxy automatically passes storage method calls to the events system.`
				),
				CodeBlock(
`// Example of accessing storage directly:
$result = $this->storage->get(1);`
				)
			]),

			// Getting, Setting, and Persistence
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Getting, Setting, and Persistence'),
				P({ class: 'text-muted-foreground' },
					`Models use PHP's magic methods (__get and __set) to simplify data access.
					For example, you can get and set properties directly on a model instance.`
				),
				CodeBlock(
`$model = new Example();

// Get a property
$value = $model->key;

// Set a property
$model->key = $value;

// Batch set properties
$model->set((object)[
	'key' => $value,
	'name' => $name
]);`
				),
				P({ class: 'text-muted-foreground' },
					`The model also provides built-in CRUD methods. For instance, to add a new row:`
				),
				CodeBlock(
`$model = new Example();
$model->name = 'save';
$model->add();

// Or use static shortcut methods:
$result = Example::create((object)[
	'name' => 'save'
]);`
				)
			]),

			// Model Data
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Model Data'),
				P({ class: 'text-muted-foreground' },
					`The model data is stored in a nested structure, allowing for complex relationships and easy access to related data. If a user just wants the data without the model wrapper, they can use the data methods to return plain arrays or objects.`
				),
				CodeBlock(
`// Get the data as a plain object
$data = $model->getData();

// Read-only data object
$dataReadonly = $model->getReadOnlyData();`
				)
			]),

			// JSON Encoding
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'JSON Encoding'),
				P({ class: 'text-muted-foreground' },
					`When encoding a model as JSON, only public, non-blacklisted fields are included.
					This ensures that sensitive data is not inadvertently exposed.`
				)
			])
		]
	);

export default ModelsPage;