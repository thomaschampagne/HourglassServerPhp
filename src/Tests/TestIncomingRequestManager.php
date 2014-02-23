<?php

/**
 * 
 * Enter description here ...
 * @author Thomas Champagne
 *
 */
namespace Tests;

use PHPUnit_Framework_TestCase;
use Hourglass\Managers\IncomingRequestManager;
use Hourglass\Managers\Archiver;
use ZipArchive;
use Exception;

class TestIncomingRequestManager extends PHPUnit_Framework_TestCase
{

	protected $incomingRequestManager;

	public function setUp() {
		$this->incomingRequestManager = new IncomingRequestManager(HISTORY_FILE_PATH_TEST, FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST);
		$this->incomingRequestManager->setRequestFingerPrint(md5("requestFingerPrint"));
	}

	public function testGetRevNumber() {
		$this->assertEquals(9, $this->incomingRequestManager->getRevNumber());
	}
	
	public function testCountFiles() {
		$this->assertEquals(6, $this->incomingRequestManager->countVersionnedFiles());
		$this->assertEquals(5, $this->incomingRequestManager->countCurrentFiles());
	}
	
	public function testAllVersionnedFilesWithSimulationBehaviour() {

		$response = $this->incomingRequestManager->checkout(true);
		
		// Test global revision
		$this->assertEquals(9, $response[IncomingRequestManager::$OUTPUT_LAST_REV]);
		$this->assertEquals(5, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILECOUNT_KEY]);
		$this->assertNotEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$HASH_KEY]);
		$this->assertGreaterThan(0, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILESIZE_KEY]);
		$this->assertEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$BINARY_LINK_KEY]);
		
		// Test files to delete
		$this->assertCount(1, $response[IncomingRequestManager::$OUTPUT_FILES_TO_DELETE]);
		$this->assertEquals('grevemedecins.jpg', basename($response[IncomingRequestManager::$OUTPUT_FILES_TO_DELETE][0]));
	}


	public function testAllVersionnedFilesBehaviour() {

		$response = $this->incomingRequestManager->checkout();
		
		// Test global revision
		$this->assertEquals(9, $response[IncomingRequestManager::$OUTPUT_LAST_REV]);

		// Test file count returned
		$this->assertEquals(5, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILECOUNT_KEY]);
		$this->assertNotEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$HASH_KEY]);
		$this->assertGreaterThan(0, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILESIZE_KEY]);
		$this->assertNotEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$BINARY_LINK_KEY]);

		// Test files to delete
		$this->assertCount(1, $response[IncomingRequestManager::$OUTPUT_FILES_TO_DELETE]);
		$this->assertEquals('grevemedecins.jpg', basename($response[IncomingRequestManager::$OUTPUT_FILES_TO_DELETE][0]));
		
		// Test zip file
		$zipLink = $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$BINARY_LINK_KEY];

		// Retrieve and save to FS. 
		$zipBin = file_get_contents($zipLink);
		$tmpZipPath = APP_TMP.'test.zip';
		file_put_contents ($tmpZipPath , $zipBin);
		
		// Open it
		$zip = new ZipArchive();
		$zip->open($tmpZipPath);
		$extractPath = APP_TMP.'extract/';
		$zip->extractTo($extractPath);
		$zip->close();
		
		// Testing files existing or not
		$this->assertTrue(file_exists($extractPath.'content/news/fr/news.csv'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/hollande.jpg'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/montebourg.jpg'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/sarko.jpg'));
		$this->assertFalse(file_exists($extractPath.'content/news/fr/images/grevemedecins.jpg'));
		
		// Testing file integrity
		$this->assertNotEquals(md5_file(DATA_FOLDER_TEST.'/img/obama.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/obama_v2.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/sarko.jpg'), md5_file($extractPath.'content/news/fr/images/sarko.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/news/fr/3/news.csv'), md5_file($extractPath.'content/news/fr/news.csv'));
		
		$this->rrmdir($extractPath);
		$this->assertFalse(file_exists($extractPath));
		unlink($tmpZipPath); // delete zip tmp file
	}

	
	public function testPullFromRevisionWithArchiveSimulation () {
		
		// rev 2
		$response = $this->incomingRequestManager->pullFromRevision(2, null, true);
		$this->assertEquals(9, $response[IncomingRequestManager::$OUTPUT_LAST_REV]);
		$this->assertEquals(3, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILECOUNT_KEY]);
		$this->assertNotEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$HASH_KEY]);
		$this->assertGreaterThan(0, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILESIZE_KEY]);
		$this->assertEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$BINARY_LINK_KEY]);
		
		// rev 5
		$response = $this->incomingRequestManager->pullFromRevision(5, null, true);
		$this->assertEquals(9, $response[IncomingRequestManager::$OUTPUT_LAST_REV]);
		$this->assertEquals(1, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILECOUNT_KEY]);
		$this->assertNotEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$HASH_KEY]);
		$this->assertGreaterThan(0, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILESIZE_KEY]);
		$this->assertEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$BINARY_LINK_KEY]);
	}
	
	public function testPullFromRevisionWithlatestRevision () {
		$revision = 9; // 9 is latest
		$response = $this->incomingRequestManager->pullFromRevision($revision, null);
		$this->assertEquals($revision, $response[IncomingRequestManager::$OUTPUT_LAST_REV]);
	}
	
	public function testPullFromRevisionWithTooUpperRevision () {
	
		$revision = 11; 		
		$catched = false;
		try {
			$response = $this->incomingRequestManager->pullFromRevision($revision);
		} catch (Exception $e) {
			$catched = true;
		}
		$this->assertTrue($catched);
	}

	
	public function testPullFromRevision () {
	
		$response = $this->incomingRequestManager->pullFromRevision(3, null);
		
		$this->assertEquals(9, $response[IncomingRequestManager::$OUTPUT_LAST_REV]);
		$this->assertEquals(2, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILECOUNT_KEY]);
		$this->assertNotEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$HASH_KEY]);
		$this->assertGreaterThan(0, $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$FILESIZE_KEY]);
		$this->assertNotEmpty($response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$BINARY_LINK_KEY]);
		
		// Test zip file
		$zipLink = $response[IncomingRequestManager::$OUTPUT_ARCHIVE][Archiver::$BINARY_LINK_KEY];

		// Retrieve and save to FS. 
		$zipBin = file_get_contents($zipLink);
		$tmpZipPath = APP_TMP.'test.zip';
		file_put_contents ($tmpZipPath , $zipBin);
		
		// Open it
		$zip = new ZipArchive();
		$zip->open($tmpZipPath);
		$extractPath = APP_TMP.'extract/';
		$zip->extractTo($extractPath);
		$zip->close();
		
		// testing
		$this->assertNotEquals(md5_file(DATA_FOLDER_TEST.'/img/obama.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/obama_v2.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertNotEquals(md5_file(DATA_FOLDER_TEST.'/img/montebourg.jpg'), md5_file($extractPath.'content/news/fr/images/montebourg.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/montebourg_v2.jpg'), md5_file($extractPath.'content/news/fr/images/montebourg.jpg'));
		
		$this->rrmdir($extractPath);
		$this->assertFalse(file_exists($extractPath));
		unlink($tmpZipPath); // delete zip tmp file
	}
	
	/**
     * @expectedException Exception
     */
	public function testPullFromRevisionWithUpperRevisionFromServer () {
	
		try {
			$response = $this->incomingRequestManager->pullFromRevision(9999, null);	
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(102, $e->getCode());
			throw $e;
		}
	}
	
	/**
     * @expectedException Exception
     */
	public function testPullFromRevisionWithRevisionNULL () {
		
		try {
			$response = $this->incomingRequestManager->pullFromRevision(null, null);
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(208, $e->getCode());
			throw $e;
		}
	}
	
	/**
     * @expectedException Exception
     */
	public function testPullFromRevisionWithRevisionString () {
		try {
			$response = $this->incomingRequestManager->pullFromRevision("string", null);
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(208, $e->getCode());
			throw $e;
		}
	}
	
	/**
	* @expectedException Exception
    */
	public function testPullFromRevisionWithRevisionZero () {
		try {
			$response = $this->incomingRequestManager->pullFromRevision(0, null);
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(208, $e->getCode());
			throw $e;
		}
	}
	
	public function testParametersCompliance () {

		/**
		 * Testing revision parameters compliance
		 */
		$catched = false;
		try {
			$vfList = $this->incomingRequestManager->pullFromRevision(-4);
		} catch (Exception $e) {
			$catched = true;
		}
		
		$this->assertTrue($catched);

		$catched = false;
		try {
			$vfList = $this->incomingRequestManager->pullFromRevision("hello");
		} catch (Exception $e) {
			$catched = true;
		}
		$this->assertTrue($catched);


	}
	
	/**
     * @expectedException Exception
     */
	public function testZipDestinationNullException () {

		$this->incomingRequestManager->getArchiver()->setZipDestination(null);
		$this->incomingRequestManager->checkout();
	}
	

	/** convert
	 * @access     public
	 * @param      string  $hexNumber      convert a hex string to binary string
	 * @return     string  binary string
	 */
	public function hex2bin($hexString)
	{
		$hexLenght = strlen($hexString);
		// only hex numbers is allowed
		if ($hexLenght % 2 != 0 || preg_match("/[^\da-fA-F]/", $hexString)) return FALSE;

		unset($binString);
		$binString = null;
		for ($x = 1; $x <= $hexLenght/2; $x++)
		{
			$binString .= chr(hexdec(substr($hexString,2 * $x - 2,2)));
		}

		return $binString;
	}

	public function rrmdir($dir) {
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
	}
}

