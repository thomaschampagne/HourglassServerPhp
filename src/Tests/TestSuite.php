<?php
// Report all PHP errors
error_reporting ( - 1 );

// Time zone
date_default_timezone_set ( 'Europe/Paris' );

// Includes
require_once '../../initialization.php';

class TestSuite {

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite ( 'HourglassTestsSuite' );
		$suite->addTestFile('TestVersionningManager.php');
		$suite->addTestFile('TestIncomingRequestManager.php');
		$suite->addTestFile('TestIncomingRequestHandler.php');
		$suite->addTestFile('TestArchiver.php');
		$suite->addTestFile('TestCaller.php');
		$suite->addTestFile('TestWs.php');
		$suite->addTestFile('TestIncomingRequestManagerCachedZipManagementSubPass.php');
		return $suite;
	}
}
