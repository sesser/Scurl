<?php

namespace Scurl;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-02-02 at 21:02:22.
 */
class UtilsTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var Utils
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
		
	}

	/**
	 * @covers Scurl\Utils::array_merge_recursive
	 */
	public function testArray_merge_recursive()
	{
		$array1 = [
			'level1' => [
				'level2' => [
					'level3' => 'test'
				],
				'test' => 'two'
			],
			'test' => 'one'
		];
		$merged = Utils::array_merge_recursive($array1, [
			'level1' => [
				'level2' => [],
				'test' => 'three'
			]
		]);
		$this->assertTrue(is_array($merged));
		$this->assertEquals('three', $merged['level1']['test']);
	}

}