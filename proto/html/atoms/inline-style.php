<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * InlineStyle
 *
 * This will create an inline style.
 *
 * @package Proto\Html\Atoms
 */
class InlineStyle extends Atom
{
    /**
     * This will set up the inline style.
     *
     * @param string $content
     * @return void
     */
    public function __construct(
        protected string $content
    )
    {
        parent::__construct();
    }

    /**
     * This will get the inline style body.
     *
     * @return string
     */
    protected function getBody()
    {
        return <<<EOT
        <style type="text/css">
            {$this->content}
        </style>
EOT;
    }
}
