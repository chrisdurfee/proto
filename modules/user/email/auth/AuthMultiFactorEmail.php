<?php declare(strict_types=1);
namespace Modules\User\Email\Auth;

/**
 * AuthNewConnectionEmail
 *
 * This will handle the email for new authorized connections.
 *
 * @package Modules\User\Email\Auth
 */
class AuthMultiFactorEmail extends AuthEmail
{
    /**
     * Adds the body to the email.
     *
     * @return string
     */
    protected function addBody(): string
    {
        $props = $this->props;
        $code = $props->code ?? '000-000-0000';

        return <<<HTML
<tr>
    <td style="vertical-align:top;" class="sub-container">
        <h1>Sign-In Code</h1>
        <p>
            This code can be used to authenticate a login attempt. If you wish to validate the request, enter this code in the code area.
        </p>
        <p>
        <br>
            Code: <strong>{$code}</strong>
        </p>
        <p>
            You may use the number below to report any invalid requests trying to access your account.
        </p>
        <br>
    </td>
</tr>
{$this->addCompanySignature()}
HTML;
    }
}