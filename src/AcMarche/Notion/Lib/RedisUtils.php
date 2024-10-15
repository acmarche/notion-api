<?php

namespace AcMarche\Notion\Lib;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Cache\CacheInterface;

class RedisUtils
{
    public ?CacheInterface $cache;
    final public const DURATION = 3600;//1heure
    final public const DURATION_LONG = 3600 * 10;//10heures
    final public const TAG = ['ESQUARE'];

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
        $this->cache = null;
    }

    public function instance(): CacheInterface|RedisTagAwareAdapter
    {
        if (!$this->cache) {
            $client = RedisAdapter::createConnection('redis://localhost');
            $this->cache = new RedisTagAwareAdapter($client);
        }

        return $this->cache;
    }

    public static function getInstance(): self
    {
        return new self();
    }

    public static function generateKey(string $cacheKey, int|null $refresh): string
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            return $cacheKey.'-'.time();
        }

        if ($refresh != null || $refresh > 0) {
            try {
                self::getInstance()->cache->delete($cacheKey);
            } catch (InvalidArgumentException $error) {
                Mailer::sendError($error->getMessage());
            }
        }

        return $cacheKey;
    }
}
