## Scurl ##

[![Build Status](https://travis-ci.org/sesser/Scurl.png?branch=master)](https://travis-ci.org/sesser/Scurl)

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
?>
```

##Quick How To##

Scurl is pretty basic. It supports the major calls (GET, POST, PUT, DELETE, HEAD).
At it's most basic level, you can make a GET request like so

``` php
<?php
	$scurl = new Sesser\Scurl\Scurl;
	$response = $scurl->get('http://www.google.com');
	echo $response->body;
?>
```

For more complex calls like PUTting objects to servers:
``` php
<?php
	$scurl = new Sesser\Scurl\Scurl;
	$response = $scurl->put('http://api.awesomeapi.net/v1/upload/file.png', [], [
	  'data' => '/full/path/to/file.png'
	]);
?>
```

PUTting `json` data (and presumably `xml` data, though untested) is possible too:
``` php
<?php
	$scurl = new Sesser\Scurl\Scurl;
	$response = $scurl->put('http://api.awesomeapi.net/v1/update', [ 'param' => 'value'], [
		'data' => '{"data": { "foo": "bar" }}',
		'headers' => ['Content-type' => 'application/json']
	]);
?>
```

##Events##

Scurl supports basic events too so you can make modifications to the `Request` object before it
sends the request off. Or, you can modify/read the `Response` after the request has been sent. This
could be useful for logging requests or keeping track of the time it takes to get a response from a
server.

Currently, there's only two events called; `Scurl::EVENT_BEFORE` and `Scurl::EVENT_AFTER`. They are
called before the request is sent and after the response is received, respectively. The events are a
in a stack and called from top to bottom (first in, first called) so you can assign more than one callback
to an event. See the example below:

```php
<?php
	$scurl = Sesser\Scurl\Scurl::getInstance();
	$after_hash = $scurl->addListener(Sesser\Scurl\Scurl::EVENT_AFTER, function(Sesser\Scurl\Request $request, Sesser\Scurl\Response $response) {
		//-- Do some magic here... inspect the request headers, log the url and time it took, etc
	});
?>
```

The `Scurl::addListener` method returns the pointer for this event callback. If, for some reason, you
want to remove the listener from the call stack, just call the `Scurl::removeListener` method.

```php
<?php
	$scurl = Sesser\Scurl\Scurl::getInstance();
	$scurl->removeListener(Sesser\Scurl\Scurl::EVENT_AFTER, $after_hash);
?>
```

Please note that if the event passed to `addListener` is not a valid event or the callback is not
callable, `addListener` will return `false` indicating that failed to register the event. Also, there
is one other event that hasn't been implemented yet; `Sesser\Scurl\Scurl::EVENT_ERROR`. This event will
be available soon and called when there is an error at the `curl` level. the method signature should look
like this:

```php
<?php function($errNo, $errMessage, Sesser\Scurl\Request $request); ?>
```

##The Long Story##

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
?>
```

### Configuration Explained ###

`$defaults['method']`: The HTTP Method. This is generally overridden with every request, but you can set a default if you want.

`$defaults['auth']`: You can add your authentication credentials here or on a per-request basis by simply adding it to the `$url` (a la `http://<user>:<pass>@somehost.com`)

`$defaults['data']`: This is used for PUT requests

`$defaults['parameters']`: Default parameters to pass in the request

`$defaults['cookie']`: Set this to pass a cookie in the request. This is either a key/value pair array or a string value ('foo=bar; uid=1234')

`$defaults['headers']`: Headers sent in the request.

`$defaults['options']`: General options
