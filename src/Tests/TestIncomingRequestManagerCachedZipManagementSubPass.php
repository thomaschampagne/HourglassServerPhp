<?php
namespace Tests;
use PHPUnit_Framework_TestCase;
use Hourglass\Managers\IncomingRequestManager;

/**
 *
 * Enter description here ...
 * @author Thomas Champagne
 *
 */
class TestIncomingRequestManagerCachedZipManagementSubPass extends PHPUnit_Framework_TestCase
{
	
	protected $incomingRequestManager;
	public static $requestFingerPrint01 = "request01pattern";
	public static $requestFingerPrint02 = "request02pattern";
	public static $callBinaryLinkCache;

	public function setUp() {
		$this->incomingRequestManager = new IncomingRequestManager(HISTORY_FILE_PATH_TEST, FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST);
	}

	public static  function setUpBeforeClass() {
		TestIncomingRequestManagerCachedZipManagementSubPass::cleanTmpZipFolder(); // Cleaning tmp dir at first
	}
	
	/**
	 *
	 * Call a getAll first, verify archive do not exist
	 * Re Call and verify archive already exist
	 */
	public function testArchiveFirstAndUseCacheWithGetAllFiles () {

		$this->setBackupStaticAttributes(true);
		
		// First call GetAll to generate a first zip
		
		$this->incomingRequestManager->setRequestFingerPrint(TestIncomingRequestManagerCachedZipManagementSubPass::$requestFingerPrint01);
		$this->incomingRequestManager->updateHistory();
		$response = $this->incomingRequestManager->checkout();
		$this->assertFalse($response["archive"]["archiveFromCache"]); // archive cache must be false
		$this->assertEquals(10, $response["latestRevision"]);
		
		$this::$callBinaryLinkCache = $response["archive"]["archiveBinaryLink"];
		
		// Recall same service
		$response = $this->incomingRequestManager->checkout();
		$this->assertTrue($response["archive"]["archiveFromCache"]); // archive cache must be true
		$this->assertEquals($this::$callBinaryLinkCache, $response["archive"]["archiveBinaryLink"]);

	}
	
	public function testArchiveFirstAndUseCacheWithGetAllFilesAndNewRevision () {

		// Adding more files
		copy(DATA_FOLDER_TEST.'/img/montebourg_v2.jpg', FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST.'/montebourg_v2.jpg');
		$this->assertTrue(file_exists(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST.'/montebourg_v2.jpg'));
		
		// Call
		$this->incomingRequestManager->setRequestFingerPrint($this::$requestFingerPrint01);
		$this->incomingRequestManager->updateHistory();
		$response = $this->incomingRequestManager->checkout();
		
		$this->assertFalse($response["archive"]["archiveFromCache"]); // archive cache must be false, new file revision
		$this->assertEquals(11, $response["latestRevision"]);
		$this->assertEquals(md5_file($response["archive"]["archiveBinaryLink"]).'_'.$this::$requestFingerPrint01.'_11.zip', basename($response["archive"]["archiveBinaryLink"]));
		$this->assertFalse(@file_get_contents($this::$callBinaryLinkCache)); // Old path should not exist anymore
		
		$this::$callBinaryLinkCache = $response["archive"]["archiveBinaryLink"]; // Keep new link in cache, for rev 11
		
		$httpGetResult = @file_get_contents($response["archive"]["archiveBinaryLink"]);
		if(!$httpGetResult)	$this->fail("unable to download");

		// Recall
		$this->incomingRequestManager->setRequestFingerPrint($this::$requestFingerPrint01);
		$this->incomingRequestManager->updateHistory();
		$response = $this->incomingRequestManager->checkout();
		$this->assertTrue($response["archive"]["archiveFromCache"]); // archive cache must be false, new file revision
		$this->assertEquals(11, $response["latestRevision"]);
		$this->assertEquals($this::$callBinaryLinkCache, $response["archive"]["archiveBinaryLink"]);
		
	}

	public static function rrmdir($dir) {
		foreach(glob($dir . '/*') as $file) {
			if(is_dir($file))
			$this->rrmdir($file);
			else
			unlink($file);
		}
		rmdir($dir);
	}

	public function tearDown(){

	}

	public static function tearDownAfterClass()
	{
		chmod(HISTORY_FILE_PATH_TEST, 0777);
		TestIncomingRequestManagerCachedZipManagementSubPass::cleanTmpZipFolder();
	}

	public static function cleanTmpZipFolder () {
		TestIncomingRequestManagerCachedZipManagementSubPass::rrmdir(APP_TMP);
		mkdir(APP_TMP, 0777);
		chown(APP_TMP, 'www-data');
		chgrp(APP_TMP, 'www-data');
	}

}
