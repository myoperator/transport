<?php

namespace MyOperator\Transport\OAuth;

interface TokenCacheInterface
{
    /**
     * Get an item from cache
     * 
     * @param string $cacheKey
     * @return mixed
     */
    public function get($key);

    /**
     * Set an item into cache
     * 
     * @param string $cacheKey
     * @param string $value
     * @param int $ttl default=0
     */
    public function set($key, $value, $ttl=0);
}