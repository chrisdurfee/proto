<?php declare(strict_types=1);
namespace Proto\Html\Atoms\Headers;

class H2 extends Header
{
	protected function getBody()
	{
		$className = $this->getClassName();

		return <<<EOT
		<h2 class="{$className}">
			{$this->getTitle()}
		</h2>
EOT;
	}
}
