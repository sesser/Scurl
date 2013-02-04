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
 * Response
 *
 * @author Randy Sesser <sesser@gmail.com>
 */
class Response
{
	/** @var string The URL that made this request */
	public $request_url = '';
	
	/** @var array The parameters passed in the request */
	public $request_parameters = [];
	
	/** @var array The response headers */
	public $headers = [];
	
	/** @var array Information about the request as reported by curl_getinfo */
	public $info = [];
	
	/** @var array If an error occurs, it's stored here: [ 'error_no' => int, 'error_message' => string ] */
	public $error = [];
	
	/** @var string The response body */
	public $body = '';
	
	/** @var string The raw response headers and all */
	public $raw = '';
	
	/** @var int The HTTP status code for this response */
	public $code = 0;
	
	/** @var string The HTTP status message */
	public $status = '';
	
	/**
	 * Creates a new Response object based on the raw response from the HTTP request
	 * 
	 * @param string $raw_response The raw response from the HTTP request
	 */
	public function __construct($response)
	{
		$this->raw = $response;
		$head = '';
		$body = '';
		
		list($head, $body) = explode("\r\n\r\n", $response, 2);
		
		if (false !== ($pos = stripos($head, '100 continue'))) {
			list($head, $body) = explode("\r\n\r\n", $body, 2);
		}
		
		$this->body = $body;
		
		$headers = explode("\r\n", $head);
		
		foreach ($headers as $header) {
			if (preg_match('/^(?<protocol>HTTPS?)\/(?<version>\d\.\d)\s(?<code>[\d]{3})\s(?<status>.+)/', $header, $m)) {
				$this->code = $m['code'];
				$this->status = $m['status'];
				continue;
			}
			$parts = explode(':', $header);
			$this->headers[$parts[0]] = trim($parts[1]);
		}
		
	}
}

