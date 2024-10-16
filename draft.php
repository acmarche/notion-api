<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\DatabaseGet;
use AcMarche\Notion\Lib\RedisUtils;
use AcMarche\Notion\Lib\RelationsEnum;
use AcMarche\Notion\Lib\ResponseUtil;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->load(__DIR__.'/.env');

$fetch = new DatabaseGet();
$databaseId = $_ENV['NOTION_ACTIVITIES_DATABASE_ID'];
$key = RedisUtils::generateKey('database-activities-'.$databaseId);
//echo "Events database \n";

$database = $fetch->getById($databaseId);
$pages = $fetch->addRelations($database, RelationsEnum::events);

    return ResponseUtil::sendSuccessResponse($pages, 'Get successfully database');
dd($pages);
foreach ($database->properties as $property) {
    if ($property->metadata()->name === 'Salles') {
        if ($property->metadata()->type->value === 'relation') {
            $databaseId = $property->databaseId;
            $databaseRooms = $fetch->getByIdWithPages($databaseId);
            foreach ($databaseRooms['pages'] as $page) {
                dd($page);
            }
        }
    }
}

return;
$events = $fetch->getEvents($databaseId);

foreach ($events as $event) {
    $data = $fetch->getEvents($databaseId, $event['id']);
}