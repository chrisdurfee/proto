<?php declare(strict_types=1);
namespace Proto\Html\Lists;

/**
 * ModuleList
 */
class ModuleList extends BasicList
{
	protected function getBody()
	{
		return <<<EOT
		<ul class="panel">
			{$this->getHeader()}
			{$this->setupRows()}
		</ul>
EOT;
	}

	protected function getHeader()
	{
		$header = $this->get('header');
		return (!empty($header))? $this->createHeader($header) : '';
	}

	/**
	 * This should return the header.
	 * @param string $header
	 * @return string
	 */
	protected function createHeader($header)
	{
		return <<<EOT
		<header>{$header}</header>
EOT;
	}

	/**
	 * This should return a row.
	 * @abstract
	 * @param object $row
	 * @return string
	 */
	protected function createRow($row)
	{
		return <<<EOT
			<li><a target="_blank" href="{$row->url}">{$row->label}</a></li>
EOT;
	}
}
