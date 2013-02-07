<?php

namespace Sesser\Scurl;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-02-02 at 21:02:22.
 * @ignore
 */
class ScurlTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var Scurl
	 */
	protected $scurl;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->scurl = new Scurl();
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
		$this->scurl = NULL;
	}
	
	/**
	 * @covers Sesser\Scurl\Request::__construct
	 * @covers Sesser\Scurl\Request::initialize
	 * @covers Sesser\Scurl\Scurl::__construct
	 * @covers Sesser\Scurl\Scurl::getInstance
	 */
	public function testInstanceAndInit()
	{
		$this->assertInstanceOf('\Sesser\Scurl\Scurl', $this->scurl);
		$scurl = Scurl::getInstance();
		$this->assertInstanceOf('\Sesser\Scurl\Scurl', $scurl);
		$this->assertEquals(sha1(serialize($this->scurl)), sha1(serialize($scurl)));
	}

	/**
	 * @covers Sesser\Scurl\Scurl::addListener
	 * @covers Sesser\Scurl\Scurl::removeListener
	 */
	public function testEvents()
	{
		$self =& $this;
		$scurl = Scurl::getInstance();
		$before_hash = $scurl->addListener(Scurl::EVENT_BEFORE, function(Request $request) use($self) {
			$self->assertTrue(TRUE);
		});
		$this->assertNotEmpty($before_hash);
		$beforeHash = $scurl->addListener(Scurl::EVENT_BEFORE, [$this, 'eventCallbackTest']);
		$this->assertEquals(sha1(serialize([$this, 'eventCallbackTest'])), $beforeHash);
		$scurl->addListener(Scurl::EVENT_AFTER, function(Request $request, Response $response) use($self) {
			$self->assertTrue(TRUE);
		});
		$response = $this->scurl->get('http://httptest.instaphp.com/test/get?_method=' . __FUNCTION__ . '&test=get');
		$this->assertTrue($scurl->removeListener(Scurl::EVENT_BEFORE, $beforeHash));
		$this->assertFalse($scurl->removeListener(Scurl::EVENT_BEFORE, 'nonexistanthash'));
	}
	
	/**
	 * @covers Sesser\Scurl\Scurl::getListeners
	 * @covers Sesser\Scurl\Scurl::getListener
	 * @covers Sesser\Scurl\Scurl::removeListeners
	 */
	public function testGetListeners()
	{
		$scurl = new Scurl;
		$expected_key = sha1(serialize([$this, 'eventAfterHandler']));
		$key = $scurl->addListener(Scurl::EVENT_AFTER, [$this, 'eventAfterHandler']);
		$this->assertEquals($expected_key, $key);
		$callback = $scurl->getListener(Scurl::EVENT_AFTER, $key);
		$this->assertTrue(is_callable($callback));
		$callbacks = $scurl->getListeners(Scurl::EVENT_AFTER);
		$this->assertNotEmpty($callbacks);
		$this->assertEquals(1, count($callbacks));
		$this->assertNull($scurl->getListener(Scurl::EVENT_AFTER, 'boguskey'));
		$all = $scurl->getListeners();
		$this->assertTrue(array_key_exists(Scurl::EVENT_AFTER, $all));
		$this->assertTrue(array_key_exists(Scurl::EVENT_BEFORE, $all));
		$this->assertTrue(array_key_exists(Scurl::EVENT_ERROR, $all));
		$scurl->addListener(Scurl::EVENT_BEFORE, [$this, 'eventCallbackTest']);
		$scurl->removeListeners(Scurl::EVENT_BEFORE);
		$this->assertEmpty($scurl->getListeners(Scurl::EVENT_BEFORE));
		$scurl->removeListeners();
		$this->assertEmpty($scurl->getListeners(Scurl::EVENT_AFTER));
	}
	
	public function eventAfterHandler(Request $req, Response $res)
	{
		echo __METHOD__;
	}
	
	/**
	 * @covers Sesser\Scurl\Scurl::request
	 */
	public function testErrorHandler()
	{
		$url = 'http://error.instaphp.com/test/get';
		$self =& $this;
		$scurl = Scurl::getInstance();
		$scurl->addListener(Scurl::EVENT_ERROR, function($no, $msg, Request $req) use($self, $url) {
			$self->assertTrue(TRUE);
			$self->assertNotEmpty($msg);
			$self->assertNotEmpty($no);
			$self->assertEquals($url, $req->url);
		});
		$scurl->get($url);
	}
	
	public function eventCallbackTest(Request $request)
	{
		$this->assertTrue(TRUE);
		$this->assertNotEmpty($request->url);
	}

	/**
	 * @covers Sesser\Scurl\Scurl::get
	 * @covers Sesser\Scurl\Scurl::request
	 * @covers Sesser\Scurl\Request::send
	 * @covers Sesser\Scurl\Response::__construct
	 */
	public function testGet()
	{
		$res = $this->scurl->get('http://httptest.instaphp.com/test/get', '_method='.__FUNCTION__.'&test=get');
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertNotEmpty($res->body);
		$json = json_decode($res->body, TRUE);
		$this->assertEquals(__FUNCTION__, $json['request_get_parameters']['_method']);
		$this->assertEquals(Request::METHOD_GET, $json['request_method']);
		
		$res = $this->scurl->get('http://httptest.instaphp.com/test/get', ['_method' => __FUNCTION__, 'test' => 'get']);
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertNotEmpty($res->body);
		$json = json_decode($res->body, TRUE);
		$this->assertEquals(__FUNCTION__, $json['request_get_parameters']['_method']);
		$this->assertEquals(Request::METHOD_GET, $json['request_method']);
	}

	/**
	 * @covers Sesser\Scurl\Scurl::post
	 * @covers Sesser\Scurl\Scurl::request
	 * @covers Sesser\Scurl\Request::send
	 * @covers Sesser\Scurl\Response::__construct
	 */
	public function testPost()
	{
		$res = $this->scurl->post('http://httptest.instaphp.com/test/post', '_method='.__FUNCTION__.'&test=post');
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertNotEmpty($res->body);
		$json = json_decode($res->body, TRUE);
		$this->assertEquals(__FUNCTION__, $json['request_post_parameters']['_method']);
		$this->assertEquals(Request::METHOD_POST, $json['request_method']);
		$res = $this->scurl->post('http://httptest.instaphp.com/test/post', ['_method' => __FUNCTION__, 'test' => 'post']);
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertNotEmpty($res->body);
		$json = json_decode($res->body, TRUE);
		$this->assertEquals(__FUNCTION__, $json['request_post_parameters']['_method']);
		$this->assertEquals(Request::METHOD_POST, $json['request_method']);
		
	}

	/**
	 * @covers Sesser\Scurl\Scurl::put
	 * @covers Sesser\Scurl\Scurl::request
	 * @covers Sesser\Scurl\Request::send
	 * @covers Sesser\Scurl\Response::__construct
	 */
	public function testPut()
	{
		
		$res = $this->scurl->put('http://httptest.instaphp.com/test/put/' . basename(TEST_PUTFILE), '', ['data' => TEST_PUTFILE]);
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertNotEmpty($res->body);
		$res = $this->scurl->put('http://httptest.instaphp.com/test/put', ['_method' => __FUNCTION__], ['data' => '_method=' . __FUNCTION__ . '&test=put']);
		$json = json_decode($res->body, TRUE);
		$this->assertEquals(__FUNCTION__, $json['request_put_parameters']['_method']);
		$this->assertEquals(Request::METHOD_PUT, $json['request_method']);
		
	}

	/**
	 * @covers Sesser\Scurl\Scurl::delete
	 * @covers Sesser\Scurl\Scurl::request
	 * @covers Sesser\Scurl\Request::send
	 * @covers Sesser\Scurl\Response::__construct
	 */
	public function testDelete()
	{
		$res = $this->scurl->delete('http://httptest.instaphp.com/test/delete/' . basename(TEST_PUTFILE), ['_method' => __FUNCTION__, 'test' => 'delete']);
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertNotEmpty($res->body);
		$json = json_decode($res->body, TRUE);
		$this->assertEquals(__FUNCTION__, $json['request_get_parameters']['_method']);
		$this->assertEquals(Request::METHOD_DELETE, $json['request_method']);
		
	}

	/**
	 * @covers Sesser\Scurl\Scurl::head
	 * @covers Sesser\Scurl\Scurl::request
	 * @covers Sesser\Scurl\Request::send
	 * @covers Sesser\Scurl\Response::__construct
	 */
	public function testHead()
	{
		$res = $this->scurl->head('http://httptest.instaphp.com/test/head', ['_method' => __FUNCTION__, 'test' => 'head']);
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertEmpty($res->body);
		$this->assertNotEmpty($res->headers);
		$this->assertEquals(__FUNCTION__, $res->headers['HTTPTEST__method']);
		$this->assertEquals(Request::METHOD_HEAD, $res->headers['HTTPTEST_METHOD']);
	}

	/**
	 * @covers Sesser\Scurl\Scurl::request
	 * @covers Sesser\Scurl\Request::setCurlOption
	 * @covers Sesser\Scurl\Response::__construct
	 */
	public function testRequest()
	{
		$res = $this->scurl->request('http://httptest.instaphp.com/test/get', ['_method' => __FUNCTION__, 'test' => 'get'], ['options' => ['user-agent' => 'ScurlTest/1.0']], Request::METHOD_GET);
		$this->assertInstanceOf('\Sesser\Scurl\Response', $res);
		$this->assertNotEmpty($res->body);
		$json = json_decode($res->body, TRUE);
		$this->assertEquals(__FUNCTION__, $json['request_get_parameters']['_method']);
		$this->assertEquals(Request::METHOD_GET, $json['request_method']);
		
	}

}
