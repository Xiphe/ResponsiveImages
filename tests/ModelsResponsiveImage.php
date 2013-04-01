<?php

namespace Xiphe\ResponsiveImages\tests;

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Main;
use Xiphe\ResponsiveImages\models\ResponsiveImage;

require_once '../vendor/autoload.php';
require_once 'Fixture.php';	

class ModelsResponsiveImage extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		Fixture::ensureCacheFolder();
	}

	public function tearDown()
	{
		Fixture::cleanCacheFolder();
	}

	/* Tests */

	public function testModelExists()
	{
		$this->assertInstanceOf(
			'Xiphe\ResponsiveImages\models\ResponsiveImage',
			Fixture::validResponsiveImage()
		);
	}

	public function testToString()
	{
		ob_start();
		echo Fixture::validResponsiveImage();
		$result = ob_get_clean();
		$this->assertRegExp('/<img src="/', $result);
	}

	public function testHasMastersConfigByDefault()
	{
		$this->assertEquals('http://example.org', Fixture::validResponsiveImage()->get('mediaUrl'));
	}

	public function testCanOverwriteMastersConfig()
	{
		$fixture = Fixture::validResponsiveImage(array(
			'mediaUrl' => 'http://example.org/foo/'
		));

		$this->assertEquals('http://example.org/foo/', $fixture->get('mediaUrl'));
		$this->assertEquals('http://example.org', Fixture::validMaster()->get('mediaUrl'));
	}

	public function testStringIsSetAsSrcOnImageCreation()
	{
		$this->assertRegExp(
			'/data\/forest\.jpg/',
			Fixture::validMaster()->getImage('data/forest.jpg')->get('src')
		);
	}

	public function testThrowsExceptionIfInitiatedWithoutSrcValue()
	{
		try {
			Fixture::validMaster()->getImage();
		} catch(\Xiphe\ResponsiveImages\models\Exception $e) {
			return;
		}

		$this->fail('No Exception thrown when image initiated without src value');
	}

	public function testThrowsExceptionIfImageFileCouldNotBeFound()
	{
		try {
			Fixture::validMaster()->getImage('foo.png');
		} catch(\Xiphe\ResponsiveImages\models\Exception $e) {
			return;
		}

		$this->fail('No Exception thrown when image initiated with invalid src.');
	}

	public function testExecutesFileFinderHelperOnMaster()
	{
		$master = Fixture::validMaster();

		$observer = $this->getMock('Observer', array('callback'));
		$observer->expects($this->once())
                 ->method('callback')
                 ->with($this->equalTo('anImageFile'))
             	 ->will($this->returnValue('data/forest.jpg'));
		$master->addCallback('fileFinder', array($observer, 'callback'));

		$master->getImage('anImageFile');
	}

	public function testSrcValueCanBeModifyedByFileFinder()
	{
		$master = Fixture::validMaster();

		$stub = $this->getMock('ASDF', array('callback'));
		$stub->expects($this->any())
             ->method('callback')
             ->will($this->returnValue('data/forest.jpg'));

        $master->addCallback('fileFinder', array($stub, 'callback'));

        $this->assertRegExp('/data\/forest\.jpg/', $master->getImage(5)->get('src'));
	}

	public function testClassesCanBeAppended()
	{
		$this->assertContains('foo', Fixture::validResponsiveImage(array('class' => 'bar foo'))->get('classes'));
	}

	public function testRatioIsFetchedFromImage()
	{
		$this->assertEquals(1.4939, Fixture::validResponsiveImage()->get('ratio'));
	}

	public function testWidthIsFetchedFromImage()
	{
		$this->assertEquals(3318, Fixture::validResponsiveImage()->get('sourceWidth'));
	}

	public function testHeightIsFetchedFromImage()
	{
		$this->assertEquals(2221, Fixture::validResponsiveImage()->get('sourceHeight'));
	}

	public function testFetchAbolutePathOfSourceImage()
	{
		$this->assertEquals(
			Fixture::data('forest.jpg'),
			Fixture::validResponsiveImage(array('src' => 'data/forest.jpg'))->get('src')
		);
	}

	public function testFetchImageTypeFromSourceImage()
	{
		$this->assertEquals('jpg', Fixture::validResponsiveImage()->get('extension'));
	}

	public function testReturnImageInstance()
	{
		$this->assertInstanceOf(
			'Xiphe\ResponsiveImages\models\Image',
			Fixture::validResponsiveImage()->getSubImageForSize(1)
		);
	}

	public function testCacheFolder()
	{
		$this->assertNotNull(Fixture::validResponsiveImage()->get('cacheFolder'));
	}

	public function testCacheFolderIsMd5ValueOfSrc()
	{
		$fakeFolder = '/foo/bar/test/somewhere/else/';

		$fixture = Fixture::validResponsiveImage(array(
			'cacheHacheStartLength' => 10
		));
		$fixture->setCacheFolder($fakeFolder);
		
		$DS = DIRECTORY_SEPARATOR;
		$assertion = substr(md5(dirname($fakeFolder)), 0, 10);
		$assertion .= $DS.'else'.$DS;

		$this->assertEquals(Fixture::cache($assertion), $fixture->get('cacheFolder'));
	}

	public function testCacheBindingFileExists()
	{
		$fixture = Fixture::validResponsiveImage();

		$this->assertFileExists(dirname($fixture->get('cacheFolder')).'/.xri_binding');
	}

	public function testCacheFolderIsBoundToSource()
	{
		$fixture = Fixture::validResponsiveImage();

		$this->assertEquals(
			Fixture::data(),
			file_get_contents(dirname($fixture->get('cacheFolder')).'/.xri_binding')
		);
	}

	public function testCacheFolderIsUsedForMultipleImages()
	{
		$fixture = Fixture::validResponsiveImage();
		$fixture2 = Fixture::validResponsiveImage(array(
			'src' => Fixture::data('forest.jpg')
		));

		$this->assertEquals(
			$fixture->get('cacheFolder'),
			$fixture2->get('cacheFolder')
		);
	}

	public function testExtendsHashIfTwoCollide()
	{
		$dir = 'foo/bar/test/somewhere/else/';
		$collidingDir = 'another/colliding/folder/uMeaj/else/';

		$fixture = Fixture::validResponsiveImage(array(
			'cacheHacheStartLength' => 1
		));

		$fixture->setCacheFolder($dir);
		$folder1 = $fixture->get('cacheFolder');

		$fixture->setCacheFolder($collidingDir);
		$folder2 = $fixture->get('cacheFolder');
		
		$this->assertNotEquals($folder1, $folder2);
	}

	public function xtestSmallImageIsBeingGeneratedForNewImageInstance()
	{
		$master = Fixture::validMaster(array('breakPoints', array(0 => 50)));

		$master->getImage(Fixture::validImageData());

		$this->assertFileExists(Fixture::cache('ResponsiveImages/data/sunset-50x33q75.jpg'));
	}




}