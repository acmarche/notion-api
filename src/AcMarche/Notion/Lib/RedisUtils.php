<?php

namespace AcMarche\Notion\Lib;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;

class RedisUtils
{
    private AsciiSlugger $slugger;
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

    public static function generateKey(string $cacheKey): string
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            $cacheKey = $cacheKey.time();
        }

        return $cacheKey;
    }
}
