## Scurl ##
Scurl is an easy to use PHP library for making HTTP requests. It requires 
PHP >= 5.4 and the curl extension. The library is [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compliant and 
includes a [composer](http://getcomposer.org) file for easy inclusion in your project.

## How To Include ##

**With Composer**

Add the following to your `composer.json` file in your project:

``` json
{
	"require": {
		"sesser/scurl": "1.*"
	}
}
```

Then make sure you include your `vendor/autoload.php` file.

**Without Composer**

``` php
<?php
	include_once 'src/Sesser/Scurl/Scurl.php';
```

## Quick How To ##

Scurl is pretty basic. It supports the major calls (GET, POST, PUT, DELETE, HEAD).
At it's most basic level, you can make a GET request like so

``` php
<?php
	$scurl = new Scurl\Scurl;
	$response = $scurl->get('http://www.google.com');
	echo $response->body;
```

For more complex calls like PUTting objects to servers:
``` php
<?php
	$scurl = new Scurl\Scurl;
	$response = $scurl->put('http://api.awesomeapi.net/v1/upload/file.png', [], [
	  'data' => '/full/path/to/file.png'
	]);
```

PUTting `json` data (and presumably `xml` data, though untested) is possible too:
``` php
<?php
	$scurl = new Scurl\Scurl;
	$response = $scurl->put('http://api.awesomeapi.net/v1/update', [ 'param' => 'value'], [
		'data' => '{"data": { "foo": "bar" }}',
		'headers' => ['Content-type' => 'application/json']
	]);
```

## The Long Story ##

`Scurl` is just a wrapper to the `Request` class which does most of the heavy lifting.
When you instantiate a `Scurl` object, you can pass an array of configuration
options. These options persist for all calls made with that object *unless* you 
override them in a specific call. The configuration passed to the `__construct`
is merged with the defaults shown below:

``` php
<?php
$defaults = [
	'method' => Request::METHOD_GET,
	'auth' => [
		'user' => '',
		'pass' => ''
	],
	'data' => '',
	'parameters' => [],
	'cookie' => [],
	'headers' => [
		'Connection'	 =>  'keep-alive',
		'Keep-Alive'	 => 300,
		'Accept-Charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
		'Accept-Language' => 'en-us,en;q=0.5'			
	],
	'options' => [
		'user-agent' => 'Scurl/1.0; PHP/' . PHP_VERSION . ' (+http://github.com/sesser/scurl)',
		'timeout' => 10,
		'connect_timeout' => 2,
		'follow_location' => TRUE,
		'max_redirects' => 3
	],
];
```

### Configuration Explained ###

`$defaults['method']`: The HTTP Method. This is generally overridden with every request, but you can set a default if you want.

`$defaults['auth']`: You can add your authentication credentials here or on a per-request basis by simply adding it to the `$url` (a la `http://<user>:<pass>@somehost.com`)

`$defaults['data']`: This is used for PUT requests

`$defaults['parameters']`: Default parameters to pass in the request

`$defaults['cookie']`: Set this to pass a cookie in the request. This is either a key/value pair array or a string value ('foo=bar; uid=1234')

`$defaults['headers']`: Headers sent in the request.

`$defaults['options']`: General options
