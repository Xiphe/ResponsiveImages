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

    public function init()
    {
        $this->addCallback('configurationInitiated', array($this, 'validateConfig'));
        parent::init();

        $this->setDimensions();
        $this->setQuality();
        $this->setName();
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
    }

    public function setDimensions($newWidth = null)
    {
        if ($newWidth === null) {
            $newWidth = $this->getConfig('width');
        }
        if ($newWidth === null) {
            $newWidth = $this->getConfig('defaultWidth');
        }

        $width = $this->getBreakpoint($newWidth);

        $this->setConfig('width', $width);
        $this->setConfig('height', round($width/$this->getConfig('ratio')));
    }

    public function setQuality()
    {
        $quality = $this->get('quality');

        switch ($this->get('extension')) {
        case 'jpg':
        case 'jpeg':
            if (empty($quality) || !in_array($quality, $this->get('qualities'))) {
                $quality = $this->get('defaultQuality');
                $this->setConfig('quality', $quality);
            }
            break;
        default:
            if (!empty($quality)) {
                $this->unsetConfig('quality');
            }
            break;
        }
    }

    public function setName()
    {
        $name = sprintf(
            '%s-%dx%d',
            pathinfo($this->get('src'), PATHINFO_FILENAME),
            $this->get('width'),
            $this->get('height')
        );

        if (null !== $this->get('quality')) {
            $name .= sprintf('q%d', $this->get('quality'));
        }

        $name .= sprintf('.%s', $this->get('extension'));

        $this->setConfig('name', $name);
    }

    public function ensureExistence()
    {
        $file = $this->get('cacheFolder').$this->get('name');
        if (file_exists($file)) {
            return $file;
        } else {
            $this->create();
        }
    }

    public function create()
    {
        $tmp = ini_get('memory_limit');
        ini_set('memory_limit', '1024M');

        $target = $this->get('cacheFolder').$this->get('name');
        $width = $this->get('width');
        $height = $this->get('height');
        $new_image = imagecreatetruecolor($width, $height);
        $original = $this->get('src');
        $extension = $this->get('extension');

        switch ($extension) {
        case 'gif':
            $original = imagecreatefromgif($original);
            break;
        case 'png':
            $original = imagecreatefrompng($original);
            break;
        default:
            $original = imagecreatefromjpeg($original);
            break;
        }
        
        if ($extension == 'png' || $extension == 'gif') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }

        if ($this->get('sharpen')) {
            $sharpenMatrix = array(
                array(-1.2, -1, -1.2),
                array(-1, 20, -1),
                array(-1.2, -1, -1.2)
            );
            $divisor = array_sum(array_map('array_sum', $sharpenMatrix));           
            $offset = 0;
           
            imageconvolution($original, $sharpenMatrix, $divisor, $offset);
        }

        imagecopyresampled(
            $new_image,
            $original, 
            0, 0, 0, 0,
            $width,
            $height,
            imagesx($original),
            imagesy($original)
        );

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), $this->get('cacheDirPermissions'), true);
        }

        switch ($extension) {
        case 'gif':
            $r = imagegif($new_image, $target);
            break;
        case 'png':
            $r = imagepng($new_image, $target, 0, PNG_NO_FILTER);
            break;
        default:
            $r = imagejpeg($new_image, $target, $this->get('quality'));
            break;
        }
        imagedestroy($original);
        imagedestroy($new_image);

        ini_set('memory_limit', $tmp);
    }

    /**
     * Get the next breakpoint for a given size.
     *
     * @param integer $targetSize
     *
     * @return integer
     */
    public function getBreakpoint($targetSize = 0)
    {
        $targetSize = intval($targetSize);
        $size = 0;
        $next = 0;
        $end = false;
        $breakPoints = $this->getConfig('breakPoints');
        $maxWidth = $this->getConfig('sourceWidth');

        foreach ($breakPoints as $start => $steps) {
            if (($n = next($breakPoints)) !== false) {
                $next = key($breakPoints);
            } else {
                $end = true;
            }

            if ($targetSize > $next && !$end) {
                continue;
            }

            $i = 0;
            while (($size = $start + ($i * $steps)) < $targetSize) {
                $i++;
            }
            break;
        }

        return $size > $maxWidth ? $maxWidth : $size;
    }

    public function __toString()
    {

    }
}