<?php
namespace Tests;
use PHPUnit_Framework_TestCase;
use Hourglass\Managers\Archiver;
use Hourglass\Managers\VersionningManager;
use Exception;

class TestArchiver extends PHPUnit_Framework_TestCase {

	protected $mArchiver;
	protected $historyFile = HISTORY_FILE_PATH_TEST;
	protected $folderToBeVersionnedPath = FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST;

	public function setUp() {
		
	}

	public function testArchiveWithoutFiles () {
		//$this->mArchiver->setVersionnedFilesList(null);
		$this->mArchiver = new Archiver(APP_TMP, FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, true);
		$this->assertEquals(-1, $this->mArchiver->createZip(null, "testArchive.zip"));
	}

	public function testZipDestinationNull () {

		$this->versionningManager = new VersionningManager($this->historyFile, $this->folderToBeVersionnedPath); // Create Version manager
		$this->versionningManager->updateHistoryFile();

		$catched = false;
		try {
			$this->mArchiver = new Archiver(null, FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, true);
			$this->mArchiver->setVersionnedFilesList($this->versionningManager->getVersionnedFiles());
			$this->mArchiver->zip("testArchive.zip");
		} catch (Exception $e) {
			$catched = true;
		}
		$this->assertTrue($catched);
	}

	public function testFileNoExistOnSystem() {
		$this->versionningManager = new VersionningManager($this->historyFile, $this->folderToBeVersionnedPath); // Create Version manager
		$this->versionningManager->updateHistoryFile();

		$catched = false;
		$this->mArchiver = new Archiver(APP_TMP, FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, true);
		$versionnedFiles = $this->versionningManager->getVersionnedFiles();

		try {
			// rename 1 file before archive to produc error
			$this->assertTrue(rename($versionnedFiles[0]->getFilePath(), $versionnedFiles[0]->getFilePath().'tmp'));
			$this->mArchiver->setVersionnedFilesList($versionnedFiles);
			$this->mArchiver->zip("testArchive.zip");

		} catch (Exception $e) {
			$catched = true;
		}

		$this->assertTrue($catched);

		// rename back file
		$this->assertTrue(rename($versionnedFiles[0]->getFilePath().'tmp', $versionnedFiles[0]->getFilePath()));
	}

	public function testNoZipFileOsPathDuringMakeHttpBinaryLinkFromOsLink () {

		$catched = false;
		try {
			$archiveName = "testArchive.zip";
			$this->mArchiver = new Archiver(APP_TMP, FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, true);
			$this->mArchiver->zip("testArchive.zip");
			$this->mArchiver->makeHttpBinaryLinkFromOsLink(APP_TMP.'/'.$archiveName.'.fake', null);
		} catch (Exception $e) {
			$catched = true;
		}

		$this->assertTrue($catched);
	}


	public function testMakeHttpBinaryLinkFromOsLinkRequestUri () {

		$catched = false;
		try {
			// Point an version file with 
			$filename = "version";
			$this->mArchiver = new Archiver(APP_TMP, FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, true);
			$r = $this->mArchiver->makeHttpBinaryLinkFromOsLink(APP_ROOT.'/'.$filename, "/hourglass/src/main/ws/call.php");

			$this->assertTrue(preg_match("#http://.*/hourglass/version#", $r));

		} catch (Exception $e) {
			$catched = true;
		}

		$this->assertTrue($catched);
	}

	public function testHttps () {

		// Https on
		$_SERVER["HTTPS"] = "on";
		$this->assertTrue(Archiver::isHttpsProtocol());

		// Https off
		$_SERVER["HTTPS"] = "off";
		$this->assertFalse(Archiver::isHttpsProtocol());
	}

	public function tearDown(){
		
		
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
}

?>