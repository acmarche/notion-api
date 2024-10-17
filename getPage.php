<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\PageGet;
use AcMarche\Notion\Lib\RedisUtils;
use AcMarche\Notion\Lib\ResponseUtil;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;
use Nyholm\Psr7\Response;

$request = Request::createFromGlobals();

$pageId = $request->query->getString("page_id");
$refresh = $request->query->get("refresh", null);
if ($pageId == 'null') {
    $pageId = null;
}
if (!$pageId) {
    return ResponseUtil::send404Response('Page not found');
}

(new Dotenv())->load(__DIR__.'/.env');
$cacheUtils = new RedisUtils();
try {
    $cacheUtils->instance();
} catch (\Exception $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}
$error = $data = null;
$key = RedisUtils::generateKey('page-'.$pageId);
if ($refresh) {
    $cacheUtils->delete($key);
}
try {
    $data = $cacheUtils->cache->get(
        $key,
        function (ItemInterface $item) use ($pageId) {
            $item->expiresAfter(RedisUtils::DURATION);
            $item->tag(RedisUtils::TAG);

            $fetch = new PageGet();

            return $fetch->fetchById($pageId);
        },
    );
} catch (\Psr\Cache\InvalidArgumentException|\Exception $error) {
    Mailer::sendError($error->getMessage());
}

if ($data) {
    return ResponseUtil::sendSuccessResponse($data, 'Get successfully page');
}

if ($error instanceof Response && $error->getStatusCode() === 404) {
    return ResponseUtil::send404Response($error->getReasonPhrase());
}

return ResponseUtil::sendErrorResponse($error?->getMessage());
