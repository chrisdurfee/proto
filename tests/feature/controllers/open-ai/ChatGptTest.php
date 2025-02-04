<?php declare(strict_types=1);
namespace Tests\Feature\Controllers\OpenAi;

use Tests\Test;
use App\Controllers\OpenAi\OpenAi;
use App\Controllers\Chat\ChatFactory;

/**
 * ChatGptTest
 */
final class ChatGptTest extends Test
{
    /**
     * @var OpenAi $controller
     */
    protected OpenAi $controller;

    /**
     * This will be called when the test is set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (class_exists(OpenAi::class))
        {
            $this->controller = new OpenAi();
        }
    }

    /**
     * This will check tf the controller is set up in composer.
     *
     * @return void
     */
    public function testHasClass(): void
    {
        $this->assertTrue(class_exists(OpenAi::class));
    }

    /**
	 * This is an example bool test.
	 *
	 * @return void
	 */
	public function testChat(): void
	{
        $prompt = "When was porsche founded?";
        $model = "gpt-3.5-turbo";
        $systemSettings = ChatFactory::getHandler('AssistantChat', (object)[
            'model' => $model
        ]);

        $result = $this->controller->chat()->generate(
            $prompt,
            $systemSettings->getSystemContent(),
            $systemSettings
        );

        if (isset($result->choices))
        {
            $choices = $result->choices;
            $firstChoice = $choices[0];
            $this->assertNotNull($firstChoice);
        }

        if (isset($result->error))
        {
		    $this->assertEquals($result->type, 'insufficient_quota', 'The billing has invalid funds.');
        }
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
}