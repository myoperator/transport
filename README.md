# MyOperator Transport

This is a package that uses guzzle http transport and can be used to make network requests.
This internally uses [Guzzle](https://github.com/guzzle/guzzle) library to make requests.

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

## Using Responses

Responses are the main part of making any responses. Since responses can be of any type (i.e. json, xml, text etc)
this library takes cares of automatically converting any json encodeable responses.

Responses are part of `\MyOperator\Transport\Response`. Hence any response have three available methods:

- `json()` which returns array if response is valid json. Else the response is returned as is
- `text()` returns the text response as is
- `getStatus()` returns the response HTTP Status code

```php

//Assuming webservice `/getjson` returns {'a': 'b'}
// and '/gettext' returns 'Simple text response'

$response = $transport->get('/getjson');
// assertTrue $response->json() == ['a' => 'b']
// assertTrue $response->text() == '{"a": "b"}'

$response = $transport->get('/gettext);
// assertTrue $response->json() == 'Simple text response'
// assertTrue $response->text() == 'Simple text response'
// assertTrue (string) $response == 'Simple text response'
```

## Setting headers

Sometime, you may wish to add headers, which can be easily done using `setHeaders` method.

```php
use MyOperator\Transport;

$transport = new Transport('http://localhost');

$transport->setHeaders(['X-Auth-key' => 'xyz']);

$transport->post('/login');
```

### Mocking network requests

This library aims at making writing unit tests and mocks a breeze. This library provides a fluent [Guzzle mock](http://docs.guzzlephp.org/en/stable/testing.html) api to make mocking easy.

To mock a network request, you can easily create a mock using `MyOperator\TransportMock`. Then can you queue some responses to it.
You can then call your apis and the mock will replay the queues responses in order.

For instance, to mock a `200 SUCCESS` response from any api, you can do:

```php
use \MyOperator\TransportMock;

// Inititalising a mocker
$transport = new TransportMock();

//Creating custom response. order is createResponse($body, $headers= [], $status_code=200);
$mockResponse = $transport->createResponse(json_encode(['a' => 'b']));

// Creating another GuzzleHttp\Psr7\Response responses
$anotherResponse = new GuzzleHttp\Psr7\Response(201, [], json_encode(['c' => 'd']));

// Queue a valid GuzzleHttp\Psr7\Response
$transport->queue($mockResponse);
$transport->queue($anotherResponse);

// Finally we can start mock
$transport->mock();

// This will return first queued response, doesn't matter whatever the request is
$response = $transport->get('/get');
//assertTrue $response->json() == ['a' => 'b']
//assertTrue $response->getStatus() == 200

$response = $transport->post('/somethingelse');
//assertTrue $response->json() == ['c' => 'd']
//assertTrue $response->getStatus() == 201

// Since at this point we have exhaused our queued response,
// this will throw an \OutOfBoundsException
$response = $transport->get('/status');
```


## TODO

- Handling public and private APIs
- Setting basic authentication process
