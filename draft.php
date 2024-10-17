<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\DatabaseGet;
use AcMarche\Notion\Lib\RedisUtils;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Contracts\Cache\ItemInterface;

(new Dotenv())->load(__DIR__.'/.env');

$fetch = new DatabaseGet();
$cacheUtils = new RedisUtils();
$cacheUtils->instance();
$databaseId = $_ENV['NOTION_ACTIVITIES_DATABASE_ID'];

$key = RedisUtils::generateKey('database-activities-'.$databaseId);
$data = $cacheUtils->cache->get(
    $key,
    function (ItemInterface $item) use ($databaseId) {
        $item->expiresAfter(RedisUtils::DURATION);
        $item->tag(RedisUtils::TAG);
        $fetch = new DatabaseGet();

        return $fetch->getEvents($databaseId, null);
    },
);

exit();
$rooms = $data['relations']['Salles'];
foreach ($data['pages'] as $event) {
    foreach ($event['properties']['Salles']['relation'] as $relationId) {
        foreach ($rooms as $room) {
            if ($room['id'] == $relationId['id']) {
                dd(5555, $room);
            }
        }
    }
}