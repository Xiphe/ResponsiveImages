<?php

namespace Xiphe\ResponsiveImages\tests;

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\Main;

require_once '../vendor/autoload.php';

class Fixture {

	public static function ensureCacheFolder()
	{
		if (!is_dir(self::cache())) {
			mkdir(self::cache(), 777, true);
		}
	}

	public static function cleanCacheFolder()
	{
		foreach (glob(self::cache().'*') as $folder) {
			self::_recursiveDelete($folder);
		}
	}

	private static function _recursiveDelete($dir)
	{
		$files = array_diff(scandir($dir), array('.', '..'));
		foreach ($files as $file) { 
			(is_dir("$dir/$file")) ? self::_recursiveDelete("$dir/$file") : unlink("$dir/$file"); 
		} 
		return rmdir($dir);
	}

	public static function cache($file = '')
	{
		return dirname(__FILE__).Main::DS.'cache'.Main::DS.$file;
	}

	public static function data($file = '')
	{
		return dirname(__FILE__).Main::DS.'data'.Main::DS.$file;
	}

	public static function validImageData($args = array())
	{
		return array_merge(
			array(
				'src' => self::data('sunset.jpg')
			),
			$args
		);
	}

	public static function validMaster($args = array())
	{
		return Main::i(array_merge(
			array(
				'mediaUrl' => 'http://example.org',
				'cacheDir' => self::cache()
			),
			$args
		));
	}

	public static function validResponsiveImage($args = array())
	{
		return self::validMaster()->getImage(self::validImageData($args));
	}

	public static function validImage($size = 1, $args = array())
	{
		return self::validMaster()->getImage(self::validImageData($args))->getSubImageForSize($size);
	}
}