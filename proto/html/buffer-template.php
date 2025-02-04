<?php declare(strict_types=1);
namespace Proto\Html;

/**
 * BufferTemplate
 *
 * This will create a buffer template.
 *
 * @package Proto\Html
 * @abstract
 */
abstract class BufferTemplate extends Template
{
	/**
	 * This is a buffer object used to render the tempalte body offscreen.
	 *
	 * @var Buffer $buffer
	 */
	protected Buffer $buffer;

	/**
	 * This will set up the buffer template.
	 *
	 * @return void
	 */
    public function __construct()
    {
        parent::__construct();
		$this->buffer = new Buffer();
    }

	/**
	 * This will render the html to the screen.
	 *
	 * @return string
	 */
	public function render(): string
	{
		$buffer = $this->buffer;
		$buffer->start();

		$this->getBody();

		echo $buffer->getContentsAndEnd();
		return '';
	}
}
