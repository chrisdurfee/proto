<?php declare(strict_types=1);
namespace Proto\Html\Tables;

/**
 * Class ExampleTable
 *
 * Generates an example HTML table with "Id" and "Company Name" columns.
 *
 * @package Proto\Html\Tables
 */
class ExampleTable extends Table
{
	/**
	 * Creates the table header row.
	 *
	 * @return string The HTML for the title row.
	 */
	protected function createTitleRow(): string
	{
		return <<<HTML
		<tr>
			<th>ID</th>
			<th>Company Name</th>
		</tr>
HTML;
	}

	/**
	 * Creates a table row with dynamic data.
	 *
	 * @param array|object $row The row data.
	 * @return string The HTML for a single row.
	 */
	protected function createRow(array|object $row): string
	{
		$id = $row->id ?? '';
		$companyName = $row->companyname ?? '';

		return <<<HTML
		<tr>
			<td>{$id}</td>
			<td>{$companyName}</td>
		</tr>
HTML;
	}
}