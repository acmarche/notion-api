<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\DatabaseGet;
use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\Menu;
use AcMarche\Notion\Lib\PageGet;
use AcMarche\Notion\Lib\RedisUtils;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Contracts\Cache\ItemInterface;

(new Dotenv())->load(__DIR__.'/.env');

$fetchMenu = new Menu();
$cacheUtils = new RedisUtils();
$fetch = new PageGet();

try {
    $cacheUtils->instance();
} catch (\Exception $e) {
    Mailer::sendError($e->getMessage());
    exit();
}

$key = RedisUtils::generateKey('menu');
try {
    $menu = $cacheUtils->cache->get(
        $key,
        function (ItemInterface $item) {
            $item->expiresAfter(RedisUtils::DURATION_LONG);
            $item->tag(RedisUtils::TAG);

            $fetch = new Menu();

            return $fetch->getMenu();
        },
    );
} catch (\Psr\Cache\InvalidArgumentException|\Exception$e) {
    $error = $e->getMessage();
    if (isset($page['id'])) {
        $error .= 'Page id'.$page['id'];
    }
    if (isset($page['title'])) {
        $error .= 'Page title '.$page['title'];
    }
    Mailer::sendError($error);

    return;
}

foreach ($menu as $page) {
    $key = RedisUtils::generateKey('page-'.$page['id']);
    $cacheUtils->delete($key);
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
        $error = $e->getMessage();
        if (isset($page['id'])) {
            $error .= 'Page id'.$page['id'];
        }
        if (isset($page['title'])) {
            $error .= 'Page title '.$page['title'];
        }
        Mailer::sendError($error);
        continue;
    }
}

$databaseId = $_ENV['NOTION_ACTIVITIES_DATABASE_ID'];
$key = RedisUtils::generateKey('database-activities-'.$databaseId);
$fetch = new DatabaseGet();

$cacheUtils->delete($key);
try {
    $events = $cacheUtils->cache->get(
        $key,
        function (ItemInterface $item) use ($fetch, $key, $databaseId) {
            $item->expiresAfter(RedisUtils::DURATION);
            $item->tag(RedisUtils::TAG);

            return $fetch->getEvents($databaseId);
        },
    );
} catch (\Exception|\Psr\Cache\InvalidArgumentException $e) {
    Mailer::sendError($e->getMessage());
    $events = [];
}
if (isset($events['pages'])) {
    foreach ($events['pages'] as $event) {
        $key = RedisUtils::generateKey('database-activities-'.$databaseId);
        $key .= '-'.$event['id'];
        try {
            $cacheUtils->cache->get(
                $key,
                function (ItemInterface $item) use ($databaseId, $event) {
                    $item->expiresAfter(RedisUtils::DURATION);
                    $item->tag(RedisUtils::TAG);
                    $fetch = new DatabaseGet();

                    return $fetch->getEvents($databaseId, $event['id']);
                },
            );
            continue;
        } catch (\Psr\Cache\InvalidArgumentException|\Exception $e) {
            Mailer::sendError($e->getMessage());

            continue;
        }
    }
}
$databaseId = $_ENV['NOTION_COWORKERS_DATABASE_ID'];
$key = RedisUtils::generateKey('database-coworkers-'.$databaseId);
$cacheUtils->delete($key);
try {
    $cacheUtils->cache->get(
        $key,
        function (ItemInterface $item) use ($fetch, $key, $databaseId) {
            $item->expiresAfter(RedisUtils::DURATION);
            $item->tag(RedisUtils::TAG);

            return $fetch->getCoworkers($databaseId);
        },
    );
} catch (\Exception|\Psr\Cache\InvalidArgumentException $e) {
    Mailer::sendError($e->getMessage());
}

$databases = [$_ENV['NOTION_ECUBE_DATABASE_ID'], $_ENV['NOTION_ROOMS_DATABASE_ID']];
foreach ($databases as $id) {
    $key = RedisUtils::generateKey('database-'.$id);
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
    } catch (\Psr\Cache\InvalidArgumentException|\Exception $e) {
        Mailer::sendError($e->getMessage());
    }
}
