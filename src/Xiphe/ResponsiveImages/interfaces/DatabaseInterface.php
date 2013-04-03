<?php 

namespace Xiphe\ResponsiveImages\interfaces;

/**
 * Generic Database Controller
 *
 * @copyright Copyright (c) 2013, Hannes Diercks
 * @author    Hannes Diercks <xiphe@gmx.de>
 * @version   1.0.0
 * @link      https://github.com/Xiphe/ResponsiveImages/
 * @package   ResponsiveImages
 */
interface DatabaseInterface {
	
	public function getConfigHash($config);

	public function getCacheHash($folder);

	public function validateHash($hash, $data);

	public function add($hash, $data);

	public function save();

	// public function updateResponsiveImage();
}