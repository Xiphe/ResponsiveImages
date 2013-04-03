<?php 

namespace Xiphe\ResponsiveImages\controllers;

/**
 * Generic Database Controller
 *
 * @copyright Copyright (c) 2013, Hannes Diercks
 * @author    Hannes Diercks <xiphe@gmx.de>
 * @version   1.0.0
 * @link      https://github.com/Xiphe/ResponsiveImages/
 * @package   ResponsiveImages
 */
class Database extends \Xiphe\Base {

	protected $_singleton = true;

	private $_hashes = array(
		'cache' => array(),
		'config' => array()
	);

	protected $_context = false;

	public function init()
	{
		parent::init();
	}

	final public function getCacheFolderByHash($hash)
	{
		$this->_context = 'cache';
		$folder = $this->get($hash);
		$this->_context = false;
		return $folder;
	}

	final public function getCacheHash($dir, $startLength = 5)
	{
		$this->_context = 'cache';
		$hash = $this->_getHash($dir, $startLength);
		$this->_context = false;
		return $hash;
	}

	final public function getConfigHash($config, $startLength = 5)
	{
		$this->_context = 'config';
		$hash = $this->_getHash($config, $startLength);
		$this->_context = false;
		return $hash;
	}

	private function _getHash($data, $startLength)
	{
		$jsonData = json_encode($data);
		if (!isset($this->_hashes[$this->_context][$jsonData])) {
			$hash = $this->_createHash($data, $jsonData, $startLength);
		}

		return $this->_hashes[$this->_context][$jsonData];
	}

	private function _createHash($data, $jsonData, $length)
	{
		$sourceHash = md5($jsonData);
		$hash = substr($sourceHash, 0, $length);

		while (!($status = $this->validateHash($hash, $data))) {
			$length++;
			$hash = substr($sourceHash, 0, $length);
		}

		if ($status === 'new') {
			$this->add($hash, $data);
		}

		return $this->_hashes[$this->_context][$jsonData] = $hash;
	}
}