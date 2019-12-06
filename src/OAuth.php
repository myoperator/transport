<?php

namespace MyOperator\Transport;

use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use MyOperator\Transport\OAuth\OAuthHandler;
use MyOperator\Transport\OAuth\TokenProviderInterface;
use MyOperator\Transport\OAuth\TokenCacheInterface;

class OAuth extends Transport{

    private $retries = 1;
    private static $retry_status_codes = [401];
    private $provider;
    private $cache;

    public function setRetries($retry) {
        if(is_int($retry) && ($retry > 0)) {
            $this->retries = $retry;
            $this->updateClient();
        }
    }

    /**
     * Set token provider which will refresh access token
     *
     * @param TokenProviderInterface $provider
     * @return self
     */
    public function setTokenProvider(TokenProviderInterface $provider)
    {
        $this->provider = $provider;
        $this->updateClient();
        return $this;
    }

    /**
     * Set Cache for auth tokens
     *
     * @param TokenCacheInterface $cache
     * @param string $cacheKey
     * @return self
     */
    public function setTokenCache(TokenCacheInterface $cache, $cacheKey)
    {
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
        $this->updateClient();
        return $this;
    }

    private function updateClient($opts = []) {
        // Make sure we update the OauthHandler
        if($this->provider && $this->cache) {
            $OAuthHandler = new OAuthHandler($this->provider, $this->cache, $this->cacheKey);
            $opts['handler'] = $this->getOauthHandlerStack($OAuthHandler);
        }
        $this->client = $this->createClient($opts);
        return $this;
    }

    /**
     * Set Status code to retry access token regeneration
     * 
     * Set $replace to False if you want to append yours to
     * default 401
     *
     * @param int|array[int] $code
     * @param boolean $replace
     * @return self
     */
    public function withStatusCodes($code, $replace=True)
    {
        $code = is_array($code) ? $code : is_int($code) ? [$code] : null;
        if($code) {
            self::$retry_status_codes = ($replace === true) ? $code : array_merge(
                array_flip(self::$retry_status_codes),
                array_flip($code)
            );
        }
        return $this;
    }

    /**
     * OAuth handler stack
     * 
     * This returns the Guzzle handler with oauth middlewares pumped in
     *
     * @param OAuthHandler $oauthHandler
     * @return \GuzzleHttp\HandlerStack
     */
    private function getOauthHandlerStack(OAuthHandler $oauthHandler) {
        $stack = new HandlerStack();
        $stack->setHandler(\GuzzleHttp\choose_handler());
        $stack->push(
            \GuzzleHttp\Middleware::mapRequest($oauthHandler),
            'oauth_2_0'
        );
        $stack->push(
            self::reauthenticate($oauthHandler, $this->retries),
            'reauthenticate'
        );
        return $stack;
    }

    /* Middleware that reauthenticates on invalid token error
    *
    * @param OAuth2Handler $oauthHandler
    * @param int $maxRetries
    * @return callable Returns a function that accepts the next handler.
    */
    public static function reauthenticate(OAuthHandler $oauthHandler, $maxRetries = 1)
    {
       return function (callable $handler) use ($oauthHandler, $maxRetries) {
           return function (RequestInterface $request, array $options) use ($handler, $oauthHandler, $maxRetries) {
               return $handler($request, $options)->then(
                   function (ResponseInterface $response) use (
                       $request,
                       $handler,
                       $oauthHandler,
                       $options,
                       $maxRetries
                   ) {
                       if (in_array($response->getStatusCode(), self::$retry_status_codes)) {
                           if (!isset($options['reauth'])) {
                               $options['reauth'] = 0;
                           }

                           if ($options['reauth'] < $maxRetries) {
                               $options['reauth']++;
                               $token = $oauthHandler->refreshToken();
                               $request = $request->withHeader(
                                   'Authorization',
                                   $oauthHandler->getAuthorizationHeader()
                               );
                               return $handler($request, $options);
                           }
                       }
                       return $response;
                   }
               );
           };
       };
    }

    /**
     * Set Headers
     *
     * @param string|array $headers
     * @param string $value
     * @return self
     */
    public function setHeaders($headers, $value = null) {
        parent::setHeaders($headers, $value);
        $this->updateClient();
        return $this;
    }

    /**
     * Get Guzzle Client to reuse in your program
     *
     * @param array $opts
     * @return \GuzzleHttp\Client
     */
    public function createClient($opts = []) {
        $opts = array_replace($this->defaultOpts, [
            'base_uri' => $this->baseurl,
            'headers' => $this->getHeaders(),
        ], $opts);
        return new \GuzzleHttp\Client($opts);
    }
}