<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\DatabaseGet;
use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\RedisUtils;
use AcMarche\Notion\Lib\ResponseUtil;
use Carbon\Carbon;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

$request = Request::createFromGlobals();
$rowId = $request->query->get("id", null);
$refresh = $request->query->get("refresh", null);
if ($rowId == 'null') {
    $rowId = 0;
}
(new Dotenv())->load(__DIR__.'/.env');
$cacheUtils = new RedisUtils();
try {
    $cacheUtils->instance();
} catch (\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}

$databaseId = $_ENV['NOTION_ACTIVITIES_DATABASE_ID'];
$key = RedisUtils::generateKey('database-activities-'.$databaseId);

if ($rowId) {
    $key .= '-'.$rowId;
}
if ($refresh) {
    $cacheUtils->delete($key);
}

try {
    $data = $cacheUtils->cache->get(
        $key,
        function (ItemInterface $item) use ($databaseId, $rowId) {
            $item->expiresAfter(RedisUtils::DURATION);
            $item->tag(RedisUtils::TAG);
            $fetch = new DatabaseGet();

            return $fetch->getEvents($databaseId, $rowId);
        },
    );

    $today = new \DateTime();
    $today->modify('+2months');
    $pages = [];
    foreach ($data['pages'] as $event) {
        $dates = $event['properties']['Date']['date'];
        $start = Carbon::parse($dates['start']);
        if ($start->format('Y-m-d') < $today->format('Y-m-d')) {
            $pages[] = $event;
        }
    }

    usort($pages, function ($eventA, $eventB) {
        $dateA = $eventA['properties']['Date']['date']['start'];
        $dateB = $eventB['properties']['Date']['date']['start'];

        return $dateA <=> $dateB;
    });

    $data['pages'] = $pages;

    return ResponseUtil::sendSuccessResponse($data, 'Get successfully database');
} catch (\Psr\Cache\InvalidArgumentException|\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}