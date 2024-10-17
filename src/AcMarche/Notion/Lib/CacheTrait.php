<?php

namespace AcMarche\Notion\Lib;

trait CacheTrait
{

    protected RedisUtils $cacheUtils;

    public function getCache($key, callable $callback)
    {
        try {

        } catch (\Psr\Cache\InvalidArgumentException|\Exception $error) {
            Mailer::sendError($error->getMessage());
        }
    }

    public function init($key, $value)
    {
        $this->cacheUtils = new RedisUtils();
        try {
            $this->cacheUtils->instance();
        } catch (\Exception $e) {
            Mailer::sendError($e->getMessage());

            return ResponseUtil::sendErrorResponse($e->getMessage());
        }
    }
}