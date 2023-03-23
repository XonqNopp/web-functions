<?php
require("logging.php");


class MyHelper {
	protected $logger;

	public function __construct() {
		global $theLogger;
		$this->logger = $theLogger;
	}

	public function setup() {
		// Empty method so if not implemented in child class we can still call
	}

	public function teardown() {
		// Empty method so if not implemented in child class we can still call
	}
}
?>
