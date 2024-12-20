<?php

namespace AcMarche\Notion\Lib;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class RedisUtils
{
    public ?CacheInterface $cache;
    final public const DURATION = 3600;//1heure
    final public const DURATION_LONG = 3600 * 10;//10heures
    final public const TAG = ['ESQUARE'];

    public function __construct()
    {
        $this->cache = null;
    }

    /**
     * @throws \Exception
     */
    public function instance(): CacheInterface|RedisTagAwareAdapter
    {
        try {
            if (!$this->cache) {
                $client = RedisAdapter::createConnection('redis://localhost');
                $this->cache = new RedisTagAwareAdapter($client);
            }
        } catch (\Exception $e) {
            throw new \Exception('Connection refusé au serveur de cache');
        }

        return $this->cache;
    }

    public static function getInstance(): self
    {
        return new self();
    }

    public static function generateKey(string $cacheKey): string
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            return $cacheKey.'-'.time();
        }

        return $cacheKey;
    }

    public function delete(string $cacheKey): void
    {
        try {
            $this->cache->delete($cacheKey);
        } catch (InvalidArgumentException|\Exception $error) {
            Mailer::sendError($error->getMessage());
        }
    }
}
