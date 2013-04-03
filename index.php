<?php

include 'vendor/autoload.php';

use Xiphe\ResponsiveImages\controllers\Main;
use Xiphe as X;


// $dir = '/a/test/folder/somewhere/';
// $dirTmpl = '/another/::/colliding/folder/';

// // var_dump(substr(md5($dir), 0, 5));
// // var_dump(substr(md5($dirTmpl), 0, 5));
// // die();

// function randomDir()
// {
// 	$dirTmpl = '/another/::/colliding/folder/';
// 	$dir = trim(X\THETOOLS::get_randomString(5, 'an', '///abcdef//////ghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz'), '/');
// 	$dir = preg_replace('/[\/]+/', '/', $dir);

// 	return str_replace('::', $dir, $dirTmpl);
// }

// $dir2 = randomDir();
// $searchsurce = md5(json_encode($dir));
// $length = 3;

// $search = substr($searchsurce, 0, $length);
// $i = 0;
// while(substr(md5(json_encode($dir2)), 0, $length) != $search) {
// 	$dir2 = randomDir();
// 	if ($i > 1000000) {
// 		die('nothing Found');
// 	}
// 	$i++;
// }

// var_dump(substr(md5($dir2), 0, $length));
// die($dir2);

$RI = Main::i(array(
	'_mediaUrl' => 'http://localhost/ResponsiveImages/'
));

$Image = $RI->getImage(array(
	'src' => 'tests/data/sunset.jpg'
));
var_dump($RI->getDB()->getCacheHash('/foo/bar'));

// include 'vendor/xiphe/thedebug/globaldebug.php';

