<?php
namespace Tests;
use PHPUnit_Framework_TestCase;
use Hourglass\Managers\Caller;

class TestCaller extends PHPUnit_Framework_TestCase {

	public function setUp() {
		
	}

	public function testCallerBasic () {
		$this->assertNotEmpty(Caller::handle('{"method":"checkout","test":true}', "PHPUnit_Framework_TestCase-user-Agent", "127.0.0.1"));
	}

	public function testCallerNoQuery () {
		$this->assertNotEmpty(Caller::handle(null, "PHPUnit_Framework_TestCase-user-Agent", "127.0.0.1"));
	}

	public function tearDown(){
		
	}

}