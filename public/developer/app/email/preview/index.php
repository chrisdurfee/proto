<?php declare(strict_types=1);

include_once __DIR__ . '/../../autoload.php';

use Proto\Dispatch\Email\Template;
use Proto\Utils\Filter\Input;

$template = Input::get('template') ?? "Common\\Email\\BasicEmail";
$email = Template::create($template);

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
