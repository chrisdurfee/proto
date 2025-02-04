<?php declare(strict_types=1);
namespace Proto\Html\Atoms\Headers;

class H3 extends Header
{
	protected function getBody()
	{
		$className = $this->getClassName();

		return <<<EOT
		<h3 class="{$className}">
			{$this->getTitle()}
		</h3>
EOT;
	}
}
