<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\Menu;
use AcMarche\Notion\Lib\PageGet;
use AcMarche\Notion\Lib\RedisUtils;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Contracts\Cache\ItemInterface;

$fetchMenu = new Menu();
$cacheUtils = new RedisUtils();
$fetch = new PageGet();

try {
    $cacheUtils->instance();
} catch (\Exception $e) {
    Mailer::sendError($e->getMessage());
    exit();
}

(new Dotenv())->load(__DIR__.'/.env');
foreach ($fetchMenu->getMenu() as $page) {
    $key = RedisUtils::generateKey('page-'.$page['id']);
    //$cacheUtils->delete($key);
    try {
        $cacheUtils->cache->get(
            $key,
            function (ItemInterface $item) use ($fetch, $key, $page) {
            $item->expiresAfter(RedisUtils::DURATION);
            $item->tag(RedisUtils::TAG);
                return $fetch->fetchById($page['id']);
            },
        );
    } catch (\Exception|\Psr\Cache\InvalidArgumentException $e) {
        Mailer::sendError($e->getMessage());
        continue;
    }
    echo $page['name']."\n";
}
