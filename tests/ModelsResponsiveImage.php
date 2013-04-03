<?php

namespace Xiphe\ResponsiveImages\tests;

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Main;
use Xiphe\ResponsiveImages\models\ResponsiveImage;
use Xiphe\ResponsiveImages\controllers\DatabaseJson;

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

	public function stubbedImage()
	{
		return Fixture::validResponsiveImage(array(
			'creator' => array($this->getMock('Stub', array('callback')), 'callback')
		));
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
		$fixture = $this->stubbedImage();

		ob_start();
		echo $fixture;
		$result = ob_get_clean();
		$this->assertTag(array('tag' => 'img'), $result);
	}

	public function testHasMastersConfigByDefault()
	{
		$this->assertEquals('http://example.org', Fixture::validResponsiveImage()->get('_mediaUrl'));
	}

	public function testCanNotOverwriteProtectedMastersConfig()
	{
		try {
			 Fixture::validResponsiveImage(array(
				'_mediaUrl' => 'http://example.org/foo/'
			));
		} catch(\Xiphe\ResponsiveImages\models\Exception $e) {
			return;
		}

		$this->fail('Protected configuration keys not protected properly.');

	}

	public function testCanOverwriteMastersConfig()
	{
		$fixture = Fixture::validResponsiveImage(array(
			'classPrefix' => 'foo_'
		));

		$this->assertEquals('foo_', $fixture->get('classPrefix'));
		$this->assertEquals('xri_', Fixture::validMaster()->get('classPrefix'));
	}

	public function testStringIsSetAsSrcOnImageCreation()
	{
		$this->assertRegExp(
			sprintf('/data%sforest\.jpg/', preg_quote(DIRECTORY_SEPARATOR)),
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

        $this->assertRegExp(
        	sprintf('/data%sforest\.jpg/', preg_quote(DIRECTORY_SEPARATOR)),
        	$master->getImage(5)->get('src')
        );
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
		$this->assertEquals(3318, Fixture::validResponsiveImage()->get('maxWidth'));
	}

	public function testHeightIsFetchedFromImage()
	{
		$this->assertEquals(2221, Fixture::validResponsiveImage()->get('maxHeight'));
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

		$DS = DIRECTORY_SEPARATOR;
		$fakeFolder = str_replace('/', $DS, '/foo/bar/test/somewhere/else/');

		$master = Fixture::validMaster(array(
			'_hashesStartLength' => 32,
		));
		$master->initGlobalSettings(true);

		$fixture = $master->getImage(Fixture::validImageData());
		$fixture->setCacheFolderTo($fakeFolder);

		$assertion = md5(json_encode(dirname($fakeFolder).$DS));
		$assertion .= $DS.'else'.$DS;

		$this->assertEquals(Fixture::cache($assertion), $fixture->get('cacheFolder'));
	}

	public function testCacheFolderIsBoundToSource()
	{
		$fixture = Fixture::validResponsiveImage();

		$cacheDir = $fixture->get('cacheFolderName');

		$folder = $fixture->get('master')->getDB()->getCacheFolderByHash(dirname($cacheDir));

		$this->assertEquals(
			Fixture::data(),
			$folder.basename($cacheDir).DIRECTORY_SEPARATOR
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

	// public function testToStringHasClass()
	// {
	// 	ob_start();
	// 	echo Fixture::stubbedResponsiveImage();
	// 	$result = ob_get_clean();

	// 	$matcher = array(
	// 	  'attributes' => array('class' => 'xri_image')
	// 	);
	// 	$this->assertTag($matcher, $result, 'xri_image class not set on responsive image');
	// }

	// public function testToStringHasMaxwidthAttr()
	// {
	// 	$fixture = Fixture::stubbedResponsiveImage();

	// 	$matcher = array(
	// 	  'attributes' => array('data-maxwidth' => $fixture->get('sourceWidth'))
	// 	);

	// 	$this->assertTag($matcher, $fixture->__toString(), 'data-maxwidth not set on responsive image');
	// }

	// public function xtestToStringHasBreakpointsAttr()
	// {
	// 	$fixture = Fixture::stubbedResponsiveImage();

	// 	$matcher = array(
	// 	  'attributes' => array('data-breakpoints' => json_encode($fixture->get('breakPoints')))
	// 	);
	// 	// var_dump($fixture->__toString());
	// 	$this->assertTag($matcher, $fixture->__toString(), 'data-breakpoints not set on responsive image');
	// }
}