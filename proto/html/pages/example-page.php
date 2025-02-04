<?php declare(strict_types=1);
namespace Proto\Html\Pages;

use Proto\Html\Tables\ExampleTable;

class ExamplePage extends Page
{
	protected function getTable()
	{
		$rows = $this->get('rows') ?? [];
		return new ExampleTable([
			'rows' => $rows
		]);
	}

    protected function getBody()
	{
		return <<<EOT
		<main>
			{$this->getTable()}
		</main>
EOT;
	}
}
