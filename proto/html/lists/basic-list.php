<?php declare(strict_types=1);
namespace Proto\Html\Lists;

use Proto\Html\Template;

/**
 * BasicList
 * @abstract
 */
abstract class BasicList extends Template
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
		<ul class="panel">
			{$this->setupRows()}
		</ul>
EOT;
	}

	/**
	 * This should return a row.
	 * @abstract
	 * @param object $row
	 * @return string
	 */
	abstract protected function createRow($row);
}
