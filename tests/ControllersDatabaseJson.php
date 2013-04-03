<?php

namespace Xiphe\ResponsiveImages\tests;

use Xiphe as X;
use Xiphe\ResponsiveImages\controllers\DatabaseJson;

require_once '../vendor/autoload.php';
require_once 'Fixture.php';
require_once 'ControllersDatabase.php';

class ControllersDatabaseJson extends ControllersDatabase {

	public function tearDown()
	{
		parent::tearDown();
		DatabaseJson::destroySingleton();
	}

	public function fixture()
	{
		return Fixture::jsonDb();
	}

	public function testJsonDBisAppendedToMasterOnCreation()
	{
		$this->assertInstanceOf('Xiphe\ResponsiveImages\controllers\DatabaseJson', Fixture::validMaster()->getDB());
	}

	public function testThrowsErrorIfDbFileIsNotAvailable()
	{
		try {
			DatabaseJson::i(array(
				'jsonFile' => Fixture::cache('asdf/db.json')
			));
		} catch(\Xiphe\ResponsiveImages\controllers\Exception $e) {
			return;
		}

		$this->fail('Invalid DB File Exception not thrown.');
	}

	public function testStoresHashesInDB()
	{
		$db = $this->fixture();
		$hash = $db->getCacheHash('/foo/bar/');
		$db->save();

		$data = json_decode(file_get_contents(Fixture::cache('db.json')));
		$assertion = new \stdClass();
		$assertion->cache = (object) array($hash => '/foo/bar/');
		$this->assertEquals($assertion, $data);
	}


}