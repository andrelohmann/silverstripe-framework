<?php

/**
 * Simple wrapper to allow access to the live site via REST
 */ 
class VersionedRestfulServer extends Controller {
	function handleRequest($request) {
		Versioned::reading_stage('Live');
		$restfulserver = new RestfulServer();
		$response = $restfulserver->handleRequest($request);
		return $response;
	}
}

?>
