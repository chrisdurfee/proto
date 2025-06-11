import{a2 as o,a0 as t,G as e,s as r,u as n,N as i}from"./index-DD5ZlTX4.js";import{D as d}from"./doc-page-DFkqm2Y0.js";import"./sidebar-menu-page-D_2zNFuZ-DJNCgvAz.js";const s=r((a,l)=>n({...a,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					  rounded-lg border bg-muted whitespace-break-spaces
					  break-all cursor-pointer mt-4 ${a.class}`},[i({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(l[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:null})}},l)])),p=()=>d({title:"Models",description:"Learn how models in Proto map database tables to structured data and provide built-in CRUD functionality."},[o({class:"space-y-4"},[t({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},`A model is an object used to get and set data. Models outline the columns, joins, table name,
					and alias of the underlying database table. They act as data containers that map database records
					to a defined structure. Models use a storage layer (via storage proxies and objects) to interface with
					the database instead of accessing it directly.`),e({class:"text-muted-foreground"},`The parent model provides built-in CRUD methods (create, read, update, delete), so child models don't
					need to re-implement these methods.`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Naming and Basic Setup"),e({class:"text-muted-foreground"},'Models should be named in singular form, matching the corresponding database table name.\n					For example, a model for the "example" table should be named `Example`.'),s(`class Example extends Model
{

}`),e({class:"text-muted-foreground"},"Set `$tableName` to the exact name of the database table and `$alias` to a short alias for query building.")]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Model Pass-Through"),e({class:"text-muted-foreground"},`To reduce boilerplate, models can pass through undeclared methods directly to the storage layer.
					This means you only need to implement new logic in the resource API and storage class.
					By default, results are wrapped in a model object.`),e({class:"text-muted-foreground"},"Alternatively, you can bypass the wrapper by calling the method statically on the storage type:"),s(`// Bypass model pass-through:
$result = static::$storageType::methodName();`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Fields and Blacklisting"),e({class:"text-muted-foreground"},`Models define the fields that map to database columns. Field names should use camelCase,
					and the model will only set values for declared fields or joins.`),e({class:"text-muted-foreground"},"To prevent sensitive data from being output, you can define a fields blacklist. For example:"),s(`protected static array $fieldsBlacklist = [
	'password'
];`),e({class:"text-muted-foreground"},'The model ID key defaults to "id" unless otherwise specified:'),s("protected static string $idKeyName = 'id';")]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Field Formatting"),e({class:"text-muted-foreground"},"Models can augment or format fields before inserting or after retrieving data.\n					The `augment` method allows you to modify data before it's stored,\n					while the `format` method converts data before it's returned via the API."),s(`protected static function augment(mixed $data = null): mixed
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
}`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Model Joins"),e({class:"text-muted-foreground"},"Proto supports two different ways to relate models:"),e({class:"text-muted-foreground"},"1. **Eager Joins** using the JoinBuilder (original style). You explicitly define table joins in the `joins()` method.\n					2. **Lazy Relationships**, inspired by Laravel, where you declare methods like `hasMany`, `belongsTo`, `hasOne`, or `belongsToMany`."),t({class:"text-base font-semibold mt-6"},"Eager Joins (JoinBuilder)"),e({class:"text-muted-foreground"},"Override the `joins()` method in your model to define SQL joins. The JoinBuilder API allows one-to-one, one-to-many, and many-to-many relationships."),s(`protected static function joins($builder): void
{
	// Example: One-to-one join to Role
	Role::one($builder)
		->on(['id', 'userId'])
		->fields('role');

	// Example: Many-to-many through a bridge table (user_roles → roles)
	UserRole::bridge($builder)
		->many(Role::class)
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
}`),t({class:"text-base font-semibold mt-6"},"JoinBuilder: belongsToMany (Chaining)"),e({class:"text-muted-foreground"},"If you've added a new `belongsToMany` builder method, you can chain multiple many-to-many joins in a single `joins()` definition. Below is how the `User` model might look when using chained `belongsToMany` calls:"),s(`protected static function joins($builder): void
{
	/**
	 * Join user_roles → roles → permissions:
	 * 1) belongsToMany(Role::class, pivotFields: ['organizationId'])
	 *    • This pulls columns from user_roles (e.g. organizationId) as pivotFields.
	 * 2) belongsToMany(Permission::class)
	 *    • Automatically joins permissions via permission_roles.
	 */
	$builder
		->belongsToMany(Role::class, pivotFields: ['organizationId'])
		->belongsToMany(Permission::class);

	/**
	 * Join organizations via organization_users:
	 * 1) belongsToMany(Organization::class, ['id', 'name'])
	 *    • Specify which Organization fields to pull.
	 */
	$builder
		->belongsToMany(Organization::class, ['id', 'name']);
}`),e({class:"text-muted-foreground"},"Here's what happens step by step in that chained `belongsToMany` sequence:"),e({class:"text-muted-foreground"},"1. `$builder->belongsToMany(Role::class, pivotFields: ['organizationId'])`\n				    • Joins `user_roles` as the pivot table.\n				    • Pulls the `organizationId` column from `user_roles` (if available).\n				    • Joins `roles` on `role_id` → `id`.\n\n				  2. `->belongsToMany(Permission::class)`\n				    • Automatically infers pivot table `permission_roles`.\n				    • Joins `permissions` on `permission_id` → `id`.\n\n				  3. These two calls appear in the same `joins()` chain, so Proto knows to nest the permissions join under the roles join."),t({class:"text-base font-semibold mt-6"},"Lazy Relationships (hasMany, belongsTo, hasOne, belongsToMany)"),e({class:"text-muted-foreground"},"Instead of defining SQL joins upfront, you can declare relationship methods in your model:\n					`hasMany` for one-to-many, `hasOne` for one-to-one, `belongsTo` for inverse relations, and `belongsToMany` for many-to-many.\n					When you access `$model->relationName`, Proto will automatically issue a separate query to load the related data."),e({class:"text-muted-foreground"},"Below are example models for `User`, `Post`, `Profile`, and `Role`, demonstrating both styles and how to use `attach/toggle/detach/sync` for many-to-many."),s(`class User extends Model
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

	/**
	 * Lazy many-to-many: User ↔ Role (pivot user_roles)
	 */
	public function roles(): \\Proto\\Models\\Relations\\BelongsToMany
	{
		// related model, pivot table, foreign pivot, related pivot, parent key, related key, parent instance
		return $this->belongsToMany(
			Role::class,
			// optional override pivot table and keys
			'user_roles',
			'user_id',
			'role_id',
			'id',
			'id'
		);
	}
}`),s(`class Role extends Model
{
	protected static ?string $tableName = 'roles';
	protected static array $fields = ['id', 'name', 'slug', 'description'];

	/**
	 * Lazy many-to-many: Role ↔ User (pivot user_roles)
	 */
	public function users(): \\Proto\\Models\\Relations\\BelongsToMany
	{
		return $this->belongsToMany(
			User::class,
			// optional override pivot table and keys
			'user_roles',
			'role_id',
			'user_id',
			'id',
			'id'
		);
	}
}`),s(`class Post extends Model
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
}`),s(`class Profile extends Model
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
}`),e({class:"text-muted-foreground"},"**Usage examples:**"),s(`// Eagerly fetch a user with roles in a single query:
$userWithRoles = User::get(1);

// Lazily load roles for a user:
$user = User::get(1);
// Issues: SELECT * FROM roles r
//         INNER JOIN user_roles p ON p.role_id = r.id
//         WHERE p.user_id = 1
$userRoles = $user->roles;

// Lazily load posts for a user:
$allPosts = $user->posts;

// Lazily load profile for a user:
$profile = $user->profile;

// Inverse lazy load from post to its author:
$post = Post::get(5);
// Issues: SELECT * FROM users WHERE id = $post->user_id
$author = $post->user;`)]),o({class:"space-y-4 mt-12"},[e({class:"text-muted-foreground"},"Both styles can be used together in a single model, allowing you to mix and match as needed. For example, you might use JoinBuilder for eager joins and lazy relationships for simpler ones. Eager and lazy belongs to many relationships can also be used to attach, detach, sync, or toggle related records."),e({class:"text-muted-foreground"},"Belongs to many examples:"),s(`// Belongs to many helper methods can be used to manage relationships:
$user = User::get(1);

// Attach a role to a user:
$user->roles()->attach(3);

// Detach a role from a user:
$user->roles()->detach(3);

// Sync roles on a user (existing roles will be removed if not in array):
$user->roles()->sync([2, 4, 5]);

// Toggle roles on a user (attach missing, detach present):
$user->roles()->toggle([2, 6]);
	`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Storage Type and Proxy"),e({class:"text-muted-foreground"},`Each model uses a default storage layer to perform CRUD operations.
					If custom actions are needed, create a custom storage class and override the $storageType property.`),s(`// Specify a custom storage class if needed:
protected static string $storageType = ExampleStorage::class;`),e({class:"text-muted-foreground"},`When a model is instantiated, it sets up a storage object accessible via the storage property.
					This proxy automatically passes storage method calls to the events system.`),s(`// Example of accessing storage directly:
$result = $this->storage->get(1);`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Getting, Setting, and Persistence"),e({class:"text-muted-foreground"},`Models use PHP's magic methods (__get and __set) to simplify data access.
					For example, you can get and set properties directly on a model instance.`),s(`$model = new Example();

// Get a property
$value = $model->key;

// Set a property
$model->key = $value;

// Batch set properties
$model->set((object)[
	'key' => $value,
	'name' => $name
]);`),e({class:"text-muted-foreground"},"The model also provides built-in CRUD methods. For instance, to add a new row:"),s(`$model = new Example();
$model->name = 'save';
$model->add();

// Or use static shortcut methods:
$result = Example::create((object)[
	'name' => 'save'
]);`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Model Data"),e({class:"text-muted-foreground"},"The model data is stored in a nested structure, allowing for complex relationships and easy access to related data. If a user just wants the data without the model wrapper, they can use the data methods to return plain arrays or objects."),s(`// Get the data as a plain object
$data = $model->getData();

// Read-only data object
$dataReadonly = $model->getReadOnlyData();`)]),o({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"JSON Encoding"),e({class:"text-muted-foreground"},`When encoding a model as JSON, only public, non-blacklisted fields are included.
					This ensures that sensitive data is not inadvertently exposed.`)])]);export{p as ModelsPage,p as default};
