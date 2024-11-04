<?php

use AcMarche\Notion\Lib\GrrCheckup;
use AcMarche\Notion\Lib\ResponseUtil;
use Carbon\Carbon;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require "vendor/autoload.php";

(new Dotenv())->load(__DIR__.'/.env');
$request = Request::createFromGlobals();

$grr = new GrrCheckup();
$result = $grr->execute();
foreach ($result as $row) {
    echo $row['name'].' '.Carbon::createFromTimestamp($row['start_time'])->format('Y-m-d').PHP_EOL;
}

return;