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
 * StoragePage
 *
 * This page explains how Proto's storage system works. Storage is used to get and set data
 * in the database, interface with query builders and database adapters, and provide built-in CRUD
 * methods to models.
 *
 * @returns {DocPage}
 */
export const StoragePage = () =>
	DocPage(
		{
			title: 'Storage',
			description: 'Learn how to use Proto\'s storage system to interact with your database via query builders and adapters.'
		},
		[
			// Overview
			Section({ class: 'space-y-4' }, [
				H4({ class: 'text-lg font-bold' }, 'Overview'),
				P({ class: 'text-muted-foreground' },
					`Storage is an object used to get and set data in a database table. It provides access to
					query builders, database adapters, and built-in CRUD methods inherited by child storage classes.`
				)
			]),

			// Naming
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Naming'),
				P({ class: 'text-muted-foreground' },
					`The storage class should always be singular and followed by "Storage". For example:`
				),
				CodeBlock(
`<?php
namespace Proto\\Storage;
use Proto\\Database\\QueryBuilder\\QueryHandler;

class ExampleStorage extends Storage
{
    // Inherits CRUD methods from the parent Storage class.
}`
				)
			]),

			// Connection Property
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Connection Property'),
				P({ class: 'text-muted-foreground' },
					`Set the $connection property to the database handle defined in your common/Config .env file.`
				),
				CodeBlock(
`// In a storage class
protected string $connection = 'default';`
				)
			]),

			// Database Adapter
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Database Adapter'),
				P({ class: 'text-muted-foreground' },
					`The storage object uses a database adapter to interact with the database.
					An instance of Proto\\Database\\Database is used to create an adapter
					(such as the Mysqli adapter) which provides built-in methods including:`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("select()"),
					Li("insert()"),
					Li("update()"),
					Li("delete()"),
					Li("execute()"),
					Li("query()"),
					Li("autoCommit()"),
					Li("beginTransaction()"),
					Li("transaction()"),
					Li("commit()"),
					Li("rollback()"),
					Li("fetch()")
				]),
				P({ class: 'text-muted-foreground' },
					`For example, in a storage method you might write:`
				),
				CodeBlock(
`$result = $this->db->fetch('SELECT * FROM example');`
				)
			]),

			// Query Builder
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Query Builder'),
				P({ class: 'text-muted-foreground' },
					`The query builder simplifies SQL queries with a fluent interface. It supports methods such as:`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("select()"),
					Li("insert()"),
					Li("update()"),
					Li("delete()"),
					Li("join()"),
					Li("leftJoin()"),
					Li("rightJoin()"),
					Li("union()"),
					Li("where()"),
					Li("orderBy()"),
					Li("groupBy()"),
					Li("limit()")
				]),
				P({ class: 'text-muted-foreground' },
					`For example, you can create a select query as follows:`
				),
				CodeBlock(
`$sql = $this->table()
    ->select()
    ->where("name = 'example'");
$rows = $this->fetch($sql);`
				)
			]),

			// Debugging
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Debugging'),
				P({ class: 'text-muted-foreground' },
					`Casting the query builder to a string will render the SQL query. Alternatively, use the debug()
					method to output the query wrapped in a preformatted block:`
				),
				CodeBlock(
`// Debug the query:
echo $sql; // or
$sql->debug();`
				)
			]),

			// Helper Methods
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Helper Methods'),
				P({ class: 'text-muted-foreground' },
					`The storage layer provides several helper methods to build queries:
					`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("table() - sets up a query builder for the model table"),
					Li("builder('table', 'alias') - creates a new query builder for any table"),
					Li("select() - creates a select query for the model table with default columns"),
					Li("where() - adds filters to the query")
				])
			]),

			// Example Queries
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Example Queries'),
				P({ class: 'text-muted-foreground' },
					`Below are examples of custom queries written within a storage class:`
				),
				CodeBlock(
`// Custom Select Example:
public function checkRequest(string $requestId, int $userId): bool
{
    $sql = $this->table()
        ->select('id')
        ->where(
            ["user_id", "?"],         // Parameterized equality
            "request_id = ?",          // Raw SQL with placeholder
            "status = 'pending'",      // Raw SQL condition
            ["created_at", "<=", "DATE_ADD(created_at, INTERVAL 1 DAY)"] // Custom comparison
        )
        ->limit(1);
    $rows = $this->fetch($sql, [$userId, $requestId]);
    return (count($rows) > 0);
}

// Custom Update Example:
public function updateStatusByRequest(string $requestId, string $status = 'complete'): bool
{
    $sql = $this->table()
        ->update("status = ?")
        ->where("request_id = ?");
    return $this->execute($sql, [$status, $requestId]);
`
				),
				CodeBlock(
`// Custom Update with Join Example:
public function updateAccessedAt(string $userId, string $guid, string $ipAddress): bool
{
    $dateTime = date('Y-m-d H:i:s');
    $sql = $this->table()
        ->update("{$this->alias}.accessed_at = '{$dateTime}'")
        ->join(function($joins) {
            $joins->left('user_authed_devices', 'ud')
                  ->on("{$this->alias}.device_id = ud.id");
        })
        ->where("{$this->alias}.ip_address = ?", "ud.user_id = ?", "ud.guid = ?");
    return $this->execute($sql, [$ipAddress, $userId, $guid]);
}`
				),
				CodeBlock(
`// Select with Union Example:
protected function getByOptionInnerQuery()
{
    return $this->table()
        ->select(['id', 'callsId'], ["NULL as calendarId"], ['scheduled', 'callStart'], ['id', 'created'])
        ->join(function($joins) {
            $joins->left('list_options', 'lo')
                  ->on("{$this->alias}.type_id = lo.option_number");
        })
        ->where("{$this->alias}.client_id = ?", "lo.list_id = ?", "lo.option_number = ?", "{$this->alias}.status IN (?)")
        ->union(
            $this->builder('calendar', 'cal')
                 ->select(["NULL as callsId"], ['id', 'calendarId'], ['start', 'callStart'], ['id', 'created'])
                 ->where('cal.client_id = ?', 'cal.type = ?', 'cal.deleted = 0')
        );
}`
				),
				CodeBlock(
`// Custom Delete Example:
public function deleteRole(int $userId, int $roleId): bool
{
    if ($userId === null) {
        return false;
    }
    $sql = $this->table()
        ->delete()
        ->where('user_id = ?', 'role_id = ?');
    return $this->db->execute($sql, [$userId, $roleId]);
}`
				)
			]),

			// Filter Arrays and Ad-hoc Queries
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-lg font-bold' }, 'Filter Arrays and Ad-hoc Queries'),
				P({ class: 'text-muted-foreground' },
					`To simplify query construction, the storage layer accepts filter arrays that follow the same patterns as the query builder.
					Examples include:`
				),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Raw SQL: \"a.id = '1'\""),
					Li("Manual parameter binding: [\"a.created_at BETWEEN ? AND ?\", ['2021-02-02', '2021-02-28']]"),
					Li("Automatic equality binding: ['a.id', \$user->id]"),
					Li("Custom operator condition: ['a.id', '>', \$user->id]")
				]),
				CodeBlock(
`$filter = [
    "a.id = '1'",
    ["a.created_at BETWEEN ? AND ?", ['2021-02-02', '2021-02-28']],
    ['a.id', $user->id],
    ['a.id', '>', $user->id]
];

$sql = $this->getBy($filter); // Retrieve one result
$sql = $this->where($filter); // Retrieve many results
$result = $this->fetch($sql, $params);

// Using ad-hoc queries with findAll and find:
$this->findAll(function($sql, &$params) {
    $params[] = 'active';
    $sql->where('status = ?')
        ->orderBy('status DESC')
        ->groupBy('user_id');
});

$this->find(function($sql, &$params) {
    $params[] = 'active';
    $sql->where('status = ?')
        ->limit(1);
});
`
				)
			])
		]
	);

export default StoragePage;