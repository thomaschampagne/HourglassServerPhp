<?php
namespace Tests;
use PHPUnit_Framework_TestCase;
use Hourglass\Managers\Archiver;
use ZipArchive;

class TestWs extends PHPUnit_Framework_TestCase
{

	public static $testUrl =  "http://127.0.0.1/hourglass/endpoint/call.php?q=";

	public static $qAllVersionnedFileSimulation = Array (	"method" => "checkout",
															"params" => Array(
																"simulateArchive" => true
															),
															"test"	=> true);

	public static $qAllVersionnedFile = Array (	"method" => "checkout",
												"test"	=> true);


	public static $qVersionnedFilesFromRevisionSimulation = Array (	"method" => "pullFromRevision",
															"params" => Array(
																"revision" => 4,
																"simulateArchive" => true
																),
																"test"	=> true);

	public static $qVersionnedFilesFromRevision = Array (	"method" => "pullFromRevision",
																"params" => Array(
																	"revision" => 4,
																),
															"test"	=> true);

	public static $qRevisionNumber = Array (	"method" => "getRevNumber",
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
	
	
	public static $qVersionnedFilesFromRevisionFilterWithRegex =  Array (	"method" => "pullFromRevision",
																		"params" => Array(
																			"revision" => 4,
																			"filterWithRegex" => 'fr/',
	),
																		"test"	=> true);

	public function setUp() {
		
	}

	public function testAllVersionnedFileSimulation() {

		// Call
		$jsonRequest = json_encode(TestWs::$qAllVersionnedFileSimulation);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);
		$this->assertNull($response['error']);
		$this->assertEquals(9, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertEquals(5, $response['response']['archive'][Archiver::$FILECOUNT_KEY]);
		$this->assertNull($response['response']['archive'][Archiver::$BINARY_LINK_KEY]);
	}

	public function testAllVersionnedFile() {

		// Call
		$jsonRequest = json_encode(TestWs::$qAllVersionnedFile);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);

		$this->assertNull($response['error']);
		$this->assertEquals(9, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertEquals(5, $response['response']['archive'][Archiver::$FILECOUNT_KEY]);
		$this->assertNotNull($response['response']['archive'][Archiver::$BINARY_LINK_KEY]);

		// Test zip file
		$zipLink = $response['response']['archive'][Archiver::$BINARY_LINK_KEY];

		// Retrieve and save to FS. 
		$zipBin = file_get_contents($zipLink);
		$tmpZipPath = APP_TMP.'test.zip';
		file_put_contents ($tmpZipPath , $zipBin);
		
		$this->assertEquals($response['response']['archive'][Archiver::$HASH_KEY], md5_file($tmpZipPath));
		
		$zip = new ZipArchive();
		$zip->open($tmpZipPath);
		$extractPath = APP_TMP.'extract/';
		$zip->extractTo($extractPath);
		$zip->close();

		// Testing archive
		$this->assertTrue(file_exists($extractPath.'content/news/fr/news.csv'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/hollande.jpg'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/montebourg.jpg'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/sarko.jpg'));
		$this->assertFalse(file_exists($extractPath.'content/news/fr/images/grevemedecins.jpg'));

		$this->assertNotEquals(md5_file(DATA_FOLDER_TEST.'/img/obama.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/obama_v2.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/sarko.jpg'), md5_file($extractPath.'content/news/fr/images/sarko.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/news/fr/3/news.csv'), md5_file($extractPath.'content/news/fr/news.csv'));

		// Clean
		$this->rrmdir($extractPath);
		$this->assertFalse(file_exists($extractPath));
		unlink($tmpZipPath); // delete zip tmp file
	}

	public function testVersionnedFilesFromRevisionSimulation() {

		// Call
		$jsonRequest = json_encode(TestWs::$qVersionnedFilesFromRevisionSimulation);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);
		
		$this->assertNull($response['error']);
		$this->assertEquals(9, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertEquals(1, $response['response']['archive'][Archiver::$FILECOUNT_KEY]);
		$this->assertNull($response['response']['archive'][Archiver::$BINARY_LINK_KEY]);
	}

	public function testVersionnedFilesFromRevision() {

		// Call
		$jsonRequest = json_encode(TestWs::$qVersionnedFilesFromRevision);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);

		$this->assertNull($response['error']);
		$this->assertEquals(9, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertEquals(1, $response['response']['archive'][Archiver::$FILECOUNT_KEY]);
		$this->assertNotNull($response['response']['archive'][Archiver::$BINARY_LINK_KEY]);

		// Test zip file
		$zipLink = $response['response']['archive'][Archiver::$BINARY_LINK_KEY];

		// Retrieve and save to FS. 
		$zipBin = file_get_contents($zipLink);
		$tmpZipPath = APP_TMP.'test.zip';
		file_put_contents ($tmpZipPath , $zipBin);

		$zip = new ZipArchive();
		$zip->open($tmpZipPath);
		$extractPath = APP_TMP.'extract/';
		$zip->extractTo($extractPath);
		$zip->close();

		// Testing archive
		$this->assertFalse(file_exists($extractPath.'content/news/fr/news.csv'));
		$this->assertFalse(file_exists($extractPath.'content/news/fr/images/hollande.jpg'));
		$this->assertTrue(file_exists($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertFalse(file_exists($extractPath.'content/news/fr/images/montebourg.jpg'));
		$this->assertFalse(file_exists($extractPath.'content/news/fr/images/sarko.jpg'));
		$this->assertFalse(file_exists($extractPath.'content/news/fr/images/grevemedecins.jpg'));

		$this->assertNotEquals(md5_file(DATA_FOLDER_TEST.'/img/obama.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));
		$this->assertEquals(md5_file(DATA_FOLDER_TEST.'/img/obama_v2.jpg'), md5_file($extractPath.'content/news/fr/images/obama.jpg'));

		// Clean
		$this->rrmdir($extractPath);
		$this->assertFalse(file_exists($extractPath));
		
		unlink($tmpZipPath); // delete zip tmp file
	}

	public function testRevisionNumber () {

		$jsonRequest = json_encode(TestWs::$qRevisionNumber);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);
	}

	public function testFilters () {

		// Adding more files
		copy(DATA_FOLDER_TEST.'/news/en/news_en.csv', FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST.'/news/en/news_en.csv');
		$this->assertTrue(file_exists(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST.'/news/en/news_en.csv'));
		
		copy(DATA_FOLDER_TEST.'/img/unionjack.jpg', FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST.'/news/en/images/unionjack.jpg');
		$this->assertTrue(file_exists(FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST.'/news/en/images/unionjack.jpg'));
		
		// With
		$jsonRequest = json_encode(TestWs::$qAllVersionnedFileSimulationFilterWith);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);
		
		$this->assertNull($response['error']);
		$this->assertEquals(10, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertEquals(5, $response['response']['archive'][Archiver::$FILECOUNT_KEY]);
		
		// Without 
		$jsonRequest = json_encode(TestWs::$qAllVersionnedFileSimulationFilterWithout);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);
		
		$this->assertNull($response['error']);
		$this->assertEquals(10, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertEquals(2, $response['response']['archive'][Archiver::$FILECOUNT_KEY]);
		
		// With + Without
		$jsonRequest = json_encode(TestWs::$qAllVersionnedFileSimulationFilterWithAndWithout);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);
		
		$this->assertNull($response['error']);
		$this->assertEquals(10, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertEquals(1, $response['response']['archive'][Archiver::$FILECOUNT_KEY]);
		$this->assertCount(1, $response['response']['filesToDelete']);
		
		// Wrong Search 
		$jsonRequest = json_encode(TestWs::$qAllVersionnedFileSimulationFilterWithAndWithoutFail);
		$response = $this->httpGet($jsonRequest);
		$response = json_decode($response, true);
		
		$this->assertNull($response['error']);
		$this->assertEquals(10, $response['response']['latestRevision']);
		$this->assertNotNull($response['response']['latestRevisionDate']);
		$this->assertNull($response['response']['archive']);
		$this->assertCount(1, $response['response']['filesToDelete']);
		
		
	}


	public function httpGet($jsonRequest) {
		return file_get_contents(TestWs::$testUrl.$jsonRequest);
	}

	public function saveZipToTmp($data) {
		$tmpZipPath = APP_TMP.'testWs.zip';
		file_put_contents ($tmpZipPath , base64_decode($data));
		return $tmpZipPath;
	}

	public static function generateHelp($popUnitTestMode = true) {

		$queries = Array(	TestWs::$qAllVersionnedFileSimulation,
		TestWs::$qAllVersionnedFile,
		TestWs::$qVersionnedFilesFromRevisionSimulation,
		TestWs::$qVersionnedFilesFromRevision,
		TestWs::$qRevisionNumber,
		TestWs::$qCountCurrentFiles,
		TestWs::$qCountVersionnedFiles,
		TestWs::$qAllVersionnedFileSimulationFilterWith,
		TestWs::$qAllVersionnedFileSimulationFilterWithout,
		TestWs::$qAllVersionnedFileSimulationFilterWithAndWithout,
		TestWs::$qAllVersionnedFileSimulationFilterWithAndWithoutFail,
		TestWs::$qVersionnedFilesFromRevisionFilterWithRegex);

		$help = '';

		foreach ($queries as $q) {

			if(!$popUnitTestMode)
			array_pop($q);

			$help .= json_encode($q)."\n";
		}

		return $help;

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

	public function tearDown() {

	}

	public static function tearDownAfterClass()
	{
		$fh = fopen(ENDPOINT_DIR.'readme.txt', 'w+') or die("cant open file");
		fwrite($fh, TestWs::generateHelp());
		fclose($fh);
	}

}