<?php declare(strict_types=1);
namespace Proto\Dispatch;

use Proto\Dispatch\Utils\Emogrifier;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class Email
 *
 * This class is responsible for sending emails. It supports plain text and HTML messages,
 * including inline CSS conversion and file attachments.
 *
 * @package Proto\Dispatch
 */
class Email extends Dispatch
{
	/**
	 * Recipient email address.
	 *
	 * @var string
	 */
	public string $to;

	/**
	 * Message type. Expected values are 'html' or 'text'.
	 *
	 * @var string
	 */
	public string $messageType;

	/**
	 * Email subject.
	 *
	 * @var string
	 */
	public string $subject;

	/**
	 * Sender email address.
	 *
	 * @var string
	 */
	public string $from;

	/**
	 * Optional sender name.
	 *
	 * @var string|null
	 */
	public ?string $fromName = null;

	/**
	 * Email body content.
	 *
	 * @var string
	 */
	public string $message;

	/**
	 * List of attachment file paths.
	 *
	 * @var array
	 */
	public array $attachments = [];

	/**
	 * PHPMailer instance for SMTP email sending.
	 *
	 * @var PHPMailer|null
	 */
	private ?PHPMailer $mailer = null;

	/**
	 * Controls whether emails are actually sent (useful for development).
	 *
	 * @var bool
	 */
	private bool $sendingEnabled = true;

	/**
	 * Unsubscribe URL for the email.
	 *
	 * @var string
	 */
	protected string $unsubscribeUrl = '';

	/**
	 * Boundary string for multipart messages.
	 *
	 * @var string
	 */
	private string $boundary = '--Multipart_Boundary_Message';

	/**
	 * List of blocked recipient email addresses.
	 *
	 * @var array
	 */
	private const BLOCKED_RECIPIENTS = [];

	/**
	 * Email constructor.
	 *
	 * @param string $to Recipient email address.
	 * @param string $messageType Message type ('html' or 'text').
	 * @param string $from Sender email address.
	 * @param string $subject Email subject.
	 * @param string $message Email body message.
	 * @param array|null $attachments List of attachment file paths.
	 */
	public function __construct(
		string $to,
		string $messageType,
		string $from,
		string $subject,
		string $message,
		?array $attachments = null,
		?string $fromName = null
	)
	{
		$this->to = $to;
		$this->messageType = $messageType;
		$this->from = $from;
		$this->subject = $subject;
		$this->message = $message;
		$this->fromName = $fromName;
		$this->addAttachments($attachments);
		$this->initializeMailer();
	}

	/**
	 * Initialize PHPMailer with SMTP configuration.
	 *
	 * @return void
	 */
	private function initializeMailer(): void
	{
		$this->mailer = new PHPMailer(true);
		/**
		 * Enable or disable email sending using the environment configuration.
		 */
		$settings = env('email')->smtp ?? (object) [];
		// Check for testing environment or array driver
		if (env('env') === 'testing')
		{
			$this->sendingEnabled = false;
		}
		else
		{
			$this->sendingEnabled = $settings->sendingEnabled ?? true;
		}

		try
		{
			// Server settings
			$this->mailer->isSMTP();
			$this->mailer->Host = $settings->host ?? '';
			$this->mailer->SMTPAuth = true;
			$this->mailer->Username = $settings->username ?? '';
			$this->mailer->Password = $settings->password ?? '';

			// Encryption and port
			$encryption = $settings->encryption ?? 'tls';
			if ($encryption === 'ssl')
			{
				$this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
				$this->mailer->Port = (int) ($settings->port ?? 465);
			}
			else
			{
				$this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
				$this->mailer->Port = (int) ($settings->port ?? 587);
			}

			// Default sender
			$fromAddress = $settings->fromAddress ?? $this->from;
			$fromName = $settings->fromName ?? $this->getFromName();
			$this->mailer->setFrom($fromAddress, $fromName);

		}
		catch (Exception $e)
		{
			error(
				"Email SMTP Configuration Error: " . $e->getMessage(),
				__FILE__,
				__LINE__
			);
		}
	}

	/**
	 * Encodes the attachment file content to base64.
	 *
	 * @param string|null $file File path.
	 *
	 * @return string|null Returns the base64 encoded content or null if the file cannot be read.
	 */
	public function encodeAttachment(?string $file = null): ?string
	{
		if ($file === null || !file_exists($file))
		{
			return null;
		}

		$data = file_get_contents($file);
		if ($data === false)
		{
			return null;
		}

		return chunk_split(base64_encode($data));
	}

	/**
	 * Adds attachments to the email.
	 *
	 * @param array|null $files List of file paths.
	 *
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
					$this->attachments[] = $file;
				}
			}
		}

		return $this;
	}

	/**
	 * Sends the email.
	 *
	 * @return Response
	 */
	public function send(): Response
	{
		if (!$this->isValidEmail($this->to))
		{
			return $this->error('Invalid recipient email address.');
		}

		if ($this->isBlockedRecipient($this->to))
		{
			return $this->error('This email is blocked from sending.');
		}

		$sent = $this->email();
		if ($sent)
		{
			return Response::create();
		}

		return $this->error('The email failed to send.');
	}

	/**
	 * Retrieves the sender's name.
	 *
	 * @return string
	 */
	protected function getFromName(): string
	{
		$siteName = env('siteName');
		return !empty($this->fromName) ? $this->fromName : "{$siteName}";
	}

	/**
	 * Sets the unsubscribe URL.
	 *
	 * @param string $url Unsubscribe URL.
	 *
	 * @return self
	 */
	public function setUnsubscribeUrl(string $url): self
	{
		$this->unsubscribeUrl = $url;
		return $this;
	}

	/**
	 * Retrieves the unsubscribe header if an unsubscribe URL is set.
	 *
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
	 * Creates a formatted attachment file name.
	 *
	 * @param string $file File path.
	 *
	 * @return string
	 */
	protected static function createAttachmentName(string $file): string
	{
		$fileInfo = pathinfo($file);
		return $fileInfo['filename'] . '.' . $fileInfo['extension'];
	}

	/**
	 * Generates the MIME part for an attachment.
	 *
	 * @param string $file File path.
	 *
	 * @return string
	 */
	protected function getAttachmentMessage(string $file): string
	{
		if (!is_file($file))
		{
			return '';
		}

		$filename   = self::createAttachmentName($file);
		$attachment = $this->encodeAttachment($file);
		if ($attachment === null)
		{
			return '';
		}

		$message  = "--{$this->boundary}\r\n";
		$message .= "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n";
		$message .= "Content-Description: {$filename}\r\n";
		$message .= "Content-Disposition: attachment; filename=\"{$filename}\"; size=\"" . filesize($file) . "\"\r\n";
		$message .= "Content-Transfer-Encoding: base64\r\n\r\n";
		$message .= $attachment . "\r\n\r\n";

		return $message;
	}

	/**
	 * Prepares the email message body including attachments.
	 *
	 * @return string
	 */
	private function setupMessage(): string
	{
		$messageContent = $this->message;

		if ($this->messageType === 'html')
		{
			$messageContent = $this->changeCssToInline($messageContent);
		}

		$attachmentCount = count($this->attachments);
		if ($attachmentCount < 1)
		{
			return $messageContent;
		}

		$formattedMessage = '';
		if ($this->messageType === 'html')
		{
			$formattedMessage .= "--{$this->boundary}\r\n";
			$formattedMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
			$formattedMessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
			$formattedMessage .= $messageContent . "\r\n\r\n";
		}
		else
		{
			$formattedMessage .= "--{$this->boundary}\r\n";
			$formattedMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
			$formattedMessage .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
			$formattedMessage .= $messageContent . "\r\n\r\n";
		}

		foreach ($this->attachments as $file)
		{
			$formattedMessage .= $this->getAttachmentMessage($file);
		}

		return $formattedMessage;
	}

	/**
	 * Converts CSS in the HTML message to inline styles.
	 *
	 * @param string $message HTML message content.
	 *
	 * @return string
	 */
	public function changeCssToInline(string $message): string
	{
		return $this->emogrify($message);
	}

	/**
	 * Applies the Emogrifier to inline CSS styles.
	 *
	 * @param string $message HTML message content.
	 *
	 * @return string
	 */
	protected function emogrify(string $message): string
	{
		// Ensure the Emogrifier class is available.
		if (!class_exists(Emogrifier::class))
		{
			include_once __DIR__ . '/Utils/Emogrifier.php';
		}

		try
		{
			$emogrifier = new Emogrifier($message);
			return $emogrifier->emogrify();
		}
		catch (\Exception $e)
		{
			// If inlining fails, return the original message.
			return $message;
		}
	}

	/**
	 * Constructs the email header.
	 *
	 * @return string
	 */
	private function setupHeader(): string
	{
		$fromName = $this->getFromName();
		$header = '';

		if (count($this->attachments) > 0)
		{
			$header = "From: \"{$fromName}\" <{$this->from}>\r\n";
			$header .= "Reply-To: {$this->from}\r\n";
			$header .= "Return-Path: {$this->from}\r\n";
			$header .= "X-Sender: \"{$fromName}\" <{$this->from}>\r\n";
			$header .= "X-Mailer: PHP/" . phpversion() . "\r\n";
			$header .= "X-Priority: 3\r\n";
			$header .= "MIME-Version: 1.0\r\n";
			$header .= "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"\r\n";
		}
		else
		{
			if ($this->messageType === 'html')
			{
				$header = "MIME-Version: 1.0\r\n";
				$header .= "Content-Type: text/html; charset=UTF-8\r\n";
				$header .= "From: \"{$fromName}\" <{$this->from}>\r\n";
				$header .= "Return-Path: <{$this->from}>\r\n";
			}
			elseif ($this->messageType === 'text')
			{
				$header = "From: {$this->from}\r\n";
			}
			else
			{
				$header = "MIME-Version: 1.0\r\n";
				$header .= "Content-Type: text/plain; charset=UTF-8\r\n";
				$header .= "From: \"{$fromName}\" <{$this->from}>\r\n";
				$header .= "Return-Path: <{$this->from}>\r\n";
			}
		}

		$header .= $this->getUnsubscribeHeader();

		return $header;
	}

	/**
	 * Sends the email using PHPMailer with SMTP.
	 *
	 * @return bool True if the email was sent successfully, false otherwise.
	 */
	private function email(): bool
	{
		if (!$this->sendingEnabled)
		{
			return true;
		}

		if (!$this->mailer)
		{
			return false;
		}

		try
		{
			// Clear any previous recipients and attachments
			$this->mailer->clearAddresses();
			$this->mailer->clearAttachments();

			// Add recipient
			$this->mailer->addAddress($this->to);

			// Set subject
			$this->mailer->Subject = $this->subject;

			// Set message content
			if ($this->messageType === 'html')
			{
				$this->mailer->isHTML(true);
				$this->mailer->Body = $this->changeCssToInline($this->message);
				// Add plain text alternative if possible
				$this->mailer->AltBody = strip_tags($this->message);
			}
			else
			{
				$this->mailer->isHTML(false);
				$this->mailer->Body = $this->message;
			}

			// Add attachments
			foreach ($this->attachments as $file)
			{
				if (is_file($file))
				{
					$filename = self::createAttachmentName($file);
					$this->mailer->addAttachment($file, $filename);
				}
			}

			// Send the email
			return $this->mailer->send();

		}
		catch (Exception $e)
		{
			error(
				"Email sending error: " . $e->getMessage(),
				__FILE__,
				__LINE__
			);
			return false;
		}
	}

	/**
	 * Enable or disable email sending (useful for development).
	 *
	 * @param bool $enabled Whether to enable email sending.
	 *
	 * @return self
	 */
	public function setSendingEnabled(bool $enabled): self
	{
		$this->sendingEnabled = $enabled;
		return $this;
	}

	/**
	 * Check if email sending is enabled.
	 *
	 * @return bool
	 */
	public function isSendingEnabled(): bool
	{
		return $this->sendingEnabled;
	}

	/**
	 * Get the PHPMailer instance for advanced configuration.
	 *
	 * @return PHPMailer|null
	 */
	public function getMailer(): ?PHPMailer
	{
		return $this->mailer;
	}

	/**
	 * Validates an email address.
	 *
	 * @param string $email Email address.
	 *
	 * @return bool True if the email address is valid, false otherwise.
	 */
	private function isValidEmail(string $email): bool
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * Checks if a recipient email address is blocked.
	 *
	 * @param string $email Email address.
	 *
	 * @return bool True if the email is blocked, false otherwise.
	 */
	private function isBlockedRecipient(string $email): bool
	{
		return in_array($email, self::BLOCKED_RECIPIENTS, true);
	}
}