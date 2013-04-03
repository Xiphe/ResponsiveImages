<?php 

namespace Xiphe\ResponsiveImages\models;

use Xiphe\THETOOLS;
use Xiphe\Base;

/**
 * Model File for a Responsive Image
 *
 * @copyright Copyright (c) 2013, Hannes Diercks
 * @author    Hannes Diercks <xiphe@gmx.de>
 * @version   1.0.0
 * @link      https://github.com/Xiphe/ResponsiveImages/
 * @package   ResponsiveImages
 */
class ResponsiveImage extends Base {

	protected $_defaultConfiguration = array(
		'defaultWidth' => 'auto',
		'id' => '',
		'classes' => array('image', 'loading'),
		'src' => false,
		'alt' => null,
		'title' => '',
		'maxWidth' => 1,
		'maxHeight' => 1,
		'sourceSize' => false
	);

	private static $_configKeysToSave = array(
		'classPrefix',
		'defaultQuality',
		'qualities',
		'_mediaUrl',
		'sharpen',
		'breakPoints',
		'sourceSize',
		'ratio',
		'maxWidth',
		'maxHeight'
	);

	private static $_ajaxConfigKeys = array(
		'qualities',
		'breakPoints',
		'ratio',
		'maxWidth',
		'maxHeight',
	);

	private $_bindings = array();

	public $sizes = array();

	public function init()
	{
		$this->addCallback('configurationInitiated', array($this, 'validateConfig'));
		parent::init();

		/* Validate */
		$this->ensureSourceImageExists();

		/* Gather Basic Data */
		$this->setCacheFolderTo(dirname($this->get('src')));
		$this->setUrl();
		$this->fetchDimensions();
		$this->setExtension();

		/* Setup the placeholder image */
		$this->getSubImageForSize(1);
	}

	public function __toString()
	{
		return '<img src="';
	}

	public function getFullConfig()
	{
		$ownConfig = (array) $this->_configuration;
		unset($ownConfig['master']);

		$masterConfig = (array) $this->get('master')->getFullConfiguration();
		unset($masterConfig['_db']);

		return (object) array_merge($masterConfig, $ownConfig);
	}

	public function getSubImageForSize($size)
	{
		$image = new Image(array(
			'width' => $size,
			'master' => $this
		));

		$width = $image->getConfig('width');

		if (!isset($this->sizes[$width])) {
			$this->sizes[$width] = $image;
		} else {
			$image = $this->sizes[$width];
		}

		return $image;
	}

	public function get($key)
	{
		return $this->getConfig($key);
	}

	public function getConfig($key)
	{
		$result = parent::getConfig($key);
		if ($result === null) {
			$result = $this->_configuration->master->getConfig($key);
		}
		return $result;
	}

	public function setConfig($key, $value)
    {
        if (substr($key, 0, 1) === '_') {
            throw new Exception('forbidden: one does not simply set protected settings.', 1);
        }
        parent::setConfig($key, $value);
    }

	public function validateConfig($config)
	{
		if (!is_object($config->master)) {
			throw new Exception('invalid master in configuration.', 1);
		}

		if ($config->src === false) {
			throw new Exception('Missing src setting in configuration.', 1);
		}

		$this->prefixClasses();

		$this->mergeClasses();

		$this->setConfig(
			'src',
			$this->_configuration->master->doCallback(
				'fileFinder',
				array(
					$this->getConfig('src')
				),
				true
			)
		);
	}

	public function prefixClasses()
	{
		$classes = $this->get('classes');
		array_walk($classes, function (&$item, $key, $prefix) {
			$item = $prefix.$item;
		}, $this->get('prefix'));

		$this->setConfig('classes', $classes);
	}

	public function mergeClasses()
	{
		$class = $this->get('class');
		if (null !== $class) {
			$this->setConfig('classes', array_merge(
				$this->get('classes'),
				explode(' ', $class)
			));
		}
	}

	public function ensureSourceImageExists()
	{
		$source = $this->getConfig('src');

		if (!($source = realpath($source))) {
			$source = THETOOLS::DS($this->getConfig('baseDir'), true);
			$source .= THETOOLS::unPreDS($this->getConfig('src'), true);

			if (!file_exists($source)) {
				throw new Exception(sprintf('Image File "%s" could not be found.', $this->getConfig('src')), 1);
			}

		}

		$this->setConfig('src', $source);
	}

	public function setUrl()
	{
		$this->setConfig(
			'url',
			THETOOLS::slash($this->get('_mediaUrl')).THETOOLS::slash($this->get('cacheFolderName'), true)
		);
	}

	public function setCacheFolderTo($sourceFolder)
	{
		$sourceFolder = THETOOLS::DS($sourceFolder, true);
		$cacheDir = THETOOLS::DS($this->get('_cacheDir'), true);
		$context = basename($sourceFolder);

		$hash = $this->get('master')->getDB()->getCacheHash(
			dirname($sourceFolder).DIRECTORY_SEPARATOR,
			$this->get('_hashesStartLength')
		);

		$cacheSubDir = THETOOLS::DS("$hash/$context", true);

		$this->setConfig('cacheFolderName', $cacheSubDir);
		$this->setConfig('cacheFolder', $cacheDir.$cacheSubDir);
	}

	public function fetchDimensions()
	{
		$source = $this->getConfig('src');
		$tmp = ini_get('memory_limit');
		ini_set('memory_limit', '1024M');
		$dims = @getimagesize($source);
		ini_set('memory_limit', $tmp);

		if (!is_array($dims)) {
			throw new Exception(sprintf('Failed to receive image size from image: %s.', $source), 1);
		}

		$ratio = round($dims[0]/$dims[1], 4);
		$this->setConfig('ratio', $ratio);
		$this->setConfig('maxWidth', $dims[0]);
		$this->setConfig('maxHeight', $dims[1]);

		return $ratio;
	}

	public function setExtension()
	{
		$this->setConfig('extension', pathinfo($this->get('src'), PATHINFO_EXTENSION));
	}
}