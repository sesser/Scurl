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
 * Utils
 *
 * @author Randy Sesser <sesser@gmail.com>
 */
class Utils
{

	/**
	 * This is a static utility class. No instantiation is supported
	 */
	private function __construct() { }
	
	/**
	 * Recusively merges one or more arrays into a 'base' array.
	 * @param array $array The base array to merge
	 * @return array
	 */
	public static function array_merge_recursive(array $array)
	{
		$args = func_get_args();
		$num = func_num_args();
		if ($num == 1)
			return $array;
		
		$merged = $array;
		
		for ($i = 1; $i < $num; $i++) {
			$merge = $args[$i];
			foreach ($merge as $key => $val) {
				if (is_array($val) && isset($merged[$key]) && is_array($merged[$key])) {
					$merged[$key] = static::array_merge_recursive($merged[$key], $val);
				} else {
					$merged[$key] = $val;
				}
			}
		}
		
		return $merged;
		
	}
	
	/**
	 * Build a URL generated from parse_url() into a string
	 * @param array $url An array URL parts (e.g. from parse_url())
	 * @param bool $strip_auth
	 * @return string
	 */
	public static function http_build_url(array $url, $strip_auth = TRUE)
	{
		$url = static::array_merge_recursive([
			'scheme' => 'http',
			'host' => '',
			'port' => 80,
			'user' => '',
			'pass' => '',
			'path' => '',
			'query' => '',
			'fragment' => ''
		], $url);
		extract($url);
		if (!$strip_auth && !empty($user)) {
			$surl = sprintf('%s://%s@%s', $scheme, (!empty($pass) ? $user.':'.$pass : $user), $host);
		} else {
			$surl = sprintf('%s://%s', $scheme, $host);
		}
		if ($port != 80 && $post != 443)
			$surl .= ':' . $port;
		$surl .= $path;
		$surl .= !empty($query) ? '?' . $query : '';
		$surl .= !empty($fragment) ? '#' . $fragment : '';
		return $surl;
	}

}

