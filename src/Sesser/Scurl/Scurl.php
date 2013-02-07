<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2013 Randy Sesser <sesser@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the “Software”), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * @author Randy Sesser <sesser@gmail.com>
 * @license http://sesser.mit-license.org MIT License
 * @copyright (c) 2013 Randy Sesser <sesser@gmail.com>
 * @package Scurl
 * @filesource
 */

namespace Sesser\Scurl;
require_once 'Utils.php';
require_once 'Exceptions/RequestException.php';
require_once 'Request.php';
require_once 'Response.php';

/**
 * Scurl
 * 
 * A Simple curl wrapper for making HTTP requests. Supported methods are GET, POST
 * PUT, DELETE and HEAD. With some modifications, it could easily send other
 * custom requests (i.e. OPTIONS). Example:
 * 
 * <code>
 *	$scurl = new Scurl\Scurl(
 *		['options' => [ 'user-agent' => 'Scurl/1.0; PHP+cURL' ],
 *		['headers' => [ 'X-Powered-By' => 'My awesome app v0.1a Super Alpha Build' ]
 *	]);
 *	$response = $scurl->get('http://api.someawesomeservice.com/v1/endpoint', 'param1=value1&foo=bar');
 *	// or the parameters can be an associative array of param => value pairs
 *	$response = $scurl->get('http://api.someawesomeservice.com/v1/endpoint', ['param1' => 'value1', 'foo' => 'bar']);
 *	// or you can pass options to 
 *	$response = $scurl->get('http://api.someawesomeservice.com/v1/endpoint?param1=value1&foo=bar', '', [
 *		'options' => ['user-agent' => 'Private UA']
 *	]);
 *	$data = json_decode($response->body, TRUE);
 *
 * </code>
 * 
 * Supported calls: get, post, put, delete, head
 * 
 * @see Scurl::get
 * @see Scurl::post
 * @see Scurl::put
 * @see Scurl::delete
 * @see Scurl::head
 *
 * @author Randy Sesser <sesser@gmail.com>
 */
class Scurl
{

	/** Before send event */
	const EVENT_BEFORE = 'beforeSend';

	/** After send event */
	const EVENT_AFTER = 'afterSend';

	/** Error event */
	const EVENT_ERROR = 'error';

	/** @var array Configuration */
	protected $config = [];

	/** @var Scurl The instance of this Scurl */
	private static $instance = NULL;

	/** @var array Events */
	private static $events = [
		Scurl::EVENT_BEFORE => [],
		Scurl::EVENT_AFTER => [],
		Scurl::EVENT_ERROR => []
	];
	
	/**
	 * Constructs a Scurl object and set the static $instance
	 * @param array $config Configuration for this Scurl object
	 */
	public function __construct(array $config = [])
	{
		$this->config = $config;
		static::$instance =& $this;
	}

	/**
	 * Get the current instance of a Scurl object
	 * @return Scurl
	 */
	public static function getInstance()
	{
		if (static::$instance === NULL)
			return new self;
		return static::$instance;
	}

	/**
	 * Add a listener to call for an event
	 * @param string $event The event
	 * @param callable $callback A callback. Must be callable (is_callable)
	 * @return mixed Returns the hash pointer for the event callback or false if the event doesn't exist or callback is not callable
	 */
	public function addListener($event, $callback)
	{
		if (!array_key_exists($event, static::$events) || !is_callable($callback))
			return false;

		$hash = '';
		if ($callback instanceof \Closure) {
			$hash = sha1(sprintf('%s_%s', $event, count(static::$events[$event])));
		} else {
			$hash = sha1(serialize($callback));
		}
		static::$events[$event][$hash] = $callback;
		return $hash;
	}

	/**
	 * Remove a listener from an event
	 * @param string $event The event
	 * @param string $hash The hash that points to the event callback
	 * @return boolean
	 */
	public function removeListener($event, $hash)
	{
		if (isset(static::$events[$event][$hash])) {
			unset(static::$events[$event][$hash]);
			return true;
		}
		return false;
	}
	
	/**
	 * Clears all callbacks for a given event or all events if not specified
	 * @param string $event Clear only this event
	 */
	public function removeListeners($event = NULL)
	{
		if (!empty($event) && array_key_exists($event, static::$events)) {
			static::$events[$event] = [];
		} else {
			foreach (static::$events as $evt => $events)
				static::$events[$evt] = [];
		}
	}
	
	/**
	 * Get callbacks for event or all callbacks for all events
	 * @param string $event The event to retrieve
	 * @return array
	 */
	public function getListeners($event = NULL)
	{
		if (!empty($event) && array_key_exists($event, static::$events))
			return static::$events[$event];
		return static::$events;
	}
	
	/**
	 * Get a specific callback for an event by its key
	 * @param string $event The event
	 * @param string $key The hash to which the callback is associated
	 * @return mixed
	 */
	public function getListener($event, $key)
	{
		if (isset(static::$events[$event]) && isset(static::$events[$event][$key]))
			return static::$events[$event][$key];
		
		return NULL;
	}
	
	/**
	 * Convenience method for GET requests
	 * @param string $url The URL to GET
	 * @param mixed $params An array of key/value pairs or query string
	 * @param array $config Extra config for this request (headers, cookies, etc)
	 * @return Response
	 */
	public function get($url, $params = '', array $config = [])
	{
		return $this->request($url, $params, $config);
	}
	
	/**
	 * Convenience method for POST requests
	 * @param string $url The URL in which to POST
	 * @param mixed $params An array of key/value pairs or query string
	 * @param array $config Extra config for this request (headers, cookies, etc)
	 * @return Response
	 */
	public function post($url, $params = '', array $config = [])
	{
		return $this->request($url, $params, $config, Request::METHOD_POST);
	}
	
	/**
	 * Convenience method for PUT requests
	 * @param string $url The URL in which to PUT a resource
	 * @param mixed $params An array of key/value pairs or query string
	 * @param array $config Extra config for this request (headers, cookies, etc)
	 * @return Response
	 */
	public function put($url, $params = '', array $config = [])
	{
		return $this->request($url, $params, $config, Request::METHOD_PUT);
	}
	
	/**
	 * Convenience method for DELETE requests
	 * @param string $url The URL to DELETE
	 * @param mixed $params An array of key/value pairs or query string
	 * @param array $config Extra config for this request (headers, cookies, etc)
	 * @return Response
	 */
	public function delete($url, $params = '', array $config = [])
	{
		return $this->request($url, $params, $config, Request::METHOD_DELETE);
	}
	
	/**
	 * Convenience method for HEAD requests
	 * @param string $url The URL
	 * @param mixed $params An array of key/value pairs or query string
	 * @param array $config Extra config for this request (headers, cookies, etc)
	 * @return Response
	 */
	public function head($url, $params = '', array $config = [])
	{
		return $this->request($url, $params, $config, Request::METHOD_HEAD);
	}
	
	/**
	 * Creats and sends a new request
	 * @param string $url The URL. Can contain authentication
	 * @param mixed $params An associative array of key/value pairs or query string (foo=bar&baz=beer)
	 * @param array $config Extra configuration for the request (cookies, headers, authentication)
	 * @param string $method The request method (Request::METHOD_GET, Request::METHOD_POST, etc)
	 * @return Response
	 */
	public function request($url, $params, array $config = [], $method = Request::METHOD_GET)
	{
		$config = Utils::array_merge_recursive($this->config, $config);
		$config['method'] = $method;
		$parameters = [];
		if (!is_array($params)) {
			parse_str($params, $parameters);
		} else {
			$parameters = $params;
		}
		$config['parameters'] = $parameters;
		$parsed_url = Utils::array_merge_recursive([
			'scheme' => 'http',
			'host' => '',
			'port' => 80,
			'user' => '',
			'pass' => '',
			'path' => '',
			'query' => '',
			'fragment' => ''
		], parse_url($url));
		
		extract($parsed_url);
		
		if (!empty($user)) 
			$config['auth'] = [ 'user' => $user, 'pass' => $pass ];
		
		$url = Utils::http_build_url($parsed_url);
		
		$request = new Request($url, $config);

		foreach (static::$events[Scurl::EVENT_BEFORE] as $hash => $callable)
			call_user_func_array($callable, [&$request]);
		
		try {
			$response =  $request->send();
			
		} catch (Exceptions\RequestException $re) {
			$response = new Response('');
			$response->code = $re->getCode();
			$response->status = $re->getMessage();
			$response->request_parameters = $config['parameters'];
			$response->request_url = $url;
			foreach (static::$events[Scurl::EVENT_ERROR] as $hash => $callback)
				call_user_func_array($callback, [$re->getCode(), $re->getMessage(), $request]);
		}
		
		foreach (static::$events[Scurl::EVENT_AFTER] as $hash => $callable) 
			call_user_func_array($callable, [&$request, &$response]);
		
		return $response;
	}
	
	/**
	 * Clean up the static variables to prevent pollution
	 */
	public function __destruct()
	{
		if (static::$instance != NULL)
			static::$instance = NULL;
		foreach (static::$events as $event => $events)
			static::$events[$event] = [];
	}
}

