<?php 

namespace Xiphe\ResponsiveImages\models;

use Xiphe as X;

/**
 * Model File for a Responsive Image
 *
 * @copyright Copyright (c) 2013, Hannes Diercks
 * @author    Hannes Diercks <xiphe@gmx.de>
 * @version   1.0.0
 * @link      https://github.com/Xiphe/ResponsiveImages/
 * @package   ResponsiveImages
 */
class Image extends X\Base {

	protected $_defaultConfiguration = array(
		'width' => 'auto',
		'id' => '',
		'class' => 'xri_iamge xri_loading',
		'src' => false,
		'alt' => null,
		'title' => ''
	);

	public function init()
	{
		$this->addCallback('configurationInitiated', array($this, 'validateConfig'));
		parent::init();
	}

	public function __toString()
	{
		return '<img src="';
	}

	public function getConfig($key) {
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
}