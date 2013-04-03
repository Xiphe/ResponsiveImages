<?php 

namespace Xiphe\ResponsiveImages\controllers;

/**
 * Database Controller for json
 *
 * @copyright Copyright (c) 2013, Hannes Diercks
 * @author    Hannes Diercks <xiphe@gmx.de>
 * @version   1.0.0
 * @link      https://github.com/Xiphe/ResponsiveImages/
 * @package   ResponsiveImages
 */
class DatabaseJson extends Database implements \Xiphe\ResponsiveImages\interfaces\DatabaseInterface {
	
	private $_DB;

	public function init()
	{
		parent::init();

		if (null === ($file = $this->getConfig('jsonFile'))) {
			$constName = 'XIPHE_RESPONSIVEIMAGES_JSONDATABASEFILE';
			$file = defined($constName) ? constant($constName)
				: realpath(dirname(__FILE__).'/../../../../').'/db.json';

			$this->setConfig('jsonFile', $file);
		}

		if (!is_file($file)) {
			$this->unsetConfig('jsonFile');
			throw new Exception('Database file for json DB not available. Should be here: "'.$file.'".', 1);
		}

		$this->_DB = json_decode(file_get_contents($file));
		if (!is_object($this->_DB)) {
			$this->_DB = new \stdClass();
		}
	}


	public function add($hash, $data)
	{
		$context = $this->_context;
		if (!is_object($this->_DB)) {
			var_dump($this->_DB);
			var_dump('ERROR');
		}
		if (!isset($this->_DB->$context)) {
			$this->_DB->$context = new \stdClass();
		}
		$this->_DB->$context->$hash = $data;
	}

	public function get($hash)
	{
		$context = $this->_context;

		if (!isset($this->_DB->$context) || !isset($this->_DB->$context->$hash)) {
			throw new Exception(sprintf('Unknown %s Hash "%s"', $context, $hash), 1);
		}

		return $this->_DB->$context->$hash;
	}

	public function validateHash($hash, $data)
	{
		$context = $this->_context;

		if (!isset($this->_DB->$context) || !isset($this->_DB->$context->$hash)) {
			return 'new';
		}

		return ($this->_DB->$context->$hash === $data);
	}

	public function save()
	{
		if (null !== ($file = $this->getConfig('jsonFile')) && file_exists($file)) {
			file_put_contents($file, json_encode($this->_DB));
		}
	}

	public function __destruct()
	{
		$this->save();
	}

}