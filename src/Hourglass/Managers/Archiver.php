<?php
/**
 *
 * Enter description here ...
 * @author Thomas Champagne
 *
 */
namespace Hourglass\Managers;

use Logger;
use ZipArchive;
use Exception;

class Archiver {

	protected $versionnedFilesList;
	protected $zipDestination;
	protected $overwrite;
	protected $versionnedFolder;
	protected $latestRevisionNumber;
	protected $clientRevisionNumber;
	protected $archiveSimulation;
	protected $logger;

	public static $HASH_KEY = "archiveMd5FingerPrint";
	public static $BINARY_LINK_KEY = "archiveBinaryLink";
	public static $FILECOUNT_KEY = "archiveFilesCount";
	public static $FILESIZE_KEY = "archiveFileSizeBytes";
	public static $FILELIST_KEY = "archiveFileList";
	public static $FROMCACHE_KEY = "archiveFromCache";
	public static $HTTP_LINK = "archiveHttpLink";

	function __construct($zipDestination, $versionnedFolder, $overwrite = true) {
		$this->versionnedFilesList = null;
		$this->zipDestination = $zipDestination;
		$this->overwrite = $overwrite;
		$this->versionnedFolder = $versionnedFolder;
		$this->archiveSimulation = false; // We archive by default
		$this->logger = Logger::getRootLogger();
		$this->logger->debug("Building instance of ".__CLASS__);
	}

	/**
	 *
	 * Enter description here ...
	 * @throws Exception
	 */
	public function zip ($zipName) {

		$zipNameDestination = $this->zipDestination.$zipName;

		// Create zip
		$zipOut = $this->createZip($this->versionnedFilesList, $zipNameDestination, $this->overwrite);

		if($zipOut == -1) {
			// no files to zip, no zip done in tmp folder
			$archive = null;

		} else {
			// Collect information about zip file
			$archiveFilesCount 	= 	$zipOut[Archiver::$FILECOUNT_KEY];
			$archiveFileList 	= 	$zipOut[Archiver::$FILELIST_KEY];
			$archiveFileSize 	= 	$zipOut[Archiver::$FILESIZE_KEY];
			$archiveFromCache 	= 	$zipOut[Archiver::$FROMCACHE_KEY];
			$zipFingerPrint 	= 	$zipOut[Archiver::$HASH_KEY];
			$zipBinaryLink		=	$zipOut[Archiver::$HTTP_LINK];

			$archive = Array(	Archiver::$FILECOUNT_KEY => $archiveFilesCount,
			Archiver::$FILELIST_KEY => $archiveFileList,
			Archiver::$FILESIZE_KEY => $archiveFileSize,
			Archiver::$HASH_KEY => $zipFingerPrint,
			Archiver::$BINARY_LINK_KEY => $zipBinaryLink,
			Archiver::$FROMCACHE_KEY => $archiveFromCache);
		}

		// Cleaning archive directory: delete deprecated zips
		$this->cleanDeprecatedCachedZip();

		return $archive;
	}

	protected function getFingerPrint($zipFile) {
		return hash_file('md5', $zipFile);
	}

	public static function makeHttpBinaryLinkFromOsLink($zipFileOsPath, $requestUri) {

		if(!file_exists($zipFileOsPath)) {
			throw new Exception(__METHOD__.": Unable to reach $zipFileOsPath", 604, null);
		}

		$ipAddr = @$_SERVER['HTTP_HOST'];
		if($ipAddr == null) {
			$ipAddr = gethostbyname(gethostname());
		}
		
        $userDir = "";
		
		if(isset($requestUri)){
			$requestUriArray = explode("/", $requestUri);
			$dirNameArray = explode("/", dirname(APP_TMP));

			$i=0;
			while(end($dirNameArray) != $requestUriArray[$i]){
				$userDir .= $requestUriArray[$i];
				if($requestUriArray[$i] != "")
					$userDir .= "/";
					$i++;
			}
		}

        return ('http'.( (Archiver::isHttpsProtocol()) ? 's': null).'://'.$ipAddr.'/'.$userDir.Archiver::returnArchiveAbsolutePath($zipFileOsPath, dirname(APP_TMP)));
	}

	protected function cleanDeprecatedCachedZip() {
		$dh = opendir(APP_TMP);
		if ($dh) {
			while (($file = readdir($dh)) !== false) {
				if(is_dir($file)) continue;
				$zipNameSplited = explode('_', $file);
				if($zipNameSplited[2] != $this->latestRevisionNumber.'.zip') {
					if(!unlink(APP_TMP.$file)) {
						throw new Exception(__METHOD__.": Unable to clean deprecated zip ".APP_TMP.$file, 612, null);
					}
					$this->logger->info("Deleting deprecated cached zip ".$file);
				}
			}
			closedir($dh);
		}
	}
	
	public static function isHttpsProtocol() {
		
		if(isset($_SERVER['HTTPS'])) {
			
			if ($_SERVER["HTTPS"] == "on") {
				return true;
			}
		}
		return false;
	}

	public function createZip($versionnedFilesList, $destination = null, $overwrite = false) {

		// Before creating a zip. We have to make sure that a cached zip doesn't exist.
		// if a cached zip exist for a given client query. Then we will provide him. If not then we create it.
		// Seek for exist file into /tmp folder
		$archivePatternToFindInCache = str_replace('.zip', '', basename($destination));
		$archiveCachedFileFound = null;
		
		$tmpFiles = scandir(APP_TMP);
		foreach ($tmpFiles as $tmpFile) {

			if(preg_match("#".$archivePatternToFindInCache."#", $tmpFile)) {
				$archiveCachedFileFound = dirname($destination).'/'.$tmpFile;
				break;
			}
		}

		if($archiveCachedFileFound != null) {
				
			$this->logger->info('Archive '.$archiveCachedFileFound.' exist in cache. Now using it for sure...');

			// A cached file exist
			$fileListToReturn = Array();
				
			$zipCountFile = 0;
				
			foreach ($versionnedFilesList as $vf) {
				if(!$vf->getIsDeleted()) {
					// Zip only non deleted files of course
					array_push($fileListToReturn, $this->returnArchiveAbsolutePath($vf->getFilePath(), $this->versionnedFolder));
					$zipCountFile++;
				}
			}
			
			// Search for md5 fingerprint inside filename instead of recomputing it
			$archiveFingerPrint = explode('_', basename($archiveCachedFileFound));
			$archiveFingerPrint = $archiveFingerPrint[0];

			return Array (	file_exists($archiveCachedFileFound),
							Archiver::$FILECOUNT_KEY => $zipCountFile,
							Archiver::$FILELIST_KEY => $fileListToReturn,
							Archiver::$FILESIZE_KEY => filesize($archiveCachedFileFound),
							Archiver::$HASH_KEY => $archiveFingerPrint,
							Archiver::$HTTP_LINK => ($this->getArchiveSimulation()) ? null : $this->makeHttpBinaryLinkFromOsLink($archiveCachedFileFound, @$_SERVER['REQUEST_URI']),
							Archiver::$FROMCACHE_KEY => true);

		}

		if($versionnedFilesList == null) {
			return -1;
		}

		$this->logger->info('Archive not found in cache. Now creating...');
		
		if($this->zipDestination == null) {
			throw new Exception(__METHOD__.": zipDestination cannot be null", 607, null);
		}

		$files = Array();

		foreach ($versionnedFilesList as $vf) {
			if(!$vf->getIsDeleted()) {
				// Zip only non deleted files of course
				array_push($files, $vf->getFilePath());
			}
		}
		$valid_files = array();

		//if files were passed in...
		if(is_array($files)) {
			//cycle through each file
			foreach($files as $file) {

				//$file = str_replace(' ', '\ ', $file);

				//make sure the file exists
				if(file_exists($file)) {
					$valid_files[] = $file;
				} else {
					throw new Exception(__METHOD__.": $file do not exist on file system", 608, null);
				}
			}
		}

		if(count($valid_files)) {
			//create the archive
			$zip = new ZipArchive();

			if($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				throw new Exception(__METHOD__.": Cannot open zipfile $destination. File is not exist or there is problem right", 609, null);
			}

			$zipCountFile = 0;

			$fileListToReturn = Array();

			//add the files
			foreach($valid_files as $file) {

				$fileAbsolutePath = $this->returnArchiveAbsolutePath($file, $this->versionnedFolder);

				if(!$zip->addFile($file, $fileAbsolutePath)) {
					throw new Exception(__METHOD__.": Unable to add file $file into zip $destination", 610, null);
				}


				array_push($fileListToReturn, $fileAbsolutePath);

				$zipCountFile++;
			}

			if(!$zip->close()) {
				throw new Exception(__METHOD__.": Unable to close zip $destination", 611, null);
			}

			// Compute archive finger print
			$archiveFingerPrint = $this->getFingerPrint($destination);
	
			// Rename archive with fingerprint first in filename (will be used later)
			$destinationRenamed = dirname($destination)."/".$archiveFingerPrint."_".basename($destination);
			
			if(!rename($destination, $destinationRenamed)) {
				throw new Exception(__METHOD__.": Unable to rename zip $destination to $destinationRenamed", 612, null);
			}
			
			return Array (	file_exists($destinationRenamed),
							Archiver::$FILECOUNT_KEY => $zipCountFile,
							Archiver::$FILELIST_KEY => $fileListToReturn,
							Archiver::$FILESIZE_KEY => filesize($destinationRenamed),
							Archiver::$HASH_KEY => $archiveFingerPrint,
							Archiver::$HTTP_LINK => ($this->getArchiveSimulation()) ? null : $this->makeHttpBinaryLinkFromOsLink($destinationRenamed, @$_SERVER['REQUEST_URI']),
							Archiver::$FROMCACHE_KEY => false);

		} else {
			return -1;
		}
	}

	public static function returnArchiveAbsolutePath($file, $folderAnchor) {
		return substr(str_replace(dirname($folderAnchor), '', $file), 1);
	}

	public function setArchiveSimulation( $archiveSimulation )
	{
		$this->archiveSimulation = $archiveSimulation;
	}

	public function getArchiveSimulation()
	{
		return $this->archiveSimulation;
	}


	public function setVersionnedFilesList( $versionnedFilesList )
	{
		$this->versionnedFilesList = $versionnedFilesList;
	}

	public function setZipDestination( $zipDestination )
	{
		$this->zipDestination = $zipDestination;
	}

	public function setlatestRevisionNumber( $latestRevisionNumber )
	{
		$this->latestRevisionNumber = $latestRevisionNumber;
	}

	public function setClientRevisionNumber( $clientRevisionNumber )
	{
		$this->clientRevisionNumber = $clientRevisionNumber;
	}
}