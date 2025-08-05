<?php declare(strict_types=1);
namespace Proto\Html\Pages;

use Proto\Html\Tables\ExampleTable;

/**
 * Class ExamplePage
 *
 * A page that renders an example table.
 *
 * @package Proto\Html\Pages
 */
class ExamplePage extends Page
{
	/**
	 * @var ExampleTable|null Caches the table instance.
	 */
	private ?ExampleTable $table = null;

	/**
	 * Returns the ExampleTable instance.
	 *
	 * @return ExampleTable
	 */
	protected function getTable(): ExampleTable
	{
		if ($this->table === null)
		{
			$this->table = new ExampleTable([
				'rows' => $this->get('rows') ?? []
			]);
		}

		return $this->table;
	}

	/**
	 * Generates the main body content of the page.
	 *
	 * @return string The rendered HTML body.
	 */
	protected function getBody(): string
	{
		return <<<HTML
		<main>
			{$this->getTable()}
		</main>
HTML;
	}
}