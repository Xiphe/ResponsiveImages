<?php

include 'vendor/autoload.php';

use Xiphe\ResponsiveImages\controllers\Main;
use Xiphe as X;

$dir = 'foo/bar/test/somewhere/else/';
$dirTmpl = 'another/colliding/folder/uMeaj/else/';

var_dump(substr(md5(dirname($dir)), 0, 1));
var_dump(substr(md5(dirname($dirTmpl)), 0, 1));
die();

$dir2 = str_replace('::', 'a', $dirTmpl);
$i = 0;
while($i < 100 && substr(md5(dirname($dir2)), 0, 1) != 3) {
	$str = X\THETOOLS::get_randomString(5, 'aAn');
	$dir2 = str_replace('::', $str, $dirTmpl);
	$i++;
}

die($str);

$RI = Main::i(array(
	'mediaUrl' => 'http://localhost/ResponsiveImages/'
));

$Image = $RI->getImage(array(
	'src' => 'tests/data/sunset.jpg'
));
var_dump($Image->getConfig('ratio'));

// include 'vendor/xiphe/thedebug/globaldebug.php';

