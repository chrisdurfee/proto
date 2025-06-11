import{a2 as a,a0 as e,G as l,s as d,Z as o,a3 as t,u as n,N as c,o as u}from"./index-DD5ZlTX4.js";import{D as m}from"./doc-page-DFkqm2Y0.js";import"./sidebar-menu-page-D_2zNFuZ-DJNCgvAz.js";const s=d((i,r)=>n({...i,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
				rounded-lg border bg-muted whitespace-break-spaces
				break-all cursor-pointer mt-4 ${i.class}`},[c({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(r[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:u.clipboard.checked})}},r)])),b=()=>m({title:"Storage",description:"Learn how to use Proto's storage system to interact with your database via query builders and adapters."},[a({class:"space-y-4"},[e({class:"text-lg font-bold"},"Overview"),l({class:"text-muted-foreground"},"Storage is an object used to get and set data to the database table. It can access its parent model and inherits all built-in CRUD methods from the base class. You don't need to manually write basic methods in most child storage classes.")]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Naming"),l({class:"text-muted-foreground"},'Storage classes should be singular and end with "Storage".'),e({class:"font-semibold"},"Example: Naming a Storage Class"),s(`<?php declare(strict_types=1);
namespace Common\\Storage;
use Proto\\Storage\\Storage;

class ExampleStorage extends Storage
{
	// inherits CRUD methods
}`)]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Connection Property"),l({class:"text-muted-foreground"},"Define a custom database connection if this storage should use a different DB from the default."),e({class:"font-semibold"},"Set a Custom Connection"),s("protected string $connection = 'default';")]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Database Adapter"),l({class:"text-muted-foreground"},"The storage layer uses a database adapter (usually Mysqli) for executing SQL operations."),e({class:"font-semibold"},"Adapter Methods"),o({class:"list-disc pl-6 text-muted-foreground"},[t("select(), insert(), update(), delete()"),t("execute(), query(), fetch()"),t("beginTransaction(), commit(), rollback(), transaction()")]),e({class:"font-semibold"},"Example: Fetch Rows Using Adapter"),s("$rows = $this->db->fetch('SELECT * FROM example');")]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Query Builder"),l({class:"text-muted-foreground"},"Storage gives access to a fluent query builder to compose SQL easily."),e({class:"font-semibold"},"Available Builder Methods"),o({class:"list-disc pl-6 text-muted-foreground"},[t("select(), insert(), update(), delete()"),t("join(), leftJoin(), rightJoin(), outerJoin(), union()"),t("where(), in(), orderBy(), groupBy(), having(), distinct(), limit()")]),e({class:"font-semibold"},"Example: Simple Select Query"),s(`$sql = $this->table()
	->select()
	->where("status = 'active'");

$rows = $this->fetch($sql);`)]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Debugging Queries"),l({class:"text-muted-foreground"},"Use casting or debug() to inspect the generated SQL."),e({class:"font-semibold"},"Example: Debugging"),s(`echo $sql;
$sql->debug();`)]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Helper Methods"),l({class:"text-muted-foreground"},"Common shortcuts available on all storage classes."),o({class:"list-disc pl-6 text-muted-foreground"},[t("table() - model's query builder"),t("builder(table, alias) - custom table builder"),t("select() - selects default columns"),t("where() - creates filtered queries")]),e({class:"font-semibold"},"Example: Table vs. Builder"),s(`$sql = $this->table()->select()->where("name = 'John'");
$sql = $this->builder('other_table', 'o')->select()->where("o.active = 1");`)]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Filter Arrays"),l({class:"text-muted-foreground"},"Filters simplify conditions and are used in methods like getBy(), where(), all()."),e({class:"font-semibold"},"Supported Filter Formats"),o({class:"list-disc pl-6 text-muted-foreground"},[t(`Raw SQL: "a.id = '1'"`),t('Manual bind: ["created_at BETWEEN ? AND ?", [date1, date2]]'),t('Auto-bind: ["a.id", $user->id]'),t('Operator: ["a.id", ">", $user->id]')]),e({class:"font-semibold"},"Example: Applying Filters"),s(`$filter = [
	"a.id = '1'",
	["a.created_at BETWEEN ? AND ?", ['2021-02-02', '2021-02-28']],
	['a.id', $user->id],
	['a.id', '>', $user->id]
];

$sql = $this->getBy($filter);   // one
$sql = $this->where($filter);   // many
$result = $this->fetch($sql, $params);`)]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Find and FindAll"),l({class:"text-muted-foreground"},"Find allows dynamic queries without creating a custom storage method."),e({class:"font-semibold"},"Examples: Find/FindAll"),s(`$this->findAll(function($sql, &$params) {
	$params[] = 'active';
	$sql->where('status = ?')->orderBy('status DESC')->groupBy('user_id');
});

$this->find(function($sql, &$params) {
	$params[] = 'active';
	$sql->where('status = ?')->limit(1);
});`)]),a({class:"space-y-4 mt-12"},[e({class:"text-lg font-bold"},"Example Queries"),e({class:"font-semibold"},"Custom Select with Conditions"),s(`public function checkRequest(string $requestId, int $userId): bool
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
}`),e({class:"font-semibold"},"Custom Update Query"),s(`public function updateStatusByRequest(string $requestId, string $status = 'complete'): bool
{
	$sql = $this->table()
		->update("status = ?")
		->where("request_id = ?");

	return $this->execute($sql, [$status, $requestId]);
}`),e({class:"font-semibold"},"Custom Update with Join"),s(`public function updateAccessedAt(string $userId, string $guid, string $ipAddress): bool
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
}`),e({class:"font-semibold"},"Select with Union"),s(`protected function getByOptionInnerQuery()
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
}`),e({class:"font-semibold"},"Delete with Conditions"),s(`public function deleteRole(int $userId, int $roleId): bool
{
	$sql = $this->table()
		->delete()
		->where('user_id = ?', 'role_id = ?');

	return $this->db->execute($sql, [$userId, $roleId]);
}`)])]);export{b as StoragePage,b as default};
