<?php
require_once("logging.php");


class MyHelper {
    protected $logger;

    public function __construct() {
        global $theLogger;
        $this->logger = $theLogger;
    }

    // Cannot define empty setup() here because args may differ and it is not allowed.

    public function teardown() {
        // Empty method so if not implemented in child class we can still call
    }
}
?>
