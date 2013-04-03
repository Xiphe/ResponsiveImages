<?php

namespace Xiphe\ResponsiveImages\tests;

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Database;

require_once '../vendor/autoload.php';
require_once 'Fixture.php';	

class ControllersDatabase extends \PHPUnit_Framework_TestCase {

	public $collidingFolderNameA = '/a/test/folder/somewhere/';
	public $collidingFolderNameB = '/another/4eay/colliding/folder/';

	public function setUp()
	{
		Fixture::ensureCacheFolder();
	}

	public function tearDown()
	{
		Fixture::cleanCacheFolder();
	}

	public function testFixtureHashLengthIsThree()
	{
		$this->assertEquals(3, Fixture::validMaster()->get('_hashesStartLength'));
	}

	public function testCanCreateAConfigHash()
	{
		$this->assertEquals(3, strlen($this->fixture()->getConfigHash(array('sampleConfig'), 3)));
	}

	public function testCanCreateACacheHash()
	{
		$this->assertEquals(3, strlen($this->fixture()->getCacheHash('/foo/bar/', 3)));
	}

	public function testHashesCollide()
	{
		$this->assertEquals(
			substr(md5(json_encode($this->collidingFolderNameA)), 0, 3),
			substr(md5(json_encode($this->collidingFolderNameB)), 0, 3)
		);
	}

	public function testDbDoesNotUseTheSameHashForDifferentValues()
	{
		$this->assertNotEquals(
			$this->fixture()->getCacheHash($this->collidingFolderNameA),
			$this->fixture()->getCacheHash($this->collidingFolderNameB)
		);
	}

}