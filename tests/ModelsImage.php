<?php

namespace Xiphe\ResponsiveImages\tests;

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Main;
use Xiphe\ResponsiveImages\models\Image;
use Xiphe\ResponsiveImages\models\ResponsiveImage;

require_once '../vendor/autoload.php';
require_once 'Fixture.php';	

class ModelsImage extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		Fixture::ensureCacheFolder();
	}

	public function tearDown()
	{
		Fixture::cleanCacheFolder();
	}
	
	public function testBreakpointCreation()
	{
		$fixture = Fixture::validImage(array('breakPoints', array(
			0 => 50,
			300 => 100,
			600 => 200,
			2000 => 500
		)));

		$this->assertEquals(50, $fixture->getBreakpoint(1));
		$this->assertEquals(50, $fixture->getBreakpoint(40));
		$this->assertEquals(100, $fixture->getBreakpoint(70));
		$this->assertEquals(200, $fixture->getBreakpoint(199));
		$this->assertEquals(300, $fixture->getBreakpoint(299));
		$this->assertEquals(400, $fixture->getBreakpoint(301));
		$this->assertEquals(800, $fixture->getBreakpoint(601));
		$this->assertEquals(2000, $fixture->getBreakpoint(1850));
		$this->assertEquals(2500, $fixture->getBreakpoint(2200));
		$this->assertEquals(3000, $fixture->getBreakpoint(2501));
	}

	public function testWidthIsSetOnSubImage()
	{
		$this->assertEquals(200, Fixture::validImage(200)->get('width'));
	}

	public function testHeightIsSetOnSubImage()
	{
		$this->assertEquals(335, Fixture::validImage(500)->get('height'));
	}

	public function testWidthIsMaxedToSourcesWidth()
	{
		$this->assertEquals(3318, Fixture::validImage(5000)->get('width'));
	}

	public function testDoesNotSetQualityForPng()
	{
		$this->assertEquals(null, Fixture::validImage(1, array('src' => 'data/waterfall.png'))->get('quality'));
	}

	public function testHasQualityForJpg()
	{
		$this->assertEquals(75, Fixture::validImage()->get('quality'));
	}

	public function testSetQualityForJpg()
	{
		$this->assertEquals(50, Fixture::validImage(1, array('quality' => 50))->get('quality'));
	}

	public function testUseDefaultIfQualityIsNotAvailable()
	{
		$this->assertEquals(75, Fixture::validImage(1, array('quality' => 44))->get('quality'));
	}

	public function testImageNameGenerationForJpg()
	{
		$this->assertEquals('sunset-50x33q75.jpg', Fixture::validImage()->get('name'));
	}

	public function testImageNameGenerationForPng()
	{
		$this->assertEquals('waterfall-50x54.png', Fixture::validImage(1, array('src' => 'data/waterfall.png'))->get('name'));
	}

	public function testImageCreationForJpg()
	{
		$fixture = Fixture::validImage();

		$fixture->ensureExistence();

		$this->assertFileExists($fixture->get('cacheFolder').$fixture->get('name'));
	}
}