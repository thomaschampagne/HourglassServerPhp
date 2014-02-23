<?php
namespace Hourglass\Managers;

use Hourglass\Managers\IncomingRequestHandler;
use Exception;

class Caller
{
	public static function handle ($q, $userAgent, $remoteAddr) {

		$response = Array('response' => null, 'error' => null);

		try {

			if(!isset($q)) {
				throw new Exception("No json query found. Please use '?q={your_json_request}'", 208, null);
			}

			$incomingRequestHandler = new IncomingRequestHandler($q);
			$incomingRequestHandler->getLogger()->info("New request from <".$remoteAddr.'>, client query is '.$q);
			
			if(isset($userAgent)) {
				$incomingRequestHandler->getLogger()->info("Client user-agent is <".$userAgent.">");
			}
			
			$response['response'] = $incomingRequestHandler->handle();


		} catch (Exception $e) {
			$response['error'] = Array(	'code' => $e->getCode(),
					'message' => $e->getMessage());

			$incomingRequestHandler = new IncomingRequestHandler();
			$incomingRequestHandler->getLogger()->error($e->getMessage());
		}
		return json_encode($response);
	}
}