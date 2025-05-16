<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Http\Router\Request;
use Proto\Dispatch\Email\Template;

/**
 * EmailController
 *
 * Handles email operations.
 *
 * @package Modules\Developer\Controllers
 */
class EmailController extends Controller
{
	/**
	 * Previews the email template.
	 *
	 * @param Request $req The request object.
	 * @return void
	 */
	public function preview(Request $req): void
	{
		$template = $req->input('template') ?? null;
		$template = !empty($template) ? $template : "Common\\Email\\BasicEmail";

		$email = Template::create($template);
		echo (string)$email;
		die;
	}
}