<?php 

namespace Xiphe\ResponsiveImages\controllers;

use Xiphe as X;
use Xiphe\ResponsiveImages\models\Image;


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
	protected $_defaultConfiguration = array(
		'databaseController' => 'Xiphe\\ResponsiveImages\\controller\\DBControllerJson',
		'mediaUrl' => false,
		'cacheLivetime' => 2592000,
		'nojsCookieDuration' => 604800,
		'prefix' => 'xri_',
		'hookIntoWordpress' => true,
		'cacheDir' => 'cache',
		'initWidth' => 50,
		'breakPoints' => array(
		 /* start-point => step-size */
			0 => 50,
			200 => 100,
			600 => 200,
			2000 => 400
		)
	);

	public function init()
	{
		$this->addCallback('configurationInitiated', array($this, 'validateConfig'));
		parent::init();
	}

	public function validateConfig($config)
	{
		if ($config->mediaUrl === false) {
			throw new Exception('Missing mediaUrl setting in configuration.', 1);
		}
	}

	public function getImage($args = array())
	{
		if (is_string($args) || is_int($args)) {
			$args = array('src' => $args);
		}
		return new Image(array_merge(array('master' => $this), $args));
	}

}
?>