<?php declare(strict_types=1);
namespace Proto\Html\Email;

class ExampleEmail extends Email
{
    /**
     * This will get the header.
     *
     * @return string
     */
    protected function addHeader()
    {
        return <<<EOT
            <table>
                <tr>
                    <td></td>
                </tr>
            </table>
EOT;
    }

    /**
     * This will get the body.
     *
     * @return string
     */
    protected function addBody()
    {
        return <<<EOT
            <table>
                <tr>
                    <td></td>
                </tr>
            </table>
EOT;
    }

    /**
     * This will get the footer.
     *
     * @return string
     */
    protected function addFooter()
    {
        return <<<EOT
            <table>
                <tr>
                    <td></td>
                </tr>
            </table>
EOT;
    }
}
