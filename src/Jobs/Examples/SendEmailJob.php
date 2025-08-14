<?php declare(strict_types=1);
namespace Proto\Jobs\Examples;

use Proto\Dispatch\Dispatcher;
use Proto\Jobs\Job;

/**
 * SendEmailJob
 *
 * Example job for sending emails.
 *
 * @package Proto\Jobs\Examples
 */
class SendEmailJob extends Job
{
	/**
	 * @var string $queue The queue name for this job
	 */
	protected string $queue = 'emails';

	/**
	 * @var int $timeout Job timeout in seconds
	 */
	protected int $timeout = 120;

	/**
	 * Execute the job.
	 *
	 * @param mixed $data The job data
	 * @return mixed The result of the job execution
	 */
	public function handle(mixed $data): mixed
	{
		// Validate required data
		if (!isset($data['to']) || !isset($data['subject']) || !isset($data['body'])) {
			throw new \InvalidArgumentException('Email job requires to, subject, and body');
		}

		$to = $data['to'];
		$subject = $data['subject'];
		$body = $data['body'];
		$from = $data['from'] ?? 'noreply@example.com';

		// Log the email sending attempt
		error_log("Sending email to: {$to}, Subject: {$subject}");

		try {
			// Here you would integrate with your email service
			// For demonstration, we'll simulate sending
			$this->sendEmail($to, $from, $subject, $body);

			error_log("Email sent successfully to: {$to}");
			return ['status' => 'sent', 'to' => $to, 'sent_at' => date('Y-m-d H:i:s')];

		} catch (\Exception $e) {
			error_log("Failed to send email to {$to}: " . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Handle job failure.
	 *
	 * @param \Throwable $exception
	 * @param mixed $data
	 * @return void
	 */
	public function failed(\Throwable $exception, mixed $data): void
	{
		$to = $data['to'] ?? 'unknown';
		error_log("Email job failed permanently for {$to}: " . $exception->getMessage());

		// Here you could notify administrators, log to a special failure log, etc.
	}

	/**
	 * Simulate sending an email.
	 *
	 * @param string $to
	 * @param string $from
	 * @param string $subject
	 * @param string $body
	 * @return object
	 */
	protected function sendEmail(string $to, string $from, string $subject, string $body): object
	{
		$settings = (object)[
            'to' => $to,
            'from' => $from,
            'subject' => $subject,
            'template' => ''
        ];

        $bayload = (object)[
            'body' => $body
        ];

		return Dispatcher::email($settings, $bayload);
	}
}
