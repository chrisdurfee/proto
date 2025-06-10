<?php declare(strict_types=1);
namespace Common\Services\OpenAi\Chat\Handlers;

use Common\Services\OpenAi\Chat\ModelHelper;

/**
 * Abstract Class ChatHandler
 *
 * Base class for chat handlers providing shared configuration logic.
 *
 * @abstract
 * @package Common\Services\OpenAi\Chat\Handlers
 */
abstract class ChatHandler implements ChatInterface
{
	/**
	 * The model identifier used for the chat request.
	 *
	 * @var string
	 */
	protected string $model;

	/**
	 * The randomness applied to the generated output.
	 *
	 * @var float
	 */
	protected float $temperature = 1.0;

	/**
	 * Penalty for repeated tokens.
	 *
	 * @var int
	 */
	protected int $frequencyPenalty = 0;

	/**
	 * Penalty for sticking to the same topic.
	 *
	 * @var int
	 */
	protected int $presencePenalty = 0;

	/**
	 * Maximum number of tokens allowed in the response.
	 *
	 * @var int
	 */
	protected int $maxTokens = 2000;

	/**
	 * The configuration object provided to the handler.
	 *
	 * @var object|null
	 */
	protected ?object $settings;

	/**
	 * ChatHandler constructor.
	 *
	 * @param object|null $settings
	 */
	public function __construct(?object $settings = null)
	{
		$this->settings = $settings;
		$this->setModel();
		$this->setTemperature();
		$this->setFrequency();
		$this->setPresence();
		$this->setMaxTokens();
	}

	/**
	 * Sets the model used for the chat request.
	 *
	 * @return void
	 */
	protected function setModel(): void
	{
		$this->model = ModelHelper::get($this->settings);
	}

	/**
	 * Sets the temperature value if specified in the settings.
	 *
	 * @return void
	 */
	protected function setTemperature(): void
	{
		if (isset($this->settings->temperature))
		{
			$this->temperature = $this->settings->temperature;
		}
	}

	/**
	 * Sets the maximum token count if specified in the settings.
	 *
	 * @return void
	 */
	protected function setMaxTokens(): void
	{
		if (isset($this->settings->maxTokens))
		{
			$this->maxTokens = $this->settings->maxTokens;
		}
	}

	/**
	 * Sets the frequency penalty if specified in the settings.
	 *
	 * @return void
	 */
	protected function setFrequency(): void
	{
		if (isset($this->settings->frequencyPenalty))
		{
			$this->frequencyPenalty = $this->settings->frequencyPenalty;
		}
	}

	/**
	 * Sets the presence penalty if specified in the settings.
	 *
	 * @return void
	 */
	protected function setPresence(): void
	{
		if (isset($this->settings->presencePenalty))
		{
			$this->presencePenalty = $this->settings->presencePenalty;
		}
	}

	/**
	 * Returns the model used for this handler.
	 *
	 * @return string
	 */
	public function model(): string
	{
		return $this->model;
	}

	/**
	 * Returns the maximum number of tokens allowed in the response.
	 *
	 * @return int
	 */
	public function maxTokens(): int
	{
		return $this->maxTokens;
	}

	/**
	 * Returns the temperature setting.
	 *
	 * @return float
	 */
	public function temperature(): float
	{
		return $this->temperature;
	}

	/**
	 * Returns the presence penalty setting.
	 *
	 * @return int
	 */
	public function presence(): int
	{
		return $this->presencePenalty;
	}

	/**
	 * Returns the frequency penalty setting.
	 *
	 * @return int
	 */
	public function frequency(): int
	{
		return $this->frequencyPenalty;
	}
}