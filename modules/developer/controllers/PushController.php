<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Http\Router\Request;
use Proto\Dispatch\Email\Template;
use Proto\Dispatch\Dispatcher;

/**
 * PushController
 *
 * Handles push notification operations.
 *
 * @package Modules\Developer\Controllers
 */
class PushController extends Controller
{
	/**
	 * Previews the push notification template.
	 *
	 * @param Request $req The request object.
	 * @return void
	 */
	public function preview(Request $req): void
	{
		$template = $req->input('template') ?? null;
		$template = !empty($template) ? $template : "Common\\Push\\PushTest";

		$push = Template::create($template);
		echo (string)$push;
		die;
	}

	/**
	 * Sends a test push notification.
	 *
	 * @param Request $req The request object.
	 * @return object
	 */
	public function test(Request $req): object
	{
		$userId = $req->input('userId') ?? null;
        if ($userId === null)
        {
            return $this->error('User ID is required');
        }

		$settings = (object)[
			'subscriptions' => [$userId],
			'template' => 'Modules\\Developer\\Push\\Test\\TestPush',
			'queue' => (bool)$req->input('queue')
		];

		return Dispatcher::push($settings);
	}
}