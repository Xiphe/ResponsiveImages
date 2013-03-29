<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once '../vendor/autoload.php';

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Main;
use Xiphe\ResponsiveImages\models\Image;

class ModelsImage extends PHPUnit_Framework_TestCase {

	/* Helpers */

	public function validImageData($args)
	{
		return array_merge(
			array(
				'src' => dirname(__FILE__).Main::DS.'sunset.jpg'
			),
			$args
		);
	}

	public function validMaster($args = array())
	{
		return Main::i(array(
			'mediaUrl' => 'http://example.org'
		));
	}

	public function validImage($args = array())
	{
		return $this->validMaster()->getImage($this->validImageData($args));
	}

	/* Tests */

	public function testModelExists()
	{
		$this->assertEquals('Xiphe\ResponsiveImages\models\Image', get_class($this->validImage()));
	}

	public function testToString()
	{
		ob_start();
		echo $this->validImage();
		$result = ob_get_clean();
		$this->assertRegExp('/<img src="/', $result);
	}

	public function testHasMastersConfigByDefault()
	{
		$this->assertEquals('http://example.org', $this->validImage()->getConfig('mediaUrl'));
	}

	public function testCanOverwriteMastersConfig()
	{
		$fixture = $this->validImage(array(
			'mediaUrl' => 'http://example.org/foo/'
		));

		$this->assertEquals('http://example.org/foo/', $fixture->getConfig('mediaUrl'));
		$this->assertEquals('http://example.org', $this->validMaster()->getConfig('mediaUrl'));
	}

	public function testStringIsSetAsSrcOnImageCreation()
	{
		$this->assertEquals('testImage.jpg', $this->validMaster()->getImage('testImage.jpg')->getConfig('src'));
	}

	public function testIntIsSetAsSrcOnImageCreation()
	{
		$this->assertEquals(3, $this->validMaster()->getImage(3)->getConfig('src'));
	}

	public function testThrowsExceptionIfInitiatedWithoutSrcValue()
	{
		try {
			$this->validMaster()->getImage();
		} catch(\Xiphe\ResponsiveImages\models\Exception $e) {
			return;
		}

		$this->fail('No Exception thrown when image initiated without src value');
	}

	public function testExecutesFileFinderHelperOnMaster()
	{
		$master = $this->validMaster();

		$observer = $this->getMock('Observer', array('callback'));
		$observer->expects($this->once())
                 ->method('callback')
                 ->with($this->equalTo('anImageFile'));
		$master->addCallback('fileFinder', array($observer, 'callback'));

		$master->getImage('anImageFile');
	}

	public function testSrcValueCanBeModifyedByFileFinder()
	{
		$master = $this->validMaster();

		$stub = $this->getMock('ASDF', array('callback'));
		$stub->expects($this->any())
             ->method('callback')
             ->will($this->returnValue('foo.png'));

        $master->addCallback('fileFinder', array($stub, 'callback'));

        $this->assertEquals('foo.png', $master->getImage(5)->getConfig('src'));
	}

}