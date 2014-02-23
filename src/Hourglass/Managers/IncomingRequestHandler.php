<?php
namespace Hourglass\Managers;

use Hourglass\Managers\IncomingRequestManager;
use Exception;
use Logger;

class IncomingRequestHandler {

	protected $logger;

	/**
	 *
	 * IncomingRequestManager dependencies
	 * @var IncomingRequestManager
	 */
	protected $incomingRequestManager;

	/**
	 * Json Client Request
	 * @var string jsonClientRequest
	 */
	protected $jsonClientRequest;
	
	protected $isTestingRequest = false;
	
	function __construct($jsonRequest = null) {
		$this->logger = Logger::getRootLogger();
		$this->logger->debug("Building instance of ".__CLASS__);
		$this->jsonClientRequest = $jsonRequest;
	}

	protected function dispatchQuery ($jsonRequest) {

		$historyFile = HISTORY_FILE_PATH;
		$folderToBeVersionnedPath = FOLDER_TO_BE_VERSIONNED_FILE_PATH;

		if(isset($jsonRequest['test'])) {
			if($jsonRequest['test']) {
				$historyFile = HISTORY_FILE_PATH_TEST;
				$folderToBeVersionnedPath = FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST;
				$this->isTestingRequest = true;
			}
		}

		$this->incomingRequestManager = new IncomingRequestManager($historyFile, $folderToBeVersionnedPath);
		$this->incomingRequestManager->updateHistory();

		// Now create a request fingerprint.
		// This will be usefull to identify cached zip file
		// which may already exist on system for a given request
		// Fingerprint is based on method name, all params (simulateArchive excluded), test boolean.
		$this->incomingRequestManager->setRequestFingerPrint(IncomingRequestHandler::generateFingerPrint($jsonRequest));

		if(!isset($jsonRequest['method'])) {
			throw new Exception("You must choose a 'method' ", 205, null);
		}

		$r = null;

		switch ($jsonRequest['method']) {

			case 'checkout':
				$r = $this->handleCheckout($jsonRequest);
				break;

			case 'pullFromRevision':
				$r = $this->handlePullFromRevision($jsonRequest);
				break;
					
			case 'countCurrentFiles':
				$r = $this->handleCountCurrentFiles($jsonRequest);
				break;

			case 'countVersionnedFiles':
				$r = $this->handleCountVersionnedFiles($jsonRequest);
				break;
			case 'getRevNumber':
				$r = $this->handleGetRevNumber($jsonRequest);
				break;

			case 'getRevNumberDate':
				$r = $this->handleGetRevNumberDate($jsonRequest);
				break;

			default:
				throw new Exception("The method '".$jsonRequest['method']."' do not exist", 206, null);
				break;

		}

		return $r;
	}

	protected function handleCheckout($jsonRequest) {

		$this->logger->debug(__METHOD__.": Call webservice method: ".$jsonRequest['method']);

		$simulateArchive = false;
		$filters = null;

		if(isset($jsonRequest['params']['simulateArchive'])) {
			if($jsonRequest['params']['simulateArchive']) {
				$simulateArchive = true;
			}
		}

		if(isset($jsonRequest['params']['filterWithRegex'])) {
			if($jsonRequest['params']['filterWithRegex']) {
				$filters['filterWithRegex'] = $jsonRequest['params']['filterWithRegex'];
			}
		}

		if(isset($jsonRequest['params']['filterWithoutRegex'])) {
			if($jsonRequest['params']['filterWithoutRegex']) {
				$filters['filterWithoutRegex'] = $jsonRequest['params']['filterWithoutRegex'];
			}
		}

		return $this->incomingRequestManager->checkout($simulateArchive, $filters);
	}

	protected function handlePullFromRevision($jsonRequest) {

		$this->logger->debug(__METHOD__.": Call webservice method: ".$jsonRequest['method']);

		$simulateArchive = false;
		$filters = null;

		if(isset($jsonRequest['params']['simulateArchive'])) {
			if($jsonRequest['params']['simulateArchive']) {
				$simulateArchive = true;
			}
		}

		if(!isset($jsonRequest['params']['revision']) || empty($jsonRequest['params']['revision'])) {
			throw new Exception("'revision' number param is missing or equals to ZERO. add it (1 minimun) !", 201, null);
		}

		if(!is_int($jsonRequest['params']['revision'])) {
			throw new Exception("'revision' must be integer", 202, null);
		}

		if(isset($jsonRequest['params']['filterWithRegex'])) {
			if($jsonRequest['params']['filterWithRegex']) {
				$filters['filterWithRegex'] = $jsonRequest['params']['filterWithRegex'];
			}
		}

		if(isset($jsonRequest['params']['filterWithoutRegex'])) {
			if($jsonRequest['params']['filterWithoutRegex']) {
				$filters['filterWithoutRegex'] = $jsonRequest['params']['filterWithoutRegex'];
			}
		}

		return $this->incomingRequestManager->pullFromRevision($jsonRequest['params']['revision'], $filters, $simulateArchive);
	}

	protected function handleCountCurrentFiles () {
		return $this->incomingRequestManager->countCurrentFiles();
	}

	protected function handleCountVersionnedFiles () {
		return $this->incomingRequestManager->countVersionnedFiles();
	}

	protected function handleGetRevNumber () {
		return $this->incomingRequestManager->getRevNumber();
	}

	protected function handleGetRevNumberDate () {
		return $this->incomingRequestManager->getRevNumberDate();
	}

	protected function validateQuery($jsonRequest) {

		$this->jsonClientRequest = json_decode($jsonRequest, true);

		if(!$this->jsonClientRequest) {
			throw new Exception("Not parsable json request: ".$jsonRequest, 207, null);
		}
	}
	
	public static function generateFingerPrint($jsonRequest) {
		$revision = (isset($jsonRequest['params']['revision'])) ? $jsonRequest['params']['revision'] : "" ;
		$filterWithRegex = (isset($jsonRequest['params']['filterWithRegex'])) ? $jsonRequest['params']['filterWithRegex'] : "";
		$filterWithoutRegex = (isset($jsonRequest['params']['filterWithoutRegex'])) ? $jsonRequest['params']['filterWithoutRegex'] : "";
		return hash('sha256', (ARCHIVE_ZIPNAME_AUTHENTIFICATION_CODE.$jsonRequest['method'].$revision.$filterWithRegex.$filterWithoutRegex.((isset($jsonRequest['test'])) ? 'withTestRes' : null)));
	}
	
	public function handle() {

		$this->validateQuery($this->jsonClientRequest);

		return $this->dispatchQuery($this->jsonClientRequest);
	}
	
	public function getLogger()
	{
		return $this->logger;
	}
}
