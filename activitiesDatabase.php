<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\DatabaseGet;
use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\RedisUtils;
use AcMarche\Notion\Lib\ResponseUtil;
use Notion\Databases\Query;
use Notion\Databases\Query\CompoundFilter;
use Notion\Databases\Query\DateFilter;
use Notion\Databases\Query\Sort;
use Notion\Databases\Query\StatusFilter;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

$request = Request::createFromGlobals();
$rowId = $request->query->getString("id");
$refresh = $request->query->get("refresh", null);

$cacheUtils = new RedisUtils();
try {
    $cacheUtils->instance();
} catch (\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}
(new Dotenv())->load(__DIR__.'/.env');

$databaseId = $_ENV['NOTION_ACTIVITIES_DATABASE_ID'];
$key = RedisUtils::generateKey('database-query-'.$databaseId);
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
            $database = $fetch->getById($databaseId);
            $today = new \DateTime();

            $query = Query::create()
                ->changeFilter(
                    CompoundFilter::and(
                        StatusFilter::property('Statut')->equals('Date validée (Public)'),
                        DateFilter::property('Date')->after($today->format('Y-m-d')),
                    ),
                )
                ->addSort(Sort::property("Date")->ascending());

            return $fetch->query($database, $query, $rowId);
        },
    );

    return ResponseUtil::sendSuccessResponse($data, 'Get successfully database');
} catch (\Psr\Cache\InvalidArgumentException|\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}