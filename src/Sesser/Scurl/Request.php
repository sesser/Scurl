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

/**
 * Request
 * 
 * The Scurl request object. Call Request::send() to send the request and return
 * the {@link Response Response} object
 *
 * @author Randy Sesser <sesser@gmail.com>
 */
class Request
{
	/** Request method GET */
	const METHOD_GET = 'GET';
	
	/** Request method POST */
	const METHOD_POST = 'POST';
	
	/** Request method PUT */
	const METHOD_PUT = 'PUT';

	/** Request method DELETE */
	const METHOD_DELETE = 'DELETE';

	/** Request method HEAD */
	const METHOD_HEAD = 'HEAD';

	/** @var string The request method */
	public $method = Request::METHOD_GET;
	
	/** @var string The URL for this request */
	public $url = '';
	
	/** @var array The request parameters */
	public $parameters = [];
	
	/** @var mixed Generally used for PUT requests */
	public $data = NULL;
	
	/** @var array The headers sent to the server */
	public $headers = [];
	
	/** @var array cURL options to set */
	public $options = [];
	
	/** @var array Authentication parameters [ 'user' => (string), 'pass' => (string)] */
	protected $auth = [];
	
	/** @var array Configuration for this request */
	protected $config = [];
	
	/** @var array Curl options container */
	private $curlopts = [];
	
	/**
	 * Constructs a new Request object
	 * @param array $config Configuration for this request
	 */
	public function __construct($url = '', array $config = [])
	{
		$this->url = $url;
		$this->config = $config;
		
		$this->initialize([
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
				'user-agent' => 'Scurl/1.0.3; PHP/' . PHP_VERSION . ' (+http://github.com/sesser/scurl)',
				'timeout' => 10,
				'connect_timeout' => 2,
				'follow_location' => TRUE,
				'max_redirects' => 3
			],
		]);
	}
	
	/**
	 * Sends the request to the server
	 * @return \Scurl\Response
	 * @throws Exceptions\RequestException
	 */
	public function send()
	{
		if (empty($this->url))
			throw new Exceptions\RequestException('No URL has been set for request');
		
		$ch = curl_init();
		$this->setCurlOption($ch, $this->curlopts);
		
		$query = '';
		foreach ($this->parameters as $k => $v)
			$query .= (empty($query) ? '?' : '&') . sprintf('%s=%s', $k, urlencode($v));
		
		if (preg_match('/[\?&]/', $this->url))
			$query = substr($query, 1);
		
		$fp = NULL;
		
		switch ($this->method)
		{
			default:
			case Request::METHOD_GET:
				$this->setCurlOption($ch, CURLOPT_URL, $this->url . $query);
				break;
			case Request::METHOD_POST:
				$this->setCurlOption($ch, [
					CURLOPT_POST => TRUE,
					CURLOPT_POSTFIELDS => $this->parameters,
					CURLOPT_URL => $this->url
				]);
				break;
			case Request::METHOD_HEAD:
				$this->setCurlOption($ch, [
					CURLOPT_NOBODY => TRUE,
					CURLOPT_URL => $this->url . $query
				]);
				break;
			case Request::METHOD_PUT:
				$this->setCurlOption($ch, CURLOPT_URL, $this->url . $query);				
				if (is_file($this->config['data'])) {
					if (FALSE !== ($fp = fopen($this->config['data'], 'r'))) {
						$this->setCurlOption($ch, CURLOPT_PUT, TRUE);
						$this->setCurlOption($ch, CURLOPT_INFILE, $fp);
						$this->setCurlOption($ch, CURLOPT_INFILESIZE, filesize($this->config['data']));
					}
				} else {
					$this->setCurlOption($ch, CURLOPT_CUSTOMREQUEST, Request::METHOD_PUT);
					$this->setCurlOption($ch, CURLOPT_POSTFIELDS, $this->config['data']);
				}
				break;
			case Request::METHOD_DELETE:
				$this->setCurlOption($ch, [
					CURLOPT_CUSTOMREQUEST => $this->method,
					CURLOPT_URL => $this->url . $query
				]);
				break;
		}
		
		$res = curl_exec($ch);
		
		$errNo = curl_errno($ch);
		if ($errNo !== 0)
			throw new Exceptions\RequestException(curl_error($ch), $errNo);
		
		
		$response = new Response($res);
		$response->info = curl_getinfo($ch);
		$response->request_url = $response->info['url'];
		$response->request_parameters = $this->parameters;
		curl_close($ch);
		
		return $response;
	}
	
	/**
	 * Sets a curl option or options if second parameter is an array
	 * @param resource $ch The cURL resource (from curl_init())
	 * @param mixed $option A CURLOPT_* option or array of CURLOPT_* => value options
	 * @param mixed $value The value to set.
	 * @throws Exceptions\RequestException
	 */
	private function setCurlOption($ch, $option, $value = NULL)
	{
		if (!is_resource($ch))
			throw new Exceptions\RequestException('Parameter #0 is not a valid curl resource');
		if (is_array($option)) {
			if (function_exists('curl_setopt_array')) {
				curl_setopt_array($ch, $option);
			} else {
				foreach ($option as $k => $v)
					$this->setCurlOption($ch, $k, $v);
			}
		} else {
			curl_setopt($ch, $option, $value);
		}
	}
	
	/**
	 * Initializes the request
	 * @param array $defaults
	 */
	private function initialize(array $defaults)
	{
		$this->config = Utils::array_merge_recursive($defaults, $this->config);
		$this->method = isset($this->config['method']) ? $this->config['method'] : $defaults['method'];
		$this->parameters = isset($this->config['parameters']) ? $this->config['parameters'] : [];
		$this->options = isset($this->config['options']) ? $this->config['options'] : [];
		$this->headers = isset($this->config['headers']) ? $this->config['headers'] : [];
		$this->data = isset($this->config['data']) ? $this->config['data'] : NULL;
		if (isset($this->config['auth']))
			$this->auth = $this->config['auth'] + $defaults['auth'];
		
		$headers = [];
		foreach ($this->headers as $k => $v)
			$headers[] = sprintf('%s: %s', $k, $v);
		
		$this->curlopts = [
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_USERAGENT => $this->options['user-agent'],
			CURLOPT_HEADER => TRUE,
			CURLINFO_HEADER_OUT => TRUE,
			CURLOPT_CONNECTTIMEOUT => $this->options['connect_timeout'],
			CURLOPT_TIMEOUT => $this->options['timeout'],
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FOLLOWLOCATION => $this->options['follow_location'],
			CURLOPT_MAXREDIRS => $this->options['max_redirects'],
			CURLOPT_SSL_VERIFYPEER => TRUE,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_CAINFO => __DIR__ . '/cacert.pem',
			CURLOPT_ENCODING => ''
		];
		
		$cookie = '';
		if (isset($this->config['cookie'])) {
			if (is_array($this->config['cookie'])) {
				$ck = [];
				foreach ($this->config['cookie'] as $k => $v)
					$ck[] = sprintf('%s=%s', $k, $v);
				$cookie = implode('; ', $ck);
			}
		}
		$this->curlopts[CURLOPT_COOKIE] = $cookie;
		
		if (isset($this->auth['user']) && !empty($this->auth['user'])) {
			$this->curlopts[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
			$this->curlopts[CURLOPT_USERPWD] = sprintf('%s:%s', $this->auth['user'], $this->auth['pass']);
		}
	}

}

