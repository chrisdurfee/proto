<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Class Select
 *
 * Represents an HTML `<select>` dropdown with options.
 *
 * @package Proto\Html\Atoms
 */
class Select extends Atom
{
	/**
	 * Generates the `<option>` elements from the provided options.
	 *
	 * @return string The generated options.
	 */
	protected function setupOptions(): string
	{
		$body = '';
		$options = $this->get('options') ?? [];
		if (!empty($options))
        {
			foreach ($options as $option)
            {
				$body .= $this->createOption($option);
			}
		}

		return $body;
	}

	/**
	 * Creates a single `<option>` element.
	 *
	 * @param array|object $option The option data.
	 * @return string The generated `<option>` tag.
	 */
	protected function createOption(array|object $option): string
	{
		$value = is_object($option) ? ($option->value ?? '') : ($option['value'] ?? '');
		$label = is_object($option) ? ($option->label ?? '') : ($option['label'] ?? '');
		$className = is_object($option) ? ($option->className ?? '') : ($option['className'] ?? '');

		return <<<HTML
		<option value="{$value}" class="{$className}">{$label}</option>
HTML;
	}

	/**
	 * Generates the default `<option>` if a label is set.
	 *
	 * @return string The default option or an empty string.
	 */
	protected function getDefault(): string
	{
		$label = $this->get('label') ?? '';
		if (!$label)
        {
			return '';
		}

		return $this->createOption([
			'value' => '',
			'label' => $label,
			'className' => '',
		]);
	}

	/**
	 * Generates the complete `<select>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		$name = $this->get('name') ?? '';
		$className = $this->get('className') ?? '';

		return <<<HTML
		<select name="{$name}" class="{$className}">
			{$this->getDefault()}
			{$this->setupOptions()}
		</select>
HTML;
	}
}