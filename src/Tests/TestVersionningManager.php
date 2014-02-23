<?php
namespace Tests;

use PHPUnit_Framework_TestCase;

use Hourglass\Managers\VersionningManager;
use Exception;

class TestVersionningManager extends PHPUnit_Framework_TestCase
{
	protected $versionningManager;
	protected $historyFile = HISTORY_FILE_PATH_TEST;
	protected $folderToBeVersionnedPath = FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST;


	public function TestVersionningManager() {
		
		
	}
	
	public function setUp() {
		$this->cleanAppContentTestFolder(); // Clean versionned folder for testing
		$this->populateVersionnedFolder(); // Create folder and files structure for testing purpose
		$this->versionningManager = new VersionningManager($this->historyFile, $this->folderToBeVersionnedPath); // Create Version manager
	}

	/**
	 * Test history file creation processes
	 */
	public function testHistoryFileCreation() {

		$this->assertEquals(HISTORY_FILE_PATH_TEST, $this->versionningManager->getHistoryFilePath());
		$this->assertEquals(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, $this->versionningManager->getVersionnedFolder());

		$created = $this->versionningManager->createHistoryFile();
		$this->assertTrue($created);
		
		// Recreate history file to that file is already created 
		$created = $this->versionningManager->createHistoryFile();
		$this->assertFalse($created);
		
	}
	
	/**
	* Test no files into folder
	*/
	public function testEmptyFolder() {

		// Create empry folder
		$versionnedFolderEmpty = FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST.'/../empty/';
		
		try {
			mkdir($versionnedFolderEmpty, 0777);
		} catch (Exception $e) {}

		$this->versionningManager->setVersionnedFolder($versionnedFolderEmpty);
		$this->assertFalse($this->versionningManager->isHistoryFileExist());
		
		// Simulate a createHistoryFile failure 
		chmod(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, 0000);
		$this->versionningManager->updateHistoryFile();
		
		chmod(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST, 0777);
	}
	
	
	/**
	 * Testing updateHistory with no files into versionned folder
	 */
	public function testUpdateHistoryFileWithEmptyContent () {

		$this->cleanAppContentTestFolder(); // Clean all :)
		mkdir($this->folderToBeVersionnedPath); // Just create versioning folder only
		
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertEmpty($versionnedFiles);
		$this->assertEquals(FIRST_REV_NUMBER, $this->versionningManager->getRevNumber());
	}

	/**
	 * Testing updateHistory with files versionned folder
	 */
	public function testUpdateHistoryFileWithContent () {

		$versionnedFiles = $this->versionningManager->updateHistoryFile(); // Versionning process is done for the first time
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(3, $versionnedFiles);
		$this->assertEquals(FIRST_REV_NUMBER, $this->versionningManager->getRevNumber());

		// Testing files
		foreach ($versionnedFiles as $f) {
			$this->assertNotEmpty($f->getFilePath());
			$this->assertNotEmpty($f->getFingerPrint());
			$this->assertNotEmpty($f->getRevDate());
			$this->assertEquals(0, $f->getIsDeleted());
			$this->assertEquals(FIRST_REV_NUMBER, $f->getRevNumber());
		}

		$this->versionningManager = new VersionningManager($this->historyFile, $this->folderToBeVersionnedPath);
		$this->assertEquals(FIRST_REV_NUMBER, $this->versionningManager->getRevNumber());
	}
	
	/**
	 *
	 * Adding a new file
	 */
	public function testFileChangeDetection_1 () {

		// Versioning phase 1
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		// Versioning phase 1 - testing
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(3, $versionnedFiles);

		// Add a new file
		copy(DATA_FOLDER_TEST.'/img/montebourg.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/montebourg.jpg');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/montebourg.jpg'));

		// Versioning phase 2
		$versionnedFiles = $this->versionningManager->updateHistoryFile();

		// Versioning phase 2 - testing
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(4, $versionnedFiles);
		$this->assertEquals(2, $this->versionningManager->getRevNumber());
	}

	/**
	 *
	 * Changing a file
	 */
	public function testFileChangeDetection_2 () {

		// Versioning phase 1
		$versionnedFiles = $this->versionningManager->updateHistoryFile();

		// Versioning phase 1 - testing
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(3, $versionnedFiles);

		// modify news file fr
		copy(DATA_FOLDER_TEST.'/news/fr/2/news.csv', $this->folderToBeVersionnedPath.'/news/fr/news.csv');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/news.csv'));

		// Versioning phase 2
		$versionnedFiles = $this->versionningManager->updateHistoryFile();

		// Versioning phase 2 - testing
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(3, $versionnedFiles);

		$modifedVersionnedFilePath = $this->folderToBeVersionnedPath.'/news/fr/news.csv';
		$modifedVersionnedFile = $this->versionningManager->getVersionnedFileFromFilePath($modifedVersionnedFilePath);
		$this->assertNotNull($modifedVersionnedFile);
		$this->assertEquals(md5_file($modifedVersionnedFilePath), $modifedVersionnedFile->getFingerPrint());
		$this->assertEquals(2, $modifedVersionnedFile->getRevNumber());
		$this->assertNotNull($modifedVersionnedFile->getRevDate());

		// Testing new revision number (= first_rev_number + 1)
		$this->assertEquals(2, $this->versionningManager->getRevNumber());
	}

	/**
	*
	* Adding a new file
	*/
	public function testFileChangeDetection_2_1 () {
	
		$this->cleanAppContentTestFolder(); // Clean all :)
		mkdir($this->folderToBeVersionnedPath); // Just create versioning folder only
		
		// Testing first versionning: only version folder presnt, no history file
		
		// Versioning phase 1
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertEmpty($versionnedFiles);
		$this->assertEquals(1, $this->versionningManager->getRevNumber());
	}
	
	
	/**
	 *
	 * Deleting a file
	 */
	public function testFileChangeDetection_3 () {

		// Versioning phase 1
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		// Versioning phase 1 - testing
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(3, $versionnedFiles);

		// delete a new file
		unlink($this->folderToBeVersionnedPath.'/news/fr/images/hollande.jpg');
		$this->assertFalse(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/hollande.jpg'));

		// Versioning phase 2
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$versionnedFiles = $this->versionningManager->getVersionnedFiles();
		//var_dump($versionnedFiles);

		// Versioning phase 2 - testing
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(3, $versionnedFiles);
		$this->assertEquals(2, $this->versionningManager->getRevNumber());
	}

	
	/**
	 *
	 * Avanced testing:
	 *  - +Queries on VersionningManager ....
	 */
	public function testFileChangeDetection_4 () {

		// Versioning phase 1
		$versionnedFiles = $this->versionningManager->updateHistoryFile();

		// Versioning phase 1 - testing
		$this->assertNotNull($versionnedFiles);
		$this->assertCount(3, $versionnedFiles);

		/**
		 * Files changes wave 1
		 */
		//sleep(2);
		//# Add a new file A
		copy(DATA_FOLDER_TEST.'/img/montebourg.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/montebourg.jpg');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/montebourg.jpg'));

		//# Add a new file B
		copy(DATA_FOLDER_TEST.'/img/sarko.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/sarko.jpg');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/sarko.jpg'));

		//# Modify news file fr
		copy(DATA_FOLDER_TEST.'/news/fr/2/news.csv', $this->folderToBeVersionnedPath.'/news/fr/news.csv');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/news.csv'));

		// Versioning phase 2 + testing
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertNotEmpty($versionnedFiles);
		$this->assertEquals((FIRST_REV_NUMBER + 1), $this->versionningManager->getRevNumber());
		$this->assertCount(5, $versionnedFiles);

		$sarkoFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/sarko.jpg';
		$sarkoVF = $this->versionningManager->getVersionnedFileFromFilePath($sarkoFilePath);
		$this->assertEquals(md5_file($sarkoFilePath), $sarkoVF->getFingerPrint());
		$this->assertFalse($sarkoVF->getIsDeleted());

		/**
		 * Files changes wave 2
		 */
		//sleep(2);
		// Delete a obama file
		unlink($this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg');
		$this->assertFalse(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg'));

		// Modify news file fr
		copy(DATA_FOLDER_TEST.'/news/fr/3/news.csv', $this->folderToBeVersionnedPath.'/news/fr/news.csv');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/news.csv'));

		// Versioning phase 3
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(5, $versionnedFiles); // Always 5 files
		$this->assertEquals((FIRST_REV_NUMBER + 2), $this->versionningManager->getRevNumber());

		$newsFrFilePath = $this->folderToBeVersionnedPath.'/news/fr/news.csv';
		$newsFrVF = $this->versionningManager->getVersionnedFileFromFilePath($newsFrFilePath);
		$this->assertEquals((FIRST_REV_NUMBER + 2), $newsFrVF->getRevNumber());
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/news/fr/3/news.csv'), $newsFrVF->getFingerPrint());
		$this->assertFalse($newsFrVF->getIsDeleted());

		$obamaFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg';
		$obamaVF = $this->versionningManager->getVersionnedFileFromFilePath($obamaFilePath);
		$this->assertTrue($obamaVF->getIsDeleted());
		$this->assertNotNull($obamaVF->getRevDate());
		$this->assertEquals((FIRST_REV_NUMBER + 2), $obamaVF->getRevNumber());


		/**
		 * FALSE file change wave
		 */
		//sleep(2);
		// Versioning phase 4
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$versionnedFiles = $this->versionningManager->updateHistoryFile();

		$this->assertCount(5, $versionnedFiles); // Always 5 files
		$this->assertEquals((FIRST_REV_NUMBER + 2), $this->versionningManager->getRevNumber());

		$newsFrFilePath = $this->folderToBeVersionnedPath.'/news/fr/news.csv';
		$newsFrVF = $this->versionningManager->getVersionnedFileFromFilePath($newsFrFilePath);
		$this->assertEquals((FIRST_REV_NUMBER + 2), $newsFrVF->getRevNumber());
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/news/fr/3/news.csv'), $newsFrVF->getFingerPrint());
		$this->assertFalse($newsFrVF->getIsDeleted());

		$obamaFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg';
		$obamaVF = $this->versionningManager->getVersionnedFileFromFilePath($obamaFilePath);
		$this->assertTrue($obamaVF->getIsDeleted());
		$this->assertEquals((FIRST_REV_NUMBER + 2), $obamaVF->getRevNumber());

		/**
		 * Files changes wave 3
		 */
		//sleep(2);
		// Modify image
		copy(DATA_FOLDER_TEST.'/img/montebourg_v2.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/montebourg.jpg');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/montebourg.jpg'));

		// Versioning phase 4
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(5, $versionnedFiles); // Always 5 files
		$this->assertEquals((FIRST_REV_NUMBER + 3), $this->versionningManager->getRevNumber());

		$montebourgFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/montebourg.jpg';
		$montebourgVF = $this->versionningManager->getVersionnedFileFromFilePath($montebourgFilePath);
		$this->assertEquals((FIRST_REV_NUMBER + 3), $montebourgVF->getRevNumber());
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/montebourg_v2.jpg'), $montebourgVF->getFingerPrint());
		$this->assertEquals(filemtime($montebourgFilePath), $montebourgVF->getLastEditDate());
		$this->assertFalse($montebourgVF->getIsDeleted());

		/**
		 * Files changes wave 4
		 */
		//sleep(2);
		// Add new file
		copy(DATA_FOLDER_TEST.'/img/grevemedecins.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/grevemedecins.jpg');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/grevemedecins.jpg'));

		// Versioning phase 4
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(6, $versionnedFiles); // now 6 files
		$this->assertEquals((FIRST_REV_NUMBER + 4), $this->versionningManager->getRevNumber());

		$medecinsFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/grevemedecins.jpg';
		$medecinsVF = $this->versionningManager->getVersionnedFileFromFilePath($medecinsFilePath);
		$this->assertEquals((FIRST_REV_NUMBER + 4), $medecinsVF->getRevNumber());

		/**
		 * FALSE file change wave
		 */
		//sleep(2);
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(6, $versionnedFiles); // now 6 files
		$this->assertEquals((FIRST_REV_NUMBER + 4), $this->versionningManager->getRevNumber());

		/**
		 * Files changes wave 4
		 */
		//sleep(2);
		// Re-add obama image
		copy(DATA_FOLDER_TEST.'/img/obama.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg'));

		// We want to verify that a file which has been
		// flagged deleted in the past become available again
		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(6, $versionnedFiles); // Always 6 files (obama image re-added)
		$this->assertEquals((FIRST_REV_NUMBER + 5), $this->versionningManager->getRevNumber()); // revision +1


		$obamaFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg';
		$obamaVF = $this->versionningManager->getVersionnedFileFromFilePath($obamaFilePath);
		$this->assertFalse($obamaVF->getIsDeleted());
		$this->assertEquals((FIRST_REV_NUMBER + 5), $obamaVF->getRevNumber());


		/**
		 * Files changes wave 5
		 */
		//sleep(2);
		// Remove again obama
		unlink($this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg');
		$this->assertFalse(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg'));

		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(6, $versionnedFiles); // Always 6 files (obama image re-added)
		$this->assertEquals((FIRST_REV_NUMBER + 6), $this->versionningManager->getRevNumber());

		$obamaFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg';
		$obamaVF = $this->versionningManager->getVersionnedFileFromFilePath($obamaFilePath);
		$this->assertTrue($obamaVF->getIsDeleted());
		$this->assertEquals((FIRST_REV_NUMBER + 6), $obamaVF->getRevNumber());

		/**
		 * Files changes wave 6
		 */
		//sleep(2);
		//Modify obama image
		copy(DATA_FOLDER_TEST.'/img/obama_v2.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg');
		$this->assertTrue(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg'));

		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(6, $versionnedFiles); // Always 6 files (obama image re-added)
		$this->assertEquals((FIRST_REV_NUMBER + 7), $this->versionningManager->getRevNumber());

		$obamaFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg';
		$obamaVF = $this->versionningManager->getVersionnedFileFromFilePath($obamaFilePath);
		$this->assertFalse($obamaVF->getIsDeleted());
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/obama_v2.jpg'), $obamaVF->getFingerPrint());
		$this->assertEquals((FIRST_REV_NUMBER + 7), $obamaVF->getRevNumber());

		/**
		 * Files changes wave 7
		 */
		//sleep(2);
		// Remove medecin
		unlink($this->folderToBeVersionnedPath.'/news/fr/images/grevemedecins.jpg');
		$this->assertFalse(file_exists($this->folderToBeVersionnedPath.'/news/fr/images/grevemedecins.jpg'));

		$versionnedFiles = $this->versionningManager->updateHistoryFile();
		$this->assertCount(6, $versionnedFiles); // now 6 files
		$this->assertEquals((FIRST_REV_NUMBER + 8), $this->versionningManager->getRevNumber());

		$medecinsFilePath = $this->folderToBeVersionnedPath.'/news/fr/images/grevemedecins.jpg';
		$medecinsVF = $this->versionningManager->getVersionnedFileFromFilePath($medecinsFilePath);
		$this->assertEquals((FIRST_REV_NUMBER + 8), $medecinsVF->getRevNumber());
		$this->assertTrue($medecinsVF->getIsDeleted());
		$this->assertEquals(time(), $medecinsVF->getRevDate());
		$this->assertEquals(time(), $this->versionningManager->getRevDate());
		

		/********************
		 * Query !!
		********************/

		/**
		 * Obtain All Versionned Files From History
		 */
		$vfList = $this->versionningManager->checkoutFromHistory();
		$this->assertCount(6, $vfList);
		/**
		 * Obtain All Versionned Files From History without deleted
		 */
		$vfList = $this->versionningManager->checkoutFromHistory(null, false);
		$this->assertCount(5, $vfList);
		foreach ($vfList as $vf) {
			$this->assertFalse($vf->getIsDeleted());
		}
// 		exit;
		/**
		 * Obtain Delta of Versionned Files from Revision Number given to the latest
		 */
		$vfList = $this->versionningManager->checkoutFromRevision(4);
		$this->assertCount(2, $vfList);
		$vfList = $this->versionningManager->checkoutFromRevision(4, null, false);
		$this->assertCount(1, $vfList);
		
		$vfList = $this->versionningManager->checkoutFromRevision(2);
		$this->assertCount(4, $vfList);
		$vfList = $this->versionningManager->checkoutFromRevision(2, null, false);
		$this->assertCount(3, $vfList);
		
		/**
		 * Obtain Versionned Files for a specific Revision Number given
		 */
		$vfList = $this->versionningManager->checkoutWithRevision(1);
		$this->assertCount(1, $vfList);

	}

	public function populateVersionnedFolder() {

		// Folder structure
		mkdir($this->folderToBeVersionnedPath, 0777, true);
		mkdir($this->folderToBeVersionnedPath.'/news/', 0777, true);
		mkdir($this->folderToBeVersionnedPath.'/news/fr/', 0777, true);
		mkdir($this->folderToBeVersionnedPath.'/news/fr/images/', 0777, true);
		mkdir($this->folderToBeVersionnedPath.'/news/en/', 0777, true);
		mkdir($this->folderToBeVersionnedPath.'/news/en/images/', 0755, true);
		mkdir($this->folderToBeVersionnedPath.'/agenda/en/', 0777, true);
		mkdir($this->folderToBeVersionnedPath.'/agenda/fr/', 0777, true);

		// Files
		copy(DATA_FOLDER_TEST.'/news/fr/1/news.csv', $this->folderToBeVersionnedPath.'/news/fr/news.csv');
		copy(DATA_FOLDER_TEST.'/img/obama.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/obama.jpg');
		copy(DATA_FOLDER_TEST.'/img/hollande.jpg', $this->folderToBeVersionnedPath.'/news/fr/images/hollande.jpg');

	}

	
	
	
	/**
	 * 
	 * Remove versionning file and content into forlder to be versionned
	 */
	public function cleanAppContentTestFolder() {

		// Delete history file
		if(file_exists(HISTORY_FILE_PATH_TEST)) unlink(HISTORY_FILE_PATH_TEST);

		// Delete app content test folder
		if(file_exists(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST)) $this->rrmdir(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST);

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


}