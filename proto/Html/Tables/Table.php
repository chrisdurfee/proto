<?php declare(strict_types=1);

namespace Proto\Html\Tables;

use Proto\Html\Template;

/**
 * Class Table
 *
 * Base class for generating HTML tables.
 *
 * @package Proto\Html\Tables
 * @abstract
 */
abstract class Table extends Template
{
	/**
	 * Generates the table rows.
	 *
	 * @return string The generated table body.
	 */
	protected function setupRows(): string
	{
		$body = '';
		$rows = $this->get('rows') ?? [];
		if (!empty($rows))
		{
			foreach ($rows as $row)
			{
				$body .= $this->createRow($row);
			}
		}

		return $body;
	}

	/**
	 * Generates the full table body.
	 *
	 * @return string The rendered HTML table.
	 */
	protected function getBody(): string
	{
		$titleRow = $this->createTitleRow();
		$rows = $this->setupRows();

		return <<<HTML
		<table>
			{$titleRow}
			{$rows}
		</table>
HTML;
	}

	/**
	 * Generates the title row of the table.
	 *
	 * @return string The HTML for the title row.
	 */
	abstract protected function createTitleRow(): string;

	/**
	 * Generates a table row.
	 *
	 * @param array|object $row The row data.
	 * @return string The HTML for a single row.
	 */
	abstract protected function createRow(array|object $row): string;
}