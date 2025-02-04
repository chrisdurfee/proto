<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

class Script extends Atom
{
    /**
     *
     * @var string
     */
    protected $src;

    /**
     *
     * @param string $src
     */
    public function __construct($src)
    {
        parent::__construct();
        $this->src = $src;
    }

    /**
     *
     * @return string
     */
    protected function getBody()
    {
        return <<<EOT
        <script src="$this->src"></script>
EOT;
    }
}
