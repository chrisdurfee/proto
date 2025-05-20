<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Http\Router\Request;
use Proto\Dispatch\Text\Template;
use Proto\Dispatch\Dispatcher;

/**
 * SmsController
 *
 * Handles sms operations.
 *
 * @package Modules\Developer\Controllers
 */
class SmsController extends Controller
{
	/**
	 * Previews the sms template.
	 *
	 * @param Request $req The request object.
	 * @return void
	 */
	public function preview(Request $req): void
	{
		$template = $req->input('template') ?? null;
		$template = !empty($template) ? $template : "Common\\Text\\BasicText";

		$sms = Template::create($template);
		echo (string)$sms;
		die;
	}

	/**
	 * Sends a test sms.
	 *
	 * @param Request $req The request object.
	 * @return object
	 */
	public function test(Request $req): object
	{
		$toAddress = $req->input('to') ?? null;
		$settings = (object)[
			'to' => $toAddress,
			'template' => 'Modules\\Developer\\Text\\Test\\TestText',
			'queue' => (bool)$req->input('queue')
		];

		return Dispatcher::sms($settings);
	}
}