<?php
error_reporting(E_ALL);
header('Content-type: application/json');
require_once '../initialization.php';
use Hourglass\Managers\Caller;
print Caller::handle(
		(isset($_GET['q']) ? $_GET['q'] : null), 
		(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null), 
		(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null));
?>