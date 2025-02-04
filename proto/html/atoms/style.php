<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

class Style extends Atom
{
    /**
     *
     * @var string
     */
    protected $href;

    /**
     *
     * @param string $href
     */
    public function __construct($href)
    {
        parent::__construct();
        $this->href = $href;
    }

    /**
     *
     * @return string
     */
    protected function getBody()
    {
        return <<<EOT
        <link href="$this->href" rel="stylesheet" type="text/css">
EOT;
    }
}
