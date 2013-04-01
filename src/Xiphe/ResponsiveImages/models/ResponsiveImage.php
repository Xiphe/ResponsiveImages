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
		'classes' => array('xri_iamge', 'xri_loading'),
		'src' => false,
		'alt' => null,
		'title' => ''
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
		$this->setCacheFolder();
		$this->fetchDimensions();
		$this->setExtension();

		/* Setup the placeholder image */
		$this->getSubImageForSize(1);
	}

	public function __toString()
	{
		return '<img src="';
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

	public function validateConfig($config)
	{
		if (!is_object($config->master)) {
			throw new Exception('invalid master in configuration.', 1);
		}

		if ($config->src === false) {
			throw new Exception('Missing src setting in configuration.', 1);
		}

		if (null !== $config->class) {
			$this->_configuration->classes = array_merge(
				$this->_configuration->classes,
				explode(' ', $config->class)
			);
		}

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

	public function setCacheFolder($sourceFolder = null)
	{
		if (null === $sourceFolder) {
			$sourceFolder = dirname($this->get('src'));
		}

		$sourceFolder = THETOOLS::DS($sourceFolder, true);
		$cacheDir = THETOOLS::DS($this->get('cacheDir'), true);
		$context = basename($sourceFolder);

		$hash = $this->_getCacheBindingHash($cacheDir, $sourceFolder);

		$this->setConfig('cacheFolder', $cacheDir.THETOOLS::DS("$hash/$context", true));
	}

	private function _getCacheBindingHash($cacheDir, $sourceFolder)
	{
		if (!isset($this->_bindings[$sourceFolder])) {

			$folderHash = md5(dirname($sourceFolder));
			$length = $this->get('cacheHacheStartLength');
			$hash = substr($folderHash, 0, $length);

			while (!$this->_validateCacheFolder($cacheDir, $hash, $sourceFolder)) {
				$length++;
				$hash = substr($folderHash, 0, $length);
			}

			$this->_bindings[$sourceFolder] = $hash;
		}

		return $this->_bindings[$sourceFolder];
	}

	private function _validateCacheFolder($cacheDir, $hash, $sourceFolder)
	{
		$fullPath = THETOOLS::DS($this->get('cacheDir'), true);
		$fullPath .= THETOOLS::DS($hash);

		if (!is_dir($fullPath)) {
			$this->_bindCacheFolder($fullPath, $sourceFolder);
			return true;
		} elseif ($this->_bindingIsCorrect($fullPath, $sourceFolder)) {
			return true;
		} else {
			return false;
		}
	}

	private function _bindCacheFolder($cacheFolder, $sourceFolder)
	{

		$file = $cacheFolder.'.xri_binding';

		if (!is_dir($cacheFolder)) {
			mkdir($cacheFolder, $this->get('cacheDirPermissions'), true);
		}

		if (!file_exists($file)) {
			$handle = fopen($file, 'w');
			fclose($handle);
		}

		file_put_contents($cacheFolder.'.xri_binding', $sourceFolder);
	}

	private function _bindingIsCorrect($cacheFolder, $sourceFolder)
	{
		return (file_get_contents($cacheFolder.'.xri_binding') === $sourceFolder);
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
		$this->setConfig('sourceWidth', $dims[0]);
		$this->setConfig('sourceHeight', $dims[1]);

		return $ratio;
	}

	public function setExtension()
	{
		$this->setConfig('extension', pathinfo($this->get('src'), PATHINFO_EXTENSION));
	}
}