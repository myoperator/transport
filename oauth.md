# MyOperator OAuth Transport

This library takes care if refreshing tokens of your oauth enabled applications. You just need to provide refresh mechanism and this library retries the refresh mechanism the number of times you wish.

Before getting started, first you have to know few things


## Token Provider

Token provider is the place where you can provide the logic to refresh the token. A basic token provider must implement `MyOperator\Transport\Oauth\TokenProviderInterface`

```php

use MyOperator\Transport\Transport;
use MyOperator\Transport\OAuth\TokenProviderInterface;

class MyTokenProvider implements TokenProviderInterface {

    // This is where you have the chance to refresh your token.
    // This will be called whenever the library is unable to find
    // access token or finds expired access tokens
    public function getToken() {
        $client_id = 'abc';
        $client_secret = 'xyz';
        $refresh_token = 'a-long-string-of-refresh-token';

        $transport = new Transport('https://foo.bar/oauth/');
        // Resolves to POST https://foo.bar/oauth/token?refresh_token=a-long-string-of-refresh-token&client_id=abc&client_secret=def&grant_type=refresh_token
        $response = $transport->post("token?refresh_token={$refresh_token}&client_id={$client_id}&client_secret={$client_secret}&grant_type=refresh_token");
        $jsonResponse = $response->json();
        if(isset($jsonResponse['access_token'])) {
            return $jsonResponse['access_token'];
        }
        return null;
    }

    // This function returns the expiry of your access token. Use 1h for default
    public function getExpiry()
    {
        return 3600;
    }
}
```

## Cache Provider

As you know, OAuth depends on access token, which is valid for an hour (or some period of time). Thus, to  keep  using the same token for subsequent requests, you have to implement a cache provider. You can provide a cache provide implementing `MyOperator\Transport\Oauth\TokenCacheInterface`

```php

use MyOperator\Transport\OAuth\TokenCacheInterface;

// A very basic cache provider which caches the token to
// a file. You should use memcache, redis or some useful 
// cache adapter instead
class CacheProvider implements TokenCacheInterface {

    public function get($key) {
        return file_get_contents("{$key}.txt");
    }

    public function set($key, $value, $ttl=0) {
        file_put_contents("{$key}.txt", $value);
    }
}
```

## Implementing OAuth

Now that you have `TokenProvider` and `CacheProvider` in place, you can actually start implementing OAuth.

```php

use MyOperator\Transport\OAuth;

$cacheProvider = new CacheProvider();
$tokenProvider = new TokenProvider();

$oauth = new OAuth('https://foo.bar/api/v1/'); // This is oauth enabled API
$oauth->setTokenProvider($tokenProvider);
$oauth->setTokenCache($cacheProvider, 'foo.bar.auth'); // The cache provider and cache key

// This request will automatically use $tokenProvider->getToken() to refresh token for expired ones
$response = $oauth->post('some-endpoint', [
    'a' => 'b',
    'c' => 'd',
]);
```

## Additional Methods

### withStatusCodes

Use this method to set your custom status code on which the token should refresh. By default, this is 401

```php
$oauth->withStatusCodes(400); // Will retry on status code = 401

$oauth->withStatusCodes(400, false); // Will retry on status code = 400, 401

$oauth->withStatusCodes([400, 500]); // Will retry on status code = 400, 500

$oauth->withStatusCodes([400, 500], true); // Will retry on status code = 400, 401, 500
```

The second param to `withStatusCodes` is to  decide wether or not you want to append your status code to
the  default 401, or replace it entirely.