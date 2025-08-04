<?php declare(strict_types=1);
namespace Proto\Html\Lists;

use Proto\Html\Template;

/**
 * Class BasicList
 *
 * Base class for generating HTML lists.
 *
 * @package Proto\Html\Lists
 * @abstract
 */
abstract class BasicList extends Template
{
	/**
	 * Generates the list items.
	 *
	 * @return string The generated list rows.
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
	 * Generates the full list structure.
	 *
	 * @return string The rendered HTML list.
	 */
	protected function getBody(): string
	{
		return <<<HTML
		<ul class="panel">
			{$this->setupRows()}
		</ul>
HTML;
	}

	/**
	 * Creates a single row in the list.
	 *
	 * @param array|object $row The row data.
	 * @return string The HTML for a single row.
	 */
	abstract protected function createRow(array|object $row): string;
}