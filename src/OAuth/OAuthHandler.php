<?php

namespace MyOperator\Transport\OAuth;

use Psr\Http\Message\RequestInterface;
use MyOperator\Transport\OAuth\TokenProviderInterface;
use MyOperator\Transport\OAuth\TokenCacheInterface;

class OAuthHandler
{
    // 1h expiry for OAuth tokens
    const DEFAULT_EXPIRY = 3600;

    /**
     * OAuth2Handler constructor.
     * @param TokenProviderInterface $provider
     * @param TokenCacheInterface $cache
     * @param string $cacheKey
     */
    public function __construct(
        TokenProviderInterface $provider,
        TokenCacheInterface $cache = null,
        $cacheKey = null
    ) {
        $this->provider = $provider;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return RequestInterface
     */
    public function __invoke(RequestInterface $request, array $options = [])
    {
        return $request->withHeader('Authorization', $this->getAuthorizationHeader());
    }

    /**
     * @return string
     */
    public function getAuthorizationHeader($authorizationKey = 'Bearer')
    {
        var_dump("I am called", $this->getBearerToken());
        return "{$authorizationKey} " . $this->getBearerToken();
    }

    public function refreshToken($ttl=300)
    {
        $token = $this->provider->getToken();
        $expiry = ($this->provider->getExpiry() ?: self::DEFAULT_EXPIRY) - $ttl;
        $this->cache->set($this->cacheKey, $token, $expiry);
        return $token;
    }

    /**
     * @return string
     */
    private function getBearerToken()
    {
        $item = null;

        $token = $this->cache->get($this->cacheKey);
        if (!is_null($token)) {
            return $token;
        }

        return $this->refreshToken();
    }
}