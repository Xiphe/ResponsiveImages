<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once '../vendor/autoload.php';

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Main;

class ControllersMain extends PHPUnit_Framework_TestCase {

	public $_validConfig = array(
		'mediaUrl' => 'http://example.org'
	);

	public $_validImage;

	public function setup()
	{
		$this->_validImage = array(
			'src' => dirname(__FILE__).Main::DS.'sunset.jpg'
		);
		$this->fixture = Main::i($this->_validConfig);
	}

	public function testControllerExists()
	{
		$this->assertInstanceOf('Xiphe\ResponsiveImages\controllers\Main', $this->fixture);
	}

	public function testMainIsSingleton()
	{
		$this->assertNotEquals($this->fixture, Main::i($this->_validConfig));
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

	public function testInitWithSettingsArray()
	{
		$fixture = Main::i(array_merge($this->_validConfig, array(
			'cacheLivetime' => 20
		)));

		$this->assertEquals(20, $fixture->getConfig('cacheLivetime'));
	}

	public function testInitWithSettingsFile()
	{
		$fixture = Main::i(dirname(__FILE__).Main::DS.'exampleConfig.json');

		$this->assertEquals(50, $fixture->getConfig('nojsCookieDuration'));
	}

	public function testImageMethodReturnsNewImageInstance()
	{
		$this->assertInstanceOf('Xiphe\ResponsiveImages\models\Image', $this->fixture->getImage($this->_validImage));
	}

	public function testMasterIsSetOnNewImage()
	{
		$this->assertEquals($this->fixture, $this->fixture->getImage($this->_validImage)->getConfig('master'));
	}
}