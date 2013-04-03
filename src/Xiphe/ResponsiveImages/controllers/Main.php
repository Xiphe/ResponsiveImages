<?php 

namespace Xiphe\ResponsiveImages\controllers;

use Xiphe as X;
use Xiphe\ResponsiveImages\models\Image;
use Xiphe\ResponsiveImages\models\ResponsiveImage;


/**
 * ResponsiveImages is a PHP Class served by !THE MASTER
 *
 * This class serves verry small images (50px width) on pageload and then loads
 * bigger versions later by using javascript.
 * It can use realpath or Wordpress attachement_IDs as source.
 *
 * @copyright Copyright (c) 2013, Hannes Diercks
 * @author    Hannes Diercks <xiphe@gmx.de>
 * @version   1.0.0
 * @link      https://github.com/Xiphe/ResponsiveImages/
 * @package   ResponsiveImages
 */
class Main extends X\Base {

	private static $_settings = array(
		'databaseController' => 'Xiphe\\ResponsiveImages\\controllers\\DatabaseJson',
		'cacheLivetime' => 2592000,
		'nojsCookieDuration' => 604800,
	);

	private static $_DB;

	protected $_defaultConfiguration = array(
		'_databaseConfig' => null,
		'_mediaUrl' => false,
		'_cacheDir' => 'cache',
		'_baseDir' => '/',
		'_hashesStartLength' => 5,
		'_cacheDirPermissions' => 0755,
		'classPrefix' => 'xri_',
		'sharpen' => true,
		'breakPoints' => array(
		 /* start-point => step-size */
			0 => 50,
			200 => 100,
			600 => 200,
			2000 => 500
		),
		'qualities' => array(
			25,
			50,
			75
		),
		'defaultQuality' => 75
	);

	public function init()
	{
		$this->_defaultConfiguration['_baseDir'] = realpath(dirname(__FILE__).'/../../../../').self::DS;
		$this->addCallback('configurationInitiated', array($this, 'validateConfig'));

		parent::init();

		$this->initGlobalSettings();
	}

	public function initGlobalSettings($force = false)
	{
		if (null === self::$_DB || $force) {
			foreach ($this->getGlobalSettings() as $key => $v) {
				$constName = 'XIPHE_RESPONSIVEIMAGES_'.strtoupper($key);
				if (defined($constName)) {
					self::$_settings[$key] = constant($constName);
				}
			}


			$dbClass = $this->getGlobalSettings('databaseController');
			self::$_DB = $dbClass::i($this->get('_database'));
		}
	}

	public function getDB()
	{
		return self::$_DB;
	}

	public function getGlobalSettings($key = null)
	{
		if (null === $key) {
			return self::$_settings;
		} elseif (isset(self::$_settings[$key])) {
			return self::$_settings[$key];
		}

	}

	public function getFullConfiguration()
	{
		return $this->_configuration;
	}

	public function get($key)
	{
		return $this->getConfig($key);
	}

	public function validateConfig($config)
	{
		if ($config->_mediaUrl === false) {
			throw new Exception('Missing _mediaUrl setting in configuration.', 1);
		}
	}

	public function getImage($args = array())
	{
		if (is_string($args) || is_int($args)) {
			$args = array('src' => $args);
		}
		return new ResponsiveImage(array_merge(array('master' => $this), $args));
	}

}
?>