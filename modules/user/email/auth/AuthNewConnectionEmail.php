<?php declare(strict_types=1);
namespace Modules\User\Email\Auth;

/**
 * AuthNewConnectionEmail
 *
 * This will handle the email for new authorized connections.
 *
 * @package Modules\User\Email\Auth
 */
class AuthNewConnectionEmail extends AuthEmail
{
    /**
     * Adds the body to the email.
     *
     * @return string
     */
    protected function addBody(): string
    {
        return <<<HTML
<tr>
    <td style="vertical-align:top;" class="sub-container">
        <h1>New Authorized Connection</h1>
        <p>
            A new multi-factor authorized connection has been added to your account. If this is invalid, please contact our office with the number below.
        <br>
    </td>
</tr>
{$this->addCompanySignature()}
HTML;
    }
}