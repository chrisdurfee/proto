<?php declare(strict_types=1);
namespace Proto\Html;

/**
 * Class BufferTemplate
 *
 * A template that uses output buffering for rendering.
 *
 * @package Proto\Html
 * @abstract
 */
abstract class BufferTemplate extends Template
{
	/**
	 * @var Buffer $buffer A buffer object used to render the template body offscreen.
	 */
	protected Buffer $buffer;

	/**
	 * Initializes the buffer template.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->buffer = new Buffer();
	}

	/**
	 * Renders the HTML content using output buffering.
	 *
	 * @return string The rendered HTML output.
	 */
	public function render(): string
	{
		$this->buffer->start();

		// Capture output from getBody()
		echo $this->getBody();

		return $this->buffer->getContentsAndEnd();
	}
}