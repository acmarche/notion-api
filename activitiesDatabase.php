<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\DatabaseGet;
use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\RedisUtils;
use AcMarche\Notion\Lib\ResponseUtil;
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

    return ResponseUtil::sendSuccessResponse($data, 'Get successfully database');
} catch (\Psr\Cache\InvalidArgumentException|\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}