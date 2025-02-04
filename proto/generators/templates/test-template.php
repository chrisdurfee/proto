<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * TestTemplate
 *
 * This will create a test template generator.
 *
 * @package Proto\Generators\Templates
 */
class TestTemplate extends ClassTemplate
{
    /**
     * @var bool $final
     */
    protected bool $final = true;

    /**
     * This will get the class directory.
     *
     * @return string
     */
    protected function getDir(): string
    {
        return 'Tests\\' . $this->get('dir');
    }

    /**
     * This will get the extends string.
     *
     * @return string
     */
    protected function getExtends(): string
    {
        $extends = $this->get('extends');
        return 'extends ' . (($extends)? $extends : 'Test');
    }

    /**
     * This will get the className.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->get('className') . 'Test';
    }

    /**
     * This will get the use string.
     *
     * @return string
     */
    protected function getUse(): string
    {
        return <<<EOT
use Tests\\Test;
EOT;
    }

    /**
     * This will get the class content.
     *
     * @return string
     */
    protected function getClassContent(): string
    {
        return <<<'EOT'
    /**
     * This will be called when the test is set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // do something on setup
    }

    /**
     * This will be called when the test is torn down.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // do something on tear down
    }
EOT;
    }
}
