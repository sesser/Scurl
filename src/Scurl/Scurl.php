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

namespace Scurl;

/**
 * Scurl
 *
 * @author Randy Sesser <sesser@gmail.com>
 */
class Scurl
{
	public static function get($url, $params = '', array $config = [])
	{
		$parameters = [];
		if (!is_array($params))
			parse_str ($params, $parameters);
		$config['parameters'] = $parameters;
		$parsed_url = [
			'scheme' => 'http',
			'host' => '',
			'port' => 80,
			'user' => '',
			'pass' => '',
			'path' => '',
			'query' => '',
			'fragment' => ''
		] + parse_url($url);
		extract($parsed_url);
		
		if (!empty($user)) 
			$config['auth'] = [ 'user' => $user, 'pass' => $pass ];
		
		$url = http_build_url($url, $parsed_url, HTTP_URL_STRIP_AUTH);
		
		$request = new Request($url, $config);
		return $request->send();
	}
}

