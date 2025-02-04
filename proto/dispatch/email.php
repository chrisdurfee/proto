<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Email
 *
 * This will send an email. The email can be plain text or HTML
 * and can have attachments.
 *
 * @package Proto\Dispatch
 */
class Email extends Dispatch
{
	/**
	 * @var string $to
	 */
	public string $to;

	/**
	 * @var string $messageType
	 */
	public string $messageType;

	/**
	 * @var string $subject
	 */
	public string $subject;

	/**
	 * @var string $from
	 */
	public string $from;

	/**
	 * @var string|null $fromName
	 */
	public ?string $fromName = null;

	/**
	 * @var string $message
	 */
	public string $message;

	/**
	 * @var array $attachments
	 */
	public ?Array $attachments = [];

	/**
	 * This will setup the email.
	 *
	 * @param string $to
	 * @param string $messageType
	 * @param string $from
	 * @param string $subject
	 * @param string $message
	 * @param array|null $attachments
	 * @return void
	 */
	public function __construct(
		string $to,
		string $messageType,
		string $from,
		string $subject,
		string $message,
		?array $attachments = null
	)
	{
		$this->to = $to;
		$this->messageType = $messageType;
		$this->from = $from;
		$this->subject = $subject;
		$this->message = $message;

		$this->addAttachments($attachments);
	}

	/**
	 * This will encode the attachment to base 64.
	 *
	 * @param string|null $file
	 * @return string|bool
	 */
	public function encodeAttachment(?string $file = ''): ?string
	{
		if (file_exists($file) === false)
		{
			return null;
		}

		$fp = fopen($file, "rb"); //Open it
		$data = fread($fp, filesize($file)); //Read it
		$data = chunk_split(base64_encode($data)); //Chunk it up and encode it as base64 so it can emailed
		fclose($fp);
		return $data;
	}

	/**
	 * This will add attachments.
	 *
	 * @param array|null $files
	 * @return self
	 */
	public function addAttachments(?array $files = null): self
	{
		if (is_array($files))
		{
			foreach ($files as $file)
			{
				if (is_file($file))
				{
					array_push($this->attachments, $file);
				}
			}
		}
		return $this;
	}

	/**
	 * This will send the email.
	 *
	 * @return Response
	 */
	public function send(): Response
	{
		if (isset($this->to) === false)
		{
			return $this->error('Invalid recipient email address.'); // This is an enum value in the messages table.
		}

		if ($this->to === 'wgi@verizon.net')
		{
			return $this->error('This email is blocked from sending.'); // This is an enum value in the messages table.
		}

		try
		{
			$response = $this->email();
			if ($response)
			{
				return Response::create();
			}

			return $this->error('The email failed to send.'); // This is an enum value in the messages table.
		}
		catch (\Exception $e)
		{
			$message = ($e) ? $e->getMessage() : 'Unable to send email error: ';
			return $this->error($message);
		}
	}

	/**
	 * This will get the from name.
	 *
	 * @return string
	 */
	protected function getFromName(): string
	{
		return (!empty($this->fromName))? $this->fromName : "DentalQore Messaging";
	}

	/**
	 * This is the boundry for multipart messages.
	 *
	 * @var string
	 */
	private $boundry = "--Multipart_Boundary_Message";

	/**
	 * This will setup the email header.
	 *
	 * @return string
	 */
	private function setupHeader(): string
	{
		$fromName = $this->getFromName();

		$attachNumber = count($this->attachments);
		if ($attachNumber > 0)
		{
			$header = "From: \"" . $fromName . "\" <" . $this->from . ">\r\n";
			$header .= "Reply-To: $this->from\r\n";
			$header .= "Return-Path: $this->from\r\n";
			$header .= "X-Sender: \"" . $fromName . "\" <" . $this->from . ">\r\n";
			$header .= "X-Mailer: PHP/" . phpversion() . "\r\n";
			$header .= "X-Priority: 3\r\n";
			$header .= "MIME-Version: 1.0\r\n";
			$header .= "Content-Type: multipart/mixed;boundary=\"$this->boundry\" \r\n";
		}
		else
		{
			if ($this->messageType === 'html')
			{
				$header = "MIME-Version: 1.0\r\n";
				$header .= "Content-type: text/html; charset=UTF-8\r\n";
				$header .= "From: \"" . $fromName . "\" <" . $this->from . ">\r\n";
				$header .= "Return-Path: <" . $this->from . ">\r\n";
			}
			else if($this->messageType === 'text')
			{
				$header = "From: $this->from\r\n";
			}
			else
			{
				$header = "MIME-Version: 1.0\r\n";
				$header .= "Content-type: text/plain; charset=UTF-8\r\n";
				$header .= "From: \"" . $fromName . "\" <" . $this->from . ">\r\n";
				$header .= "Return-Path: <" . $this->from . ">\r\n";
			}
		}

		$header .= $this->getUnsubscribeHeader();
		return $header;
	}

	/**
	 * @var string $unsubscribeUrl
	 */
	protected string $unsubscribeUrl = '';

	/**
	 * This will set the unsubscribe url.
	 *
	 * @param string $url
	 * @return self
	 */
	public function setUnsubscribeUrl(string $url): self
	{
		$this->unsubscribeUrl = $url;
		return $this;
	}

	/**
	 * This will add the unsubscribe header.
	 *
	 * @param string $header
	 * @return string
	 */
	protected function getUnsubscribeHeader(): string
	{
		if (empty($this->unsubscribeUrl))
		{
			return '';
		}

		return "List-Unsubscribe: <{$this->unsubscribeUrl}>\r\n";
	}

	/**
	 * This will create an attachment file name.
	 *
	 * @param string $file
	 * @return string
	 */
	protected static function createAttachmentName(string $file): string
	{
		$fileInfo = pathinfo($file);
		return $fileInfo['filename'] . '.' . $fileInfo['extension'];
	}

	/**
	 * This will get the attachment message.
	 *
	 * @param string $file
	 * @return string
	 */
	protected function getAttachmentMessage(string $file): string
	{
		if (is_file($file) === false)
		{
			return '';
		}

		$filename = self::createAttachmentName($file);
		$attachment = $this->encodeAttachment($file);
		if (!$attachment)
		{
			return '';
		}

		/* attachment */
		$message = "--$this->boundry\n";
		$message .= "Content-Type: application/octet-stream;\n name=\"" . $filename . "\"\n";
		$message .= "Content-Description: " . $filename . "\n";
		$message .= "Content-Disposition: attachment;\n filename=\"" . $filename . "\"; size=\"" . filesize($file) . "\";\n";
		$message .= "Content-Transfer-Encoding: base64\n\n";
		$message .= $attachment; //The base64 encoded message
		$message .= "\n\n";
		return $message;
	}

	/**
	 * This will setup the email message.
	 *
	 * @return string
	 */
	private function setupMessage(): string
	{
		if ($this->messageType === 'html')
		{
			/* this will convert the style to inline css */
			$this->message = $this->changeCssToInline($this->message);
		}

		$attachNumber = count($this->attachments);
		if ($attachNumber < 1)
		{
			return $this->message;
		}

		$message = '';
		if ($this->messageType === 'html')
		{
			/* html message */
			$message .= "--$this->boundry\n";
			$message .= "Content-Type: text/html; charset=UTF-8\n";
			$message .= "Content-Transfer-Encoding: 8bit\n";
			$message .= "\n";
			$message .= $this->message . "\n";
			$message .= "\n";
		}
		else
		{
			/* message */
			$message .= "--$this->boundry\n";
			$message .= "Content-Type: text/plain; charset=UTF-8\n";
			$message .= "Content-Transfer-Encoding: quoted-printable\n";
			$message .= "\n";
			$message .= $this->message . "\n";
			$message .= "\n";
			$message .= "--$this->boundry\n";
		}

		for ($i = 0; $i < $attachNumber; $i++)
		{
			$file = $this->attachments[$i];
			if ($file)
			{
				$message .= $this->getAttachmentMessage($file);
			}
		}

		return $message;
	}

	/**
	 * This will convert css to style attributes.
	 *
	 * @param string $message
	 * @return string
	 */
	public function changeCssToInline(string $message): string
	{
		if (isset($message))
		{
			$message = $this->emogrify($message);
		}

		return $message;
	}

	/**
	 * This will use the emogrify lib to convert the css.
	 *
	 * @param string $message
	 * @return string
	 */
	protected function emogrify(string $message): string
	{
		include_once __DIR__ . '/utils/emogrifier.php';

		try {
			$emogrifier = new \Pelago\Emogrifier($message);
			return $emogrifier->emogrify();
		}
		catch (\Exception $e)
		{

		}
		return $message;
	}

	/**
	 * This will setup the email by the settings and send the message.
	 *
	 * @return bool
	 */
	private function email(): bool
	{
		$to = $this->to;
		$subject = $this->subject;
		$message = $this->setupMessage();
		$header = $this->setupHeader();

		try {
			return mail($to, $subject, wordwrap($message,70), $header, '-f' . $this->from);
		}
		catch (\Exception $e)
		{
			return false;
		}
	}
}