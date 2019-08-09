# MyOperator Transport

This is a package that uses guzzle http transport and can be used to make network requests.
This internally uses `GuzzleHTTP` library to make requests.

## Quick Start

To make a `GET` or `POST` requests:

```php

include_once 'vendor/autoload.php';

use MyOperator\Transport;

$transport = new Transport('http://localhost/api');

// Making a simple GET request
$response = $transport->get('/users?a=b&c=d');

// More better GET request
$response = $transport->get('/users', ['a' => 'b', 'c' => 'd']); 
// Equivalent to curl -XGET -H 'application/json' http://localhost/api/users?a=b&c=d

// To make a POST
$response = $transport->post('/users', ['a' => 'b']); 
// Equivalent to curl -XPOST -d a=b -H 'application/json' http://localhost/api/users

// Response can be directly cast to string
echo (string) $response; // {"a" : "b"}

//Or json if you like
print_r($response->json()); // ['a' => 'b']

// Or plaintext, same like casting
echo $response->text(); //{"a": "b"}
```

## Setting headers

Sometime, you may wish to add headers, which can be easily done using `setHeaders` method.

```php
use MyOperator\Transport;

$transport = new Transport('http://localhost');

$transport->setHeaders(['X-Auth-key' => 'xyz']);

$transport->post('/login');
```

## TODO

- Handling public and private APIs
- Setting basic authentication process
