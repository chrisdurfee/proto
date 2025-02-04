<?php declare(strict_types=1);
namespace Proto\Controllers\Sms;

use Proto\Config;
use Proto\Integrations\Sms\ZipwhipProvision;
use Proto\Integrations\Sms\Zipwhip;
use Proto\Models\Zipwhip as ZipwhipModel;
use Proto\Utils\Strings;

/**
 * ZipwhipController
 *
 * This is the zipwhip controller class. This will be used to send text messages.
 *
 * @package Proto\Controllers\Sms
 */
class ZipwhipController extends SmsController
{
	/**
	 * @var string $apiKey
	 */
	protected string $apiKey;

	/**
	 * This will construct the class.
	 *
	 * @param ZipwhipProvision $provisionApi
	 * @return void
	 */
	public function __construct(
		protected ZipwhipProvision $provisionApi = new ZipwhipProvision(),
		protected Zipwhip $api = new Zipwhip()
	)
    {
        parent::__construct();
		$this->apiKey = $this->getApiKey();
	}

	/**
	 * This will get the api key.
	 *
	 * @return string
	 */
	protected function getApiKey(): string
	{
		if (isset($this->apiKey))
		{
			return $this->apiKey;
		}

		$settings = Config::getInstance();
		return ($this->apiKey = $settings->apis->zipwhip);
	}

	/**
	 * This will check if a number is eligible.
	 *
	 * @param string $number
	 * @return bool
	 */
	public function checkEligiblity(string $number): bool
	{
		$result = $this->provisionApi->eligible($number);

		$data = $result->data;
		return ($data->eligible === 'false')? false : true;
	}

	/**
	 * This will provision a number.
	 *
	 * @param string $number
	 * @param mixed $clientId
	 * @return object|bool
	 */
	protected function addProvision(string $number, mixed $clientId): object|bool
	{
		$result = $this->provisionApi->add($number);

		$data = $result->data;
		if ($data->status_code != 100 || $data->error != 'false')
		{
			return false;
		}

		$this->addToPending($data, $number, $clientId);

		return $data;
	}

	/**
	 * This will add a number to the pending table.
	 *
	 * @param object|null $data
	 * @param mixed $number
	 * @param mixed $clientId
	 * @return boolean
	 */
	protected function addToPending(?object $data, mixed $number, mixed $clientId): bool
	{
		$model = new ZipwhipModel((object)[
			'clientId' => $clientId,
			'number' => $number,
			'password' => $data->password,
			'session' => '',
			'status' => 'pending'
		]);
		return $model->setup();
	}

	/**
	 * This will get the status of a number.
	 *
	 * @param mixed $number
	 * @return object|bool
	 */
	public function status(mixed $number): object|bool
	{
		$result = $this->provisionApi->status($number);

		$data = $result->data;
		if ($data->status_code != 100 || $data->error != 'false')
		{
			return false;
		}

		return $data;
	}

	/**
	 * This will add the new hooks.
	 *
	 * @return void
	 */
	public function addNewHooks(): void
	{
		$model = new ZipwhipModel();
		$active = $model->allActive();
		if (empty($active))
		{
			return;
		}

		foreach ($active as $row)
		{
			$this->api->addProgressHook($row->session, $row->clientId);
		}
	}

	/**
	 * This will login to zipwhip.
	 *
	 * @param mixed $number
	 * @param string $password
	 * @param mixed $clientId
	 * @return object|bool
	 */
	public function login(mixed $number, string $password, mixed $clientId): object|bool
	{
		$result = $this->api->login($number, $password);

		$data = $result->data;
		if ($data->success != true)
		{
			return false;
		}

		$this->api->addHook($data->response, 'Receive', $clientId);
		//$this->api->addProgressHook($data->response, $clientId);

		return $data;
	}

	/**
	 * This will send a message.
	 *
	 * @param string $session
	 * @param string $to
	 * @param string $message
	 * @return object|bool
	 */
	public function send(string $session, string $to, string $message): object|bool
	{
		$result = $this->api->send($session, $to, $message);
		return ($result->success == true)? $result : false;
	}

	/**
	 * This will provision a number.
	 *
	 * @param mixed $number
	 * @param mixed $clientId
	 * @return object
	 */
	public static function provision(mixed $number, mixed $clientId): object
	{
		$controller = new static();
		return $controller->provisionNumber($number, $clientId);
	}

	/**
	 * This will provision a number.
	 *
	 * @param mixed $number
	 * @param mixed $clientId
	 * @return object
	 */
	public function provisionNumber(mixed $number, mixed $clientId): object
	{
		if (empty($number))
		{
			return $this->response(false, 'No number');
		}

		$number = Strings::cleanPhone($number);
		if (strlen($number) !== 10)
		{
			return $this->response(false, 'Invalid number');
		}

		$result = $this->checkEligiblity($number);
		if (!$result)
		{
			return $this->response(false, 'Not eligible');
		}

		$result = $this->addProvision($number, $clientId);
		if (!$result)
		{
			return $this->response(false, 'Could not add number');
		}

		return $this->response($result);
	}
}