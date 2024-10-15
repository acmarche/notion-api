<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\Menu;
use AcMarche\Notion\Lib\RedisUtils;
use AcMarche\Notion\Lib\ResponseUtil;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

$request = Request::createFromGlobals();
$refresh = $request->query->get("refresh", null);
$cacheUtils = new RedisUtils();
try {
    $cacheUtils->instance();
} catch (\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}
(new Dotenv())->load(__DIR__.'/.env');

$key = RedisUtils::generateKey('menu', $refresh);
try {
    $data = $cacheUtils->cache->get(
        $key,
        function (ItemInterface $item) {
            $item->expiresAfter(RedisUtils::DURATION_LONG);
            $item->tag(RedisUtils::TAG);

            $fetch = new Menu();

            return $fetch->getMenu();
        },
    );

    return ResponseUtil::sendSuccessResponse($data, 'Get successfully page');
} catch (\Psr\Cache\InvalidArgumentException|\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}