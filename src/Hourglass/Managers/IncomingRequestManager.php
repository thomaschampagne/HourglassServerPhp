<?php

namespace Hourglass\Managers;

use Logger;
use Exception;

class IncomingRequestManager {

	/**
	 * 
	 * VersionningManager dependency
	 * @var VersionningManager
	 */
	protected $versionningManager;
	
	/**
	 * 
	 * Archiver dependency
	 * @var Archiver
	 */
	protected $archiver;
	
	/**
	 * 
	 * Enter description here ...
	 * @var Logger 
	 */
	protected $logger;
	
	/**
	 * 
	 * Enter description here ...
	 * @var String
	 */
	protected $requestFingerPrint;

	public static $OUTPUT_LAST_REV = "latestRevision";
	public static $OUTPUT_LAST_REV_DATE = "latestRevisionDate";
	public static $OUTPUT_FILES_TO_DELETE = "filesToDelete";
	public static $OUTPUT_ARCHIVE = "archive";
	public static $OUTPUT_REV_INFO = "revisionInfo";

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $historyFile
	 * @param unknown_type $folderToBeVersionnedPath
	 */
	function __construct($historyFile, $folderToBeVersionnedPath) {

		$this->versionningManager = new VersionningManager($historyFile, $folderToBeVersionnedPath);
		$this->archiver = new Archiver(APP_TMP, $folderToBeVersionnedPath, $this->versionningManager->getRevNumber());

		$this->logger = Logger::getRootLogger();
		$this->logger->debug("Building instance of ".__CLASS__);
	}

	/**
	 *
	 * Enter description here ...
	 * @param boolean $simulateArchive
	 * @param boolean $returnDeleted
	 * @return array  response array
	 */
	public function checkout($simulateArchive = false, $filter = null, $returnDeleted = true) {

		$vfList = $this->versionningManager->checkoutFromHistory($filter, $returnDeleted);

		$latestRevNumber = $this->versionningManager->getRevNumber();

		// Lets archive
		$this->archiver->setVersionnedFilesList($vfList);
		$this->archiver->setArchiveSimulation($simulateArchive);
		$this->archiver->setlatestRevisionNumber($latestRevNumber);
		
		$zipName =  $this->getRequestFingerPrint().'_'.$this->versionningManager->getRevNumber().'.zip';		
		$archiveOutput = $this->archiver->zip($zipName);

		return $this->generateResponse(	$latestRevNumber,
										$this->versionningManager->getRevDate(),
										$this->getFilesToDelete($vfList),
										$archiveOutput);

	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $revision
	 * @param unknown_type $simulateArchive
	 * @throws Exception
	 * @return string
	 */
	public function pullFromRevision ($revision, $filters, $simulateArchive = false, $returnDeleted = true) {

		if(!is_integer($revision) || intval($revision) <= 0) {
			$eMessage = __METHOD__.": Param must be an integer upper that ZERO";
			throw new Exception($eMessage, 208, null);
		}

		$revision = intval($revision);
		$response = null;

		if($revision == $this->versionningManager->getRevNumber()) {

			$this->logger->debug(__METHOD__." client revision equals server revision");

			// You are on latest revision already
			$response = $this->generateResponse(	$this->versionningManager->getRevNumber(),
													$this->versionningManager->getRevDate(),
													null,
													null,
													"You are up to date");

		} else if($revision > $this->versionningManager->getRevNumber()) {

			$this->logger->error(__METHOD__." client revision upper than server revision");
			throw new Exception("You cannot have an upper revision than the server (current revision is '".$this->versionningManager->getRevNumber()."'). You may need to erase all your stuff and call checkout instead", 102, null);

		} else { // revision < lastRevision

			$this->logger->info(__METHOD__." client revision lower than server revision");
			
			$latestRevNumber = $this->versionningManager->getRevNumber();

			// Update request from client
			$vfList = $this->versionningManager->checkoutFromRevision($revision, $filters, $returnDeleted);

			$this->archiver->setVersionnedFilesList($vfList);
			$this->archiver->setArchiveSimulation($simulateArchive);
			$this->archiver->setlatestRevisionNumber($latestRevNumber);
			$this->archiver->setClientRevisionNumber($revision);
			
			$zipName =  $this->getRequestFingerPrint().'_'.$this->versionningManager->getRevNumber().'.zip';
			$archiveOutput = $this->archiver->zip($zipName);

			$response = $this->generateResponse(	$latestRevNumber,
													$this->versionningManager->getRevDate(),
													$this->getFilesToDelete($vfList),
													$archiveOutput);
		}

		return $response;
	}

	/**
	 *
	 * Enter description here ...
	 * @return
	 */
	public function getRevNumber () {
		return $this->versionningManager->getRevNumber();
	}

	/**
	*
	 * Enter description here ...
	 * @return
	 */
	public function getRevNumberDate () {
		return $this->versionningManager->getRevDate();
	}
	
	/**
	 * Enter description here ...
	 * @param unknown_type $countDeletedToo
	 */
	public function countVersionnedFiles() {
		return count($this->versionningManager->checkoutFromHistory(true));
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $countDeletedToo
	 */
	public function countCurrentFiles() {
		return count($this->versionningManager->checkoutFromHistory(null, false));
	}

	public function updateHistory() {
		$this->versionningManager->updateHistoryFile();
	}

	protected function getFilesToDelete($vfList) {
		$filesToDelete = Array();
		foreach ($vfList as $vf) {
			if($vf->getIsDeleted()) {
				array_push($filesToDelete, Archiver::returnArchiveAbsolutePath($vf->getFilePath(), $this->versionningManager->getVersionnedFolder()));
			}
		}
		return array_values(array_unique($filesToDelete));
	}

	protected function generateResponse($currentlatestRevision, $currentlatestRevisionDate, $filesTodelete, $archiveInfo, $revisionInfo = null) {

		$revisionInfo = ($archiveInfo != null) ? $revisionInfo : "No files to give into archive. The last revision may only refers to deleted files :)";

		return Array(		IncomingRequestManager::$OUTPUT_LAST_REV => $currentlatestRevision,
							IncomingRequestManager::$OUTPUT_LAST_REV_DATE => $currentlatestRevisionDate,
							IncomingRequestManager::$OUTPUT_FILES_TO_DELETE => $filesTodelete,
							IncomingRequestManager::$OUTPUT_ARCHIVE => $archiveInfo,
							IncomingRequestManager::$OUTPUT_REV_INFO => $revisionInfo);

	}

	public function setRequestFingerPrint( $requestFingerPrint )
	{
		$this->requestFingerPrint = $requestFingerPrint;
	}

	public function getRequestFingerPrint()
	{
		return $this->requestFingerPrint;
	}

	public function getArchiver()
	{
		return $this->archiver;
	}
		
		
}