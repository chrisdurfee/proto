<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * ControllerTemplate
 *
 * This will create a controller template generator.
 *
 * @package Proto\Generators\Templates
 */
class ControllerTemplate extends ClassTemplate
{
    /**
     * This will get the extends string.
     *
     * @return string
     */
    protected function getExtends(): string
    {
        $extends = $this->get('extends');
        return 'extends ' . (($extends)? $extends : 'Controller');
    }

    /**
     * This will get the getModelClass string.
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        $className = $this->get('className');

        return <<<EOT
/**
     * This will setup the model class.
     *
     * @param string|null \$modelClass by using the magic constant ::class
     */
    public function __construct(
        protected ?string \$modelClass = {$className}::class
    )
    {
        parent::__construct(\$modelClass);
    }
EOT;
    }

    /**
     * This will get the className.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->get('className') . 'Controller';
    }

    /**
     * This will get the use string.
     *
     * @return string
     */
    protected function getUse(): string
    {
        $className = $this->getNamespace() . $this->get('className');

        return <<<EOT
use App\Models\\{$className};
EOT;
    }

    /**
     * This will get the class content.
     *
     * @return string
     */
    protected function getClassContent(): string
    {
        $model = $this->getModelClass();
        return <<<EOT
    /**
     * @var bool \$passResponse
     */
    protected \$passResponse = true;

    {$model}
EOT;
    }
}
