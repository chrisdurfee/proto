import { Code, H4, Li, P, Pre, Section, Ul } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { DocPage } from "../../doc-page.js";

/**
 * CodeBlock
 *
 * Adds copy-to-clipboard and visual formatting.
 */
const CodeBlock = Atom((props, children) =>
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
);

export const StoragePage = () =>
	DocPage(
		{
			title: 'Storage',
			description: 'Learn how to use Proto\'s storage system to interact with your database via query builders and adapters.'
		},
		[

			// OVERVIEW
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`Storage is an object used to get and set data to the database table. It can access its parent model and inherits all built-in CRUD methods from the base class. You don't need to manually write basic methods in most child storage classes.`
				)
			]),

			// NAMING
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming'),
				P({ class: 'text-muted-foreground' },
					`Storage classes should be singular and end with "Storage".`
				),
				H4({ class: 'font-semibold' }, 'Example: Naming a Storage Class'),
				CodeBlock(
`<?php declare(strict_types=1);
namespace Common\\Storage;
use Proto\\Storage\\Storage;

class ExampleStorage extends Storage
{
	// inherits CRUD methods
}`
				)
			]),

			// CONNECTION PROPERTY
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Connection Property'),
				P({ class: 'text-muted-foreground' },
					`Define a custom database connection if this storage should use a different DB from the default.`
				),
				H4({ class: 'font-semibold' }, 'Set a Custom Connection'),
				CodeBlock(`protected string $connection = 'default';`)
			]),

			// DATABASE ADAPTER
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Database Adapter'),
				P({ class: 'text-muted-foreground' },
					`The storage layer uses a database adapter (usually Mysqli) for executing SQL operations.`
				),
				H4({ class: 'font-semibold' }, 'Adapter Methods'),
				Ul({ class: 'list-disc pl-6 text-muted-foreground' }, [
					Li("select(), insert(), update(), delete()"),
					Li("execute(), query(), fetch()"),
					Li("beginTransaction(), commit(), rollback(), transaction()")
				]),
				H4({ class: 'font-semibold' }, 'Example: Fetch Rows Using Adapter'),
				CodeBlock(`$rows = $this->db->fetch('SELECT * FROM example');`)
			]),

			// QUERY BUILDER
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Query Builder'),
				P({ class: 'text-muted-foreground' },
					`Storage gives access to a fluent query builder to compose SQL easily.`
				),
				H4({ class: 'font-semibold' }, 'Available Builder Methods'),
				Ul({ class: 'list-disc pl-6 text-muted-foreground' }, [
					Li("select(), insert(), update(), delete()"),
					Li("join(), leftJoin(), rightJoin(), outerJoin(), union()"),
					Li("where(), in(), orderBy(), groupBy(), having(), distinct(), limit()")
				]),
				H4({ class: 'font-semibold' }, 'Example: Simple Select Query'),
				CodeBlock(
`$sql = $this->table()
	->select()
	->where("status = 'active'");

$rows = $this->fetch($sql);`
				)
			]),

			// DEBUGGING
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Debugging Queries'),
				P({ class: 'text-muted-foreground' },
					`Use casting or debug() to inspect the generated SQL.`
				),
				H4({ class: 'font-semibold' }, 'Example: Debugging'),
				CodeBlock(
`echo $sql;
$sql->debug();`
				)
			]),

			// HELPER METHODS
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Helper Methods'),
				P({ class: 'text-muted-foreground' },
					`Common shortcuts available on all storage classes.`
				),
				Ul({ class: 'list-disc pl-6 text-muted-foreground' }, [
					Li("table() - model's query builder"),
					Li("builder(table, alias) - custom table builder"),
					Li("select() - selects default columns"),
					Li("where() - creates filtered queries")
				]),
				H4({ class: 'font-semibold' }, 'Example: Table vs. Builder'),
				CodeBlock(
`$sql = $this->table()->select()->where("name = 'John'");
$sql = $this->builder('other_table', 'o')->select()->where("o.active = 1");`
				)
			]),

			// FILTER ARRAYS
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Filter Arrays'),
				P({ class: 'text-muted-foreground' },
					`Filters simplify conditions and are used in methods like getBy(), where(), all().`
				),
				H4({ class: 'font-semibold' }, 'Supported Filter Formats'),
				Ul({ class: 'list-disc pl-6 text-muted-foreground' }, [
					Li(`Raw SQL: "a.id = '1'"`),
					Li(`Manual bind: ["created_at BETWEEN ? AND ?", [date1, date2]]`),
					Li(`Auto-bind: ["a.id", $user->id]`),
					Li(`Operator: ["a.id", ">", $user->id]`)
				]),
				H4({ class: 'font-semibold' }, 'Example: Applying Filters'),
				CodeBlock(
`$filter = [
	"a.id = '1'",
	["a.created_at BETWEEN ? AND ?", ['2021-02-02', '2021-02-28']],
	['a.id', $user->id],
	['a.id', '>', $user->id]
];

$sql = $this->getBy($filter);   // one
$sql = $this->where($filter);   // many
$result = $this->fetch($sql, $params);`
				)
			]),

			// FIND & FINDALL
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Find and FindAll'),
				P({ class: 'text-muted-foreground' },
					`Find allows dynamic queries without creating a custom storage method.`
				),
				H4({ class: 'font-semibold' }, 'Examples: Find/FindAll'),
				CodeBlock(
`$this->findAll(function($sql, &$params) {
	$params[] = 'active';
	$sql->where('status = ?')->orderBy('status DESC')->groupBy('user_id');
});

$this->find(function($sql, &$params) {
	$params[] = 'active';
	$sql->where('status = ?')->limit(1);
});`
				)
			]),

			// EXAMPLES
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Example Queries'),

				H4({ class: 'font-semibold' }, 'Custom Select with Conditions'),
				CodeBlock(
`public function checkRequest(string $requestId, int $userId): bool
{
	$sql = $this->table()
		->select('id')
		->where(
			["user_id", "?"], // Equality comparison with param bind placeholder
			"request_id = ?", // raw sql with placeholder
			"status = 'pending'", //raw sql
			["created_at", "<=", "DATE_ADD(created_at, INTERVAL 1 DAY)"] // Custom comparison operator
		)
		->limit(1);

		$rows = $this->fetch($sql, [$userId, $requestId]);
	return (count($rows) > 0);
}`
				),

				H4({ class: 'font-semibold' }, 'Custom Update Query'),
				CodeBlock(
`public function updateStatusByRequest(string $requestId, string $status = 'complete'): bool
{
	$sql = $this->table()
		->update("status = ?")
		->where("request_id = ?");

	return $this->execute($sql, [$status, $requestId]);
}`
				),

				H4({ class: 'font-semibold' }, 'Custom Update with Join'),
				CodeBlock(
`public function updateAccessedAt(string $userId, string $guid, string $ipAddress): bool
{
	$dateTime = date('Y-m-d H:i:s');
	$sql = $this->table()
		->update("{$this->alias}.accessed_at = '{$dateTime}'")
		->join(function($joins) {
			$joins
				->left('user_authed_devices', 'ud')
				->on("{$this->alias}.device_id = ud.id");
		})
		->where("{$this->alias}.ip_address = ?", "ud.user_id = ?", "ud.guid = ?");

	return $this->execute($sql, [$ipAddress, $userId, $guid]);
}`
				),

				H4({ class: 'font-semibold' }, 'Select with Union'),
				CodeBlock(
`protected function getByOptionInnerQuery()
{
  	return $this->table()
		->select(['id', 'callsId'], ["NULL as calendarId"], ['scheduled', 'callStart'], ['id', 'created'])
		->join(function($joins) {
			$joins
				->left('list_options', 'lo')
				->on("{$this->alias}.type_id = lo.option_number");
		})
		->where(
			"{$this->alias}.client_id = ?",
			"lo.list_id = ?",
			"lo.option_number = ?",
			"{$this->alias}.status IN (?)"
		)
		->union(
			$this->builder('calendar', 'cal')
			->select(["NULL as callsId"], ['id', 'calendarId'], ['start', 'callStart'], ['id', 'created'])
			->where('cal.client_id = ?', 'cal.type = ?', 'cal.deleted = 0')
		);
}`
				),

				H4({ class: 'font-semibold' }, 'Delete with Conditions'),
				CodeBlock(
`public function deleteRole(int $userId, int $roleId): bool
{
	if ($userId === null) return false;

	$sql = $this->table()
		->delete()
		->where('user_id = ?', 'role_id = ?');

	return $this->db->execute($sql, [$userId, $roleId]);
}`
				)
			])
		]
	);

export default StoragePage;