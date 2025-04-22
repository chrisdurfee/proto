<?php declare(strict_types=1);

include_once __DIR__ . '/../../autoload.php';

use Proto\Base;
use Proto\Dispatch\Email\Template;
use Proto\Utils\Filter\Input;

/**
 * This will boostrap the application and load the necessary classes.
 */
new Base();

$template = Input::get('template') ?? null;
$template = !empty($template) ? $template : "Common\\Email\\BasicEmail";
$email = Template::create($template);

echo (string)$email;

?>
<style>
    html, body{
        background: #242527;
    }
    *::-webkit-scrollbar {
        background:rgba(0,0,0,.1);
        width:8px;
        height: 8px;
    }
    *::-webkit-scrollbar-thumb {
        background:rgba(0,0,0,.2) !important;
    }
</style>
<?php
