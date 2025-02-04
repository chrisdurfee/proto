<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Pre
 */
class Pre extends Atom
{
    protected function getBody()
	{
        $content = $this->get('content') ?? '';

		return <<<EOT
<pre>
    {$content}
</pre>
EOT;
	}
}
