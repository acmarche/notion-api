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
$id = $request->query->getString("id");
$refresh = $request->query->get("refresh", null);

if (!$id) {
    return ResponseUtil::send404Response('Database not found');
}

$cacheUtils = new RedisUtils();
try {
    $cacheUtils->instance();
} catch (\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}
(new Dotenv())->load(__DIR__.'/.env');

$key = RedisUtils::generateKey('database-'.$id);
if ($refresh) {
    $cacheUtils->delete($key);
}
try {
    $data = $cacheUtils->cache->get(
        $key,
        function (ItemInterface $item) use ($id) {
            $item->expiresAfter(RedisUtils::DURATION);
            $item->tag(RedisUtils::TAG);
            $fetch = new DatabaseGet();

            return $fetch->getByIdWithPages($id);
        },
    );

    return ResponseUtil::sendSuccessResponse($data, 'Get successfully database');
} catch (\Psr\Cache\InvalidArgumentException|\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}