<?php
namespace Hourglass\Managers;

use Logger;
use Exception;
use Hourglass\Model\VersionnedFile;

class VersionningManager {

	public static $REVISION = "revision";
	public static $REVISIONDATE = "revisionDate";
	public static $REVISIONFILES =  "revisionFiles";
	
	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $historyFilePath;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $versionnedFolder;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $revNumber;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $revDate;

	/**
	 *
	 * Enter description here ...
	 * @var VersionnedFile array
	 */
	protected $versionnedFiles;

	/**
	 *
	 * Enter description here ...
	 * @var unknown_type
	 */
	protected $logger;

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $historyFilePath
	 * @param unknown_type $folderToBeVersionnedPath
	 */
	public function __construct($historyFilePath, $folderToBeVersionnedPath) {
		$this->logger = Logger::getRootLogger();
		$this->logger->debug("Building VersionningManager");

		$this->setHistoryFilePath($historyFilePath);
		$this->setVersionnedFolder($folderToBeVersionnedPath);
	}

	/**
	 *
	 * Enter description here ...
	 * @return boolean
	 */
	public function createHistoryFile() {
		$filename = $this->getHistoryFilePath();
		if(!file_exists($filename)) {
			$handle = fopen($filename, 'w+');
			fclose($handle);
			chmod($filename, 0755);
			$this->logger->debug("History file '".$filename."' created");
			if($handle) return true;

		} else {
			$this->logger->debug("History file '".$this->getHistoryFilePath()."' already exists");
		}
		return false;
	}


	/**
	 *
	 * Update history file (new files, modified files, removed files)
	 * @return ?
	 */
	public function updateHistoryFile() {

		$this->logger->info("Update History File");

		$scannedFileslist = $this->scanVersionnedFolder();

		if($scannedFileslist != null) {
			// Some files are present

			if(!$this->isHistoryFileExist()) {
				
				// Versionning for the first time or reseted..
				$createdFile = $this->createHistoryFile(); // Create/Write into new history file

				foreach ($scannedFileslist as $scannedFile) {
					// Set rev number to FIRST_REV_NUMBER for all files
					$scannedFile->setRevNumber(FIRST_REV_NUMBER);
					$scannedFile->setRevDate(time());
				}

				$this->saveToHistoryFile($scannedFileslist, FIRST_REV_NUMBER, time()); // Save history, revision = FIRST_REV_NUMBER

				// Load versionned files information from history file to instance
				$this->loadVersionnedFilesFromHistory();

				return $this->getVersionnedFiles();

			} else { // History file exist and not empty

				$this->logger->debug("History file '".$this->getHistoryFilePath()."' exist");

				// Load versionned files information from history file to instance
				$this->loadVersionnedFilesFromHistory();

				// Check for changes and update history
				$this->checkAndUpdateChanges($scannedFileslist, $this->getVersionnedFiles());

				return $this->getVersionnedFiles();
			}

		} else { // No files into folder

			$this->logger->error("No files found during the scan of folder ".$this->getVersionnedFolder());

			if(!$this->isHistoryFileExist()) {
				if(!$this->createHistoryFile()) {
					$errorMessage = "Unable to create History file in '".__METHOD__."'";
					$this->logger->error($errorMessage);
					throw new Exception($errorMessage, 301, null);
				}

				// When history file don't exist we set rev number at 1
				$this->saveToHistoryFile(null, FIRST_REV_NUMBER, time());
			}

			// Load versionned files information from history file to instance
			$this->loadVersionnedFilesFromHistory();

			// Check for changes and update history
			$this->checkAndUpdateChanges($scannedFileslist, $this->getVersionnedFiles());

			return $this->getVersionnedFiles();
		}
		return null;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $scannedFileslist
	 * @param unknown_type $versionnedFilesList
	 * @throws Exception
	 */
	public function checkAndUpdateChanges ($scannedFileslist, $versionnedFilesList) {

		$newRevisionNumber = $this->getRevNumber() + 1; // Potentially not used, computed anyway :)
		$newFileDetected = false;
		$modifedFileDetected = false;
		$deletedFileDetected = false;

		if($scannedFileslist != null) {

			/**
			 * First Building array of filenames versionned currently
			 */
			$versionnedFilesPathListString = $this->getVersionnedFilePathAsArrayOfString();

			foreach ($scannedFileslist as $scannedFile) {

				/**
				 * New file come up?
				 */
				if(!in_array($scannedFile->getFilePath(), $versionnedFilesPathListString)) {

					$this->logger->debug("A new file has been detected: ".$scannedFile->getFilePath());
					$newFileDetected = true;

					// Add file to local versionnedFiles array
					$scannedFile->setRevNumber($newRevisionNumber);
					$scannedFile->setRevDate(time());
					array_push($this->versionnedFiles, $scannedFile);
				}

				/**
				 * File already exist, any updates?
				 */
				else {

					$versionnedFile = $this->getVersionnedFileFromFilePath($scannedFile->getFilePath());

					// File already exist, same file?
					if($scannedFile->getFingerPrint() != $versionnedFile->getFingerPrint()) {

						// File change detected
						$this->logger->debug("A file has been modifed: ".$scannedFile->getFilePath());

						$modifedFileDetected = true;

						// Update $this->versionnedFiles
						$scannedFile->setRevNumber($newRevisionNumber);
						$scannedFile->setRevDate(time());
						$this->updateVersionnedFile($scannedFile);

					} else {

						// Same fingerprint with scanned and versionned file
						// So same file...
						// Now verify if versionned file is marked as "deleted".
						// An old file could come Up
						if($versionnedFile->getIsDeleted()) {

							$this->logger->debug("Old file come Up: ".$versionnedFile->getFilePath());

							$modifedFileDetected = true;
							$versionnedFile->setIsDeleted(false);
							$versionnedFile->setRevNumber($newRevisionNumber);
							$versionnedFile->setRevDate(time());
							$this->updateVersionnedFile($versionnedFile);
						}
					}
				}
			}
		}
		/**
		 * File deleted?
		 */

		if($versionnedFilesList != null) {

			/**
			 * First Building array of filenames versionned currently
			 */
			$versionnedFilesPathListString = $this->getVersionnedFilePathAsArrayOfString();

			foreach($versionnedFilesPathListString as $versionnedFileAsString) {

				if(!file_exists($versionnedFileAsString)) {

					$versionnedFileToUpdate = $this->getVersionnedFileFromFilePath($versionnedFileAsString);

					if(!$versionnedFileToUpdate->getIsDeleted()) {

						$deletedFileDetected = true;

						$versionnedFileToUpdate->setIsDeleted(true);
						$versionnedFileToUpdate->setRevNumber($newRevisionNumber);
						$versionnedFileToUpdate->setRevDate(time());

						if(!$this->updateVersionnedFile($versionnedFileToUpdate)) {
							$errorMessage = "Unable to update VersionnedFile in '".__METHOD__."'";
							$this->logger->error($errorMessage);
							throw new Exception($errorMessage, 302, null);
						}
					}
				}
			}
		}

		// Save changes to history
		if($newFileDetected || $modifedFileDetected || $deletedFileDetected) {
			
			$this->saveToHistoryFile($this->versionnedFiles, $newRevisionNumber, time()); // Save history, revision = FIRST_REV_NUMBER

			// Reload all versionned files information from history file to instance
			$this->loadVersionnedFilesFromHistory();
		}
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $filePath
	 */
	public function getVersionnedFileFromFilePath($filePath) {

		foreach ($this->versionnedFiles as $versionnedFile) {
			if ($versionnedFile->getFilePath() == $filePath) {
				return $versionnedFile;
			}
		}
		return null;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $filePath
	 */

	protected function updateVersionnedFile($newVersionnedFile) {

		if ($newVersionnedFile != null) {

			if($newVersionnedFile instanceof VersionnedFile) {

				$index = 0;
				foreach ($this->versionnedFiles as $versionnedFile) {
					if ($versionnedFile->getFilePath() == $newVersionnedFile->getFilePath()) {
						$this->versionnedFiles[$index] = $newVersionnedFile;
						return true;
					}
					$index++;
				}
					
			} else {
				$errorMessage = "Wrong type parameter in '".__METHOD__."' params must be type of VersionnedFile";
				$this->logger->error($errorMessage);
				throw new Exception($errorMessage, 303, null);
			}

		} else {
			$errorMessage = "Wrong type parameter in '".__METHOD__."' params must be not null";
			$this->logger->error($errorMessage);
			throw new Exception($errorMessage, 304, null);
		}

		return false;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $versionnedFilesList
	 */
	protected function getVersionnedFilePathAsArrayOfString () {
		$versionnedFilesListString = Array();

		if($this->versionnedFiles) {
			foreach ($this->versionnedFiles as $file) {
				array_push($versionnedFilesListString, $file->getFilePath());
			}
		}
		return $versionnedFilesListString;
	}

	/**
	 * Up all the file into the versionned folder
	 * @return Array of VersionnedFile objects
	 */
	protected function scanVersionnedFolder () {

		$files = $this->directoryToArray($this->getVersionnedFolder());

		$scannedFileList = Array();

		foreach ($files as $f) {

			$scannedFile = new VersionnedFile();
			$scannedFile->setFilePath($f);
			$fingerprint = hash_file('md5', $f, false);
			$scannedFile->setFingerPrint($fingerprint);
			$scannedFile->setIsVersionned(true);
			$scannedFile->setIsDeleted(false);
			$scannedFile->setLastEditDate(filemtime($f));

			array_push($scannedFileList, $scannedFile);
		}

		return (!empty($scannedFileList)) ? $scannedFileList : null;
	}

	/**
	 * Get an array that represents directory tree
	 * @param string $directory     Directory path
	 * @param bool $recursive         Include sub directories
	 * @param bool $listDirs         Include directories on listing
	 * @param bool $listFiles         Include files on listing
	 * @param regex $exclude         Exclude paths that matches this regex
	 */
	protected function directoryToArray($directory, $recursive = true, $listDirs = false, $listFiles = true, $exclude = '') {
		$arrayItems = array();
		$skipByExclude = false;
		$handle = opendir($directory);
		if ($handle) {
			while (false !== ($file = readdir($handle))) {
				preg_match("/(^(([\.]){1,2})$|(\.(svn|git|md))|(Thumbs\.db|\.DS_STORE))$/iu", $file, $skip);
				if($exclude){
					preg_match($exclude, $file, $skipByExclude);
				}
				if (!$skip && !$skipByExclude) {
					if (is_dir($directory. DIRECTORY_SEPARATOR . $file)) {
						if($recursive) {
							$arrayItems = array_merge($arrayItems, $this->directoryToArray($directory. DIRECTORY_SEPARATOR . $file, $recursive, $listDirs, $listFiles, $exclude));
						}
						if($listDirs){
							$file = $directory . DIRECTORY_SEPARATOR . $file;
							$arrayItems[] = $file;
						}
					} else {
						if($listFiles){
							$file = $directory . DIRECTORY_SEPARATOR . $file;
							$arrayItems[] = $file;
						}
					}
				}
			}
			closedir($handle);
		} else {
			throw new Exception("Unable to open $directory. You may need to check rights of that folder", 305, null);
		}
		return $arrayItems;
	}

	/**
	 *
	 * Enter description here ...
	 * @param VersionnedFile Array $filesList
	 * @param int $revision global revision number
	 */
	public function saveToHistoryFile ($filesList, $revision = FIRST_REV_NUMBER, $revisionDate = null) {

		// Build file list
		$filesListArray = Array();
		if(!empty($filesList)) {
			foreach ($filesList as $file) {
				array_push($filesListArray, $file->getStoreFormatArray());
			}
		}
		
		$historyArray = Array(
				VersionningManager::$REVISION => $revision,
				VersionningManager::$REVISIONDATE => $revisionDate,
				VersionningManager::$REVISIONFILES => $filesListArray);

		if(!file_put_contents($this->getHistoryFilePath(), json_encode($historyArray))) {
			throw new Exception("Unable to save history to <".$this->getHistoryFilePath().">. You may need to check rights of that folder", 307, null);
		}
	}

	/**
	 *
	 * Load versionned files information from history file to instance
	 */
	public function loadVersionnedFilesFromHistory() {
		$this->setVersionnedFiles($this->checkoutFromHistory());
		$this->setRevNumber($this->getRevNumberFromHistory());
	}

	/**
	 * Testing if history file exist
	 * @return true if file exist
	 */
	public function isHistoryFileExist() {
		return file_exists($this->getHistoryFilePath());
	}

	/**
	 *
	 * @return Array of ALL VersionnedFiles (from history file)
	 */
	public function checkoutFromHistory ($filter = null, $returnDeleted = true) {
		return $this->getVersionnedFilesFromHistory($filter, $returnDeleted);
	}

	/**
	 *
	 * @param $revisionNumber
	 * @param $returnDeleted
	 * @return Array of VersionnedFiles from a revision to a latest (from history file) filtered along param
	 */
	public function checkoutFromRevision ($revisionNumber, $filters = null, $returnDeleted = true) {
		if(!is_array($filters)) {
			$filters = Array();
		}
		$filters['fromRevision'] = $revisionNumber;
		return $this->getVersionnedFilesFromHistory($filters, $returnDeleted);
	}

	/**
	 *
	 * @param $revisionNumber
	 * @param $returnDeleted
	 * @return Array of VersionnedFiles from a revision to a latest (from history file) filtered along param
	 */
	public function checkoutWithRevision ($revisionNumber, $returnDeleted = true) {
		$filter = Array('equalsToRevision' => $revisionNumber);
		return $this->getVersionnedFilesFromHistory($filter, $returnDeleted);
	}


	/**
	 * @param array $filter
	 * @return Array of VersionnedFiles (from history file) filtered along param
	 */
	protected function getVersionnedFilesFromHistory($filter = null, $returnDeleted = true) {
		
		$this->logger->info("Getting VersionnedFilesFromHistory with filters: ".json_encode($filter).", return deleted:".$returnDeleted);
		
		$fromRevision = null;
		$equalsToRevision = null;

		$filterWith = null;
		$filterWithout = null;

		if(isset($filter['fromRevision'])) {
			$fromRevision = intval($filter['fromRevision']);
		}

		if(isset($filter['equalsToRevision'])) {
			$equalsToRevision = intval($filter['equalsToRevision']);
		}

		if(isset($filter['filterWithRegex'])) {
			$filterWith = $filter['filterWithRegex'];
		}

		if(isset($filter['filterWithoutRegex'])) {
			$filterWithout = $filter['filterWithoutRegex'];
		}

		if(!file_exists($this->getHistoryFilePath())) {
			throw new Exception($this->getHistoryFilePath()." file do not exist", 306, null);
		}
		
		$historyJsonTextFlow = file_get_contents($this->getHistoryFilePath());
		$historyJsonArray = json_decode($historyJsonTextFlow);
		$versionnedFilelist = Array();
		
		foreach ($historyJsonArray->{VersionningManager::$REVISIONFILES} as $fileInfo) {
			$versionnedFile = new VersionnedFile();
			$versionnedFile->setFilePath(trim($fileInfo->{VersionnedFile::$FILEPATH}));
			$versionnedFile->setFingerPrint(trim($fileInfo->{VersionnedFile::$FINGERPRINT}));
			$versionnedFile->setIsVersionned(intval($fileInfo->{VersionnedFile::$ISVERSIONNED}));
			$versionnedFile->setNote($fileInfo->{VersionnedFile::$NOTE});
			$versionnedFile->setLastEditDate(intval($fileInfo->{VersionnedFile::$LASTEDITDATE}));
			$versionnedFile->setIsDeleted((intval($fileInfo->{VersionnedFile::$ISDELETED}) == 1) ? true : false);
			$versionnedFile->setRevNumber(intval($fileInfo->{VersionnedFile::$REVNUMBER}));
			$versionnedFile->setRevDate(intval($fileInfo->{VersionnedFile::$REVDATE}));
			
			if($versionnedFile->getIsDeleted() && !$returnDeleted) {
				continue;
			}
			
			if($fromRevision != null) {
					
				if($versionnedFile->getRevNumber() > $fromRevision) {
					array_push($versionnedFilelist, $versionnedFile);
				}
			
			} elseif($equalsToRevision != null){
			
				if($versionnedFile->getRevNumber() == $equalsToRevision) {
					array_push($versionnedFilelist, $versionnedFile);
				}
			
			} else {
				array_push($versionnedFilelist, $versionnedFile);
			}
		}
		
		if($filterWith != null || $filterWithout != null) {
		
			$regexFilteredVersionnedFilelist = Array();
		
			foreach ($versionnedFilelist as $file) {
		
				// Push by default files to remove:
				if($file->getIsDeleted()) {
					array_push($regexFilteredVersionnedFilelist, $file);
				}
		
				if($filterWith != null && $filterWithout != null) {
		
					if(preg_match("#".$filterWith."#", $file->getFilePath())
					&& !preg_match("#".$filterWithout."#", $file->getFilePath())) {
						array_push($regexFilteredVersionnedFilelist, $file);
					}
		
				} else if($filterWith != null) {
		
					if(preg_match("#".$filterWith."#", $file->getFilePath())) {
						array_push($regexFilteredVersionnedFilelist, $file);
					}
				} else if ($filterWithout != null) {

					if(!preg_match("#".$filterWithout."#", $file->getFilePath())) {
						array_push($regexFilteredVersionnedFilelist, $file);
					}
				}
			}
		
			$versionnedFilelist = $regexFilteredVersionnedFilelist;
		}
		
		
		return (!empty($versionnedFilelist)) ? $versionnedFilelist : Array();
	}

	public function setRevNumber( $revisionNumber )
	{
		$this->revNumber = $revisionNumber;
	}

	public function setVersionnedFiles( $versionnedFiles )
	{
		$this->versionnedFiles = $versionnedFiles;
	}

	public function getRevNumber()
	{
		if(is_null($this->revNumber)) {
			$this->setRevNumber($this->getRevNumberFromHistory());
		}
		return $this->revNumber;
	}
	
	public function getRevNumberFromHistory()
	{		
		
		if(!file_exists($this->getHistoryFilePath())) return null;
		
		$historyJsonTextFlow = file_get_contents($this->getHistoryFilePath());
		$historyJsonArray = json_decode($historyJsonTextFlow);
		$revNumber = intval($historyJsonArray->{VersionningManager::$REVISION});
		return ($revNumber != 0) ? $revNumber : null;
	}

	public function getRevDateFromHistory()
	{
		$historyJsonTextFlow = file_get_contents($this->getHistoryFilePath());
		$historyJsonArray = json_decode($historyJsonTextFlow);
		$revDate = intval($historyJsonArray->{VersionningManager::$REVISIONDATE});
		return ($revDate != 0) ? $revDate : null;
	}

	public function getVersionnedFiles()
	{
		return $this->versionnedFiles;
	}

	public function setHistoryFilePath( $historyFilePath )
	{
		$this->historyFilePath = $historyFilePath;
	}

	public function setVersionnedFolder( $versionnedFolder )
	{
		$this->versionnedFolder = $versionnedFolder;
	}

	public function getHistoryFilePath()
	{
		return $this->historyFilePath;
	}

	public function getVersionnedFolder()
	{
		return $this->versionnedFolder;
	}

	public function setRevDate( $revDate )
	{
		$this->revDate = $revDate;
	}

	public function getRevDate()
	{
		if(is_null($this->revDate)) {
			$this->setRevDate($this->getRevDateFromHistory());
		}
		return $this->revDate;
	}
	
}