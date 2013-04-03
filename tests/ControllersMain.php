<?php

namespace Xiphe\ResponsiveImages\tests;

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Main;

require_once '../vendor/autoload.php';
require_once 'Fixture.php';

class ControllersMain extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		Fixture::ensureCacheFolder();
	}

	public function tearDown()
	{
		Fixture::cleanCacheFolder();
	}
	
	public function testControllerExists()
	{
		$this->assertInstanceOf('Xiphe\ResponsiveImages\controllers\Main', Fixture::validMaster());
	}

	public function testMainIsSingleton()
	{
		$this->assertNotEquals(Fixture::validMaster(), Fixture::validMaster());
	}

	public function testExceptionThrownWithoutMediaUrl()
	{
		try {
			Main::i();
		} catch(\Xiphe\ResponsiveImages\controllers\Exception $e) {
			return $e;
		}

		$this->fail('Exception not thrown');
	}

	public function testHasGlobalConfiguration()
	{
		$this->assertEquals(2592000, Fixture::validMaster()->getGlobalSettings('cacheLivetime'));
	}

	public function testGlobalConfigurationCanBeChangedWithConstants()
	{
		define('XIPHE_RESPONSIVEIMAGES_CACHELIVETIME', 3600);

		$fixture = Fixture::validMaster();
		$fixture->initGlobalSettings(true);
		
		$this->assertEquals(3600, $fixture->getGlobalSettings('cacheLivetime'));
	}

	public function testInitWithSettingsArray()
	{
		$fixture = Fixture::validMaster(array(
			'cacheLivetime' => 20
		));

		$this->assertEquals(20, $fixture->get('cacheLivetime'));
	}

	public function testInitWithSettingsFile()
	{
		$fixture = Main::i(Fixture::data('exampleConfig.json'));

		$this->assertEquals(50, $fixture->get('_nojsCookieDuration'));
	}

	public function testImageMethodReturnsNewImageInstance()
	{
		$this->assertInstanceOf(
			'Xiphe\ResponsiveImages\models\ResponsiveImage',
			Fixture::validMaster()->getImage(Fixture::validImageData())
		);
	}

	public function testMasterIsSetOnNewImage()
	{
		$master = Fixture::validMaster();
		$this->assertEquals($master, $master->getImage(Fixture::validImageData())->get('master'));
	}
}