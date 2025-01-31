<?php
require_once("logging.php");


class MyHelper {
    protected $logger;

    /**
     * Init
     *
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    public function __construct() {
        $this->logger = new Logger(get_class($this));
    }

    // Cannot define empty setup() here because args may differ and it is not allowed.

    public function teardown() {
        // Empty method so if not implemented in child class we can still call
    }
}
?>
