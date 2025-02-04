<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

class Select extends Atom
{
    /**
	 * This will setup the options.
	 *
	 * @return string
	 */
	protected function setupOptions()
	{
		$body = '';
		$options = $this->get('options') ?? [];
		if(count($options))
		{
			foreach($options as $option)
			{
				$body .= $this->createOption($option);
			}
		}
		return $body;
    }

    /**
     * This will add an option.
     *
     * @param object $option
     * @return string
     */
    protected function createOption($option)
    {
        $className = $option->className ?? '';

        return <<<EOT
            <option value="{$option->value}" class="{$className}">{$option->label}</option>
EOT;
    }

    /**
     * This will check to setup a default option if a
     * label is set.
     *
     * @return string
     */
    protected function getDefault()
    {
        $label = $this->get('label') ?? '';
        if(!$label)
        {
            return '';
        }

        return $this->createOption((object)[
            'value' => '',
            'label' => $label
        ]);
    }

	protected function getBody()
	{
        $name = $this->get('name') ?? '';
        $className = $this->get('className') ?? '';

		return <<<EOT
        <select name="{$name}" class="{$className}">
            {$this->getDefault()}
			{$this->setupOptions()}
		</select>
EOT;
	}
}
