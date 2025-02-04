<?php declare(strict_types=1);
namespace Proto\Html\Tables;

use Proto\Html\Template;

/**
 * Table
 * @abstract
 */
abstract class Table extends Template
{
	/**
	 * This will setup the table rows.
	 *
	 * @return string
	 */
	protected function setupRows()
	{
		$body = '';
		$rows = $this->get('rows') ?? [];
		if(count($rows))
		{
			foreach($rows as $row)
			{
				$body .= $this->createRow($row);
			}
		}
		return $body;
	}

	protected function getBody()
	{
		return <<<EOT
		<table>
			{$this->createTitleRow()}
			{$this->setupRows()}
		</table>
EOT;
	}

	/**
	 * This should return the title row.
	 * @abstract
	 * @return string
	 */
	abstract protected function createTitleRow();

	/**
	 * This should return a row.
	 * @abstract
	 * @param object $row
	 * @return string
	 */
	abstract protected function createRow($row);
}
