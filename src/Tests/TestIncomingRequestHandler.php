<?php
namespace Tests;

use PHPUnit_Framework_TestCase;
use Hourglass\Managers\IncomingRequestHandler;
use Exception;

/**
 *
 * Enter description here ...
 * @author Thomas Champagne
 *
 */
class TestIncomingRequestHandler extends PHPUnit_Framework_TestCase
{


	public static $qAllVersionnedFileSimulation = Array (	"method" => "checkout",
																"params" => Array(
																	"simulateArchive" => true
	),
																"test"	=> true);

	public static $qVersionnedFilesFromRevisionSimulation = Array (	"method" => "pullFromRevision",
																"params" => Array(
																	"revision" => 4,
																	"simulateArchive" => true
	),
																	"test"	=> true);

	public static $qRevisionNumber = Array (	"method" => "getRevNumber",
													"test"	=> true);

	public static $qRevNumberDate = Array (	"method" => "getRevNumberDate",
													"test"	=> true);
	
	public static $qCountCurrentFiles = Array (	"method" => "countCurrentFiles",
													"test"	=> true);
	
	public static $qCountVersionnedFiles = Array (	"method" => "countVersionnedFiles",
														"test"	=> true);
	
	
	public static $qAllVersionnedFileSimulationFilterWith =  Array (	"method" => "checkout",
																		"params" => Array(
																			"simulateArchive" => true,
																			"filterWithRegex" => 'fr/',
	),
																		"test"	=> true);
	
	public static $qAllVersionnedFileSimulationFilterWithout =  Array (	"method" => "checkout",
																			"params" => Array(
																				"simulateArchive" => true,
																				"filterWithoutRegex" => 'fr/',
	),
																			"test"	=> true);
	
	public static $qAllVersionnedFileSimulationFilterWithAndWithout =  Array (	"method" => "checkout",
																				"params" => Array(
																					"simulateArchive" => true,
																					"filterWithRegex" => 'fr/',
																					"filterWithoutRegex" => '.jpg',
	),
																				"test"	=> true);
	
	public static $qAllVersionnedFileSimulationFilterWithAndWithoutFail =  Array (	"method" => "checkout",
																					"params" => Array(
																						"simulateArchive" => true,
																						"filterWithRegex" => '45/fr/',
																						"filterWithoutRegex" => '.jpg',
	),
																					"test"	=> true);
	
	
	public static $qVersionnedFilesFromRevisionFilterWithAndWithout =  Array (	"method" => "pullFromRevision",
																			"params" => Array(
																				"revision" => 4,
																				"filterWithRegex" => 'fr/',
																				"filterWithoutRegex" => '.jpg',
																			),
																			"test"	=> true);
	
	
	protected $incomingRequestHandler;

	public function setUp() {

	}

	public function tearDown(){
	}

	public function testIncomingRequestHandlerAllVersionnedFileSimulation () {

		$jsonRequest = json_encode($this::$qAllVersionnedFileSimulation);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);

		$r = $this->incomingRequestHandler->handle();

		$this->assertNotNull($r);
		$this->assertEquals(9, $r["latestRevision"]);
		$this->assertEquals(5, $r["archive"]["archiveFilesCount"]);
		
	}

	public function testIncomingRequestHandlerVersionnedFilesFromRevisionSimulation () {
		$jsonRequest = json_encode($this::$qVersionnedFilesFromRevisionSimulation);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
	
		$this->assertNotNull($r);
		$this->assertEquals(9, $r["latestRevision"]);
		$this->assertEquals(1, $r["archive"]["archiveFilesCount"]);
	}

	public function testIncomingRequestHandlerCountCurrentFiles () {
		$jsonRequest = json_encode($this::$qCountCurrentFiles);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		
		$this->assertNotNull($r);
		$this->assertEquals(5, $r);

	}
	public function testIncomingRequestHandlerCountVersionnedFiles () {
		$jsonRequest = json_encode($this::$qCountVersionnedFiles);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull($r);
		$this->assertEquals(6, $r);

	}

	public function testIncomingRequestHandlerGetRevNumber () {
		$jsonRequest = json_encode($this::$qRevisionNumber);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull($r);
		$this->assertEquals(9, $r);
	}
	
	/**
	 * 
	 * Note: Regex queries all full tested from TestWS TestUnit Class 
	 */
	public function testIncomingRequestHandlerRegexQuery () {
		
		$jsonRequest = json_encode($this::$qAllVersionnedFileSimulationFilterWith);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull($r);
		
		$jsonRequest = json_encode($this::$qAllVersionnedFileSimulationFilterWithAndWithout);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull($r);
		
		$jsonRequest = json_encode($this::$qAllVersionnedFileSimulationFilterWithAndWithoutFail);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull($r);
		
		$jsonRequest = json_encode($this::$qAllVersionnedFileSimulationFilterWithout);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull($r);
		
		$jsonRequest = json_encode($this::$qVersionnedFilesFromRevisionFilterWithAndWithout);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull($r);
	}
	
	
	/**
	* @expectedException Exception
    */
	public function testIncomingRequestHandlerNoMethod () {
		
		try {
			$q = $this::$qAllVersionnedFileSimulationFilterWith;
			$q['method'] = null;
			$jsonRequest = json_encode($q);
			$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
			$r = $this->incomingRequestHandler->handle();
			
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(205, $e->getCode());
			throw $e;
		}
	}
	
	
	/**
	* @expectedException Exception
    */
	public function testIncomingRequestHandlerWrongMethod () {
		
		try {
			$q = $this::$qAllVersionnedFileSimulationFilterWith;
			$q['method'] = 'vanishMethod';
			$jsonRequest = json_encode($q);
			$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
			$r = $this->incomingRequestHandler->handle();
			
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(206, $e->getCode());
			throw $e;
		}
	}
	
	
	/**
	* @expectedException Exception
    */
	public function testIncomingRequestHandlerNotParsableJsonQuery () {
		
		try {
			$q = $this::$qAllVersionnedFileSimulationFilterWith;
			$jsonRequest = json_encode($q).'}'; // Fake wrong JSON Query
			$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
			$r = $this->incomingRequestHandler->handle();
			
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(207, $e->getCode());
			throw $e;
		}
	}

	/**
	* @expectedException Exception
    */
	public function testIncomingRequestHandlerVersionnedFilesFromRevisionSimulationWithRevisionNULL() {
		
		try {
			$q = $this::$qVersionnedFilesFromRevisionSimulation;
			$q['params']['revision'] = null;
			$jsonRequest = json_encode($q); 
			$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
			$r = $this->incomingRequestHandler->handle();
			
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(201, $e->getCode());
			throw $e;
		}
	}
	
	/**
	* @expectedException Exception
    */
	public function testIncomingRequestHandlerVersionnedFilesFromRevisionSimulationWithRevisionString() {
		
		try {
			$q = $this::$qVersionnedFilesFromRevisionSimulation;
			$q['params']['revision'] = "String";
			$jsonRequest = json_encode($q); 
			$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
			$r = $this->incomingRequestHandler->handle();
			
		} catch (Exception $e) {
			// Testing Exception
			$this->assertEquals(202, $e->getCode());
			throw $e;
		}
	}
	
	public function testHandleGetRevNumberDate () {
		$q = $this::$qRevNumberDate;
		$jsonRequest = json_encode($q);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$r = $this->incomingRequestHandler->handle();
		$this->assertNotNull(intval($r));

	}
	public function testgetLogger () {
		$q = $this::$qRevNumberDate;
		$jsonRequest = json_encode($q);
		$this->incomingRequestHandler = new IncomingRequestHandler($jsonRequest);
		$this->assertNotNull($this->incomingRequestHandler->getLogger());
	}

	public static function tearDownAfterClass()
	{

	}
}