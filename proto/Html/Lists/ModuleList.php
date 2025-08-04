<?php declare(strict_types=1);

namespace Proto\Html\Lists;

/**
 * Class ModuleList
 *
 * Generates a module list with an optional header.
 *
 * @package Proto\Html\Lists
 */
class ModuleList extends BasicList
{
	/**
	 * Generates the body of the list.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		return <<<HTML
		<ul class="panel">
			{$this->getHeader()}
			{$this->setupRows()}
		</ul>
HTML;
	}

	/**
	 * Retrieves the header if it exists.
	 *
	 * @return string The HTML header or an empty string if not set.
	 */
	protected function getHeader(): string
	{
		$header = $this->get('header');
		return (!empty($header)) ? $this->createHeader($header) : '';
	}

	/**
	 * Creates the header HTML.
	 *
	 * @param string $header The header text.
	 * @return string The formatted header HTML.
	 */
	protected function createHeader(string $header): string
	{
		return <<<HTML
		<header>{$header}</header>
HTML;
	}

	/**
	 * Creates a list row.
	 *
	 * @param array|object $row The row data.
	 * @return string The formatted row HTML.
	 */
	protected function createRow(array|object $row): string
	{
		$url = $row->url ?? '#';
		$label = $row->label ?? 'Unnamed';

		return <<<HTML
			<li><a target="_blank" href="{$url}">{$label}</a></li>
HTML;
	}
}