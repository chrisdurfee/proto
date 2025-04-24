<?php declare(strict_types=1);
namespace Modules\User\Services\Password;

use Modules\User\Models\User;
use Modules\User\Models\PasswordRequest;
use Proto\Dispatch\Enqueuer;
use Modules\User\Auth\Gates\PasswordRequestGate;

/**
 * PasswordService
 *
 * @package Modules\User\Services\Password
 */
class PasswordService
{
    /** @var PasswordRequestGate|null */
    protected static ?PasswordRequestGate $gate = null;

    /**
     * Lazy-load the password request gate.
     *
     * @return PasswordRequestGate
     */
    protected static function gate(): PasswordRequestGate
    {
        return self::$gate ?? (self::$gate = new PasswordRequestGate());
    }

    /**
     * This will get the password model.
     *
     * @return PasswordRequest
     */
    protected function model(): PasswordRequest
    {
        return new PasswordRequest();
    }

    /**
     * Kick off a new password reset request for the given user.
     *
     * @param User $user
     * @param string $type
     * @return object
     */
    public function sendResetRequest(User $user, string $type = 'email'): object
    {
        $model = $this->model();
        $model->set('userId', $user->id);
        $model->add();
        $requestId = $model->requestId;

        self::gate()->setRequest($requestId, $user->id);

        return $this->dispatchRequest($user, $type, $requestId);
    }

    /**
     * Route the reset request through email or SMS.
     *
     * @param User $user
     * @param string $type
     * @param string $requestId
     * @return object
     */
    protected function dispatchRequest(User $user, string $type, string $requestId): object
    {
        return $type === 'sms'
            ? $this->textRequest($user, $requestId)
            : $this->emailRequest($user, $requestId);
    }

    /**
     * Send the reset link/code via email.
     *
     * @param User $user
     * @param string $requestId
     * @return object
     */
    protected function emailRequest(User $user, string $requestId): object
    {
        $settings = (object)[
            'to' => $user->email,
            'subject' => 'Password Reset Request',
            'template' => 'Modules\\User\\Email\\Password\\PasswordResetRequestEmail'
        ];
        $data = (object)[
            'username' => $user->username,
            'resetUrl' => $this->buildResetUrl($requestId, $user->id)
        ];

        return $this->dispatchEmail($settings, $data);
    }

    /**
     * Send the reset link/code via SMS.
     *
     * @param User $user
     * @param string $requestId
     * @return object
     */
    protected function textRequest(User $user, string $requestId): object
    {
        $settings = (object)[
            'to' => $user->mobile,
            'session' => $this->getSmsSession(),
            'template' => 'Modules\\User\\Text\\Password\\PasswordResetRequestText'
        ];
        $data = (object)[
            'code' => $requestId,
            'resetUrl' => $this->buildResetUrl($requestId, $user->id)
        ];

        return $this->dispatchText($settings, $data);
    }

    /**
     * Build the public URL for resetting.
     *
     * @param string $requestId
     * @param int $userId
     * @return string
     */
    protected function buildResetUrl(string $requestId, int $userId): string
    {
        return envUrl()
            . '/api/user/auth/password-reset?requestId='
            . $requestId
            . '&userId='
            . $userId;
    }

    /**
     * Complete the password reset.
     *
     * @param string $requestId
     * @param int $userId
     * @param string $newPassword
     * @param string $type
     * @return bool
     */
    public function resetPassword(
        string $requestId,
        int $userId,
        string $newPassword,
        string $type = 'email'
    ): bool {
        $username = self::gate()->validateRequest($requestId, $userId);
        if ($username === null)
        {
            return false;
        }

        $user = User::find($userId);
        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->save();

        $this->storage()->updateStatusByRequest($requestId);
        self::gate()->resetRequest();

        $this->dispatchSuccess($user, $type);
        return true;
    }

    /**
     * Route the success notification through email or SMS.
     *
     * @param User $user
     * @param string $type
     * @return object
     */
    protected function dispatchSuccess(User $user, string $type): object
    {
        return $type === 'sms'
            ? $this->textSuccess($user)
            : $this->emailSuccess($user);
    }

    /**
     * Email confirmation of a successful password reset.
     *
     * @param User $user
     * @return object
     */
    protected function emailSuccess(User $user): object
    {
        $settings = (object)[
            'to' => $user->email,
            'subject' => 'Your Password Has Been Reset',
            'template' => 'Modules\\User\\Email\\Password\\PasswordResetSuccessEmail'
        ];

        return $this->dispatchEmail($settings);
    }

    /**
     * SMS confirmation of a successful password reset.
     *
     * @param User $user
     * @return object
     */
    protected function textSuccess(User $user): object
    {
        $settings = (object)[
            'to' => $user->mobile,
            'session' => $this->getSmsSession(),
            'template' => 'Modules\\User\\Text\\Password\\PasswordResetSuccessText'
        ];

        return $this->dispatchText($settings);
    }

    /**
     * Queue an email via the app’s enqueue system.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    protected function dispatchEmail(object $settings, ?object $data = null): object
    {
        return Enqueuer::email($settings, $data);
    }

    /**
     * Queue an SMS via the app’s enqueue system.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    protected function dispatchText(object $settings, ?object $data = null): object
    {
        return Enqueuer::sms($settings, $data);
    }

    /**
     * Helper to get the SMS “from” ID.
     *
     * @return string
     */
    protected function getSmsSession(): string
    {
        $sms = env('sms');
        return $sms->fromSendId;
    }
}
