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
define('SCURL_VERSION', '1.01');
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
	/** @var array Configuration */
	protected $config = [];
	
	public function __construct(array $config = [])
	{
		$this->config = $config;
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
		return $request->send();		
	}
}

