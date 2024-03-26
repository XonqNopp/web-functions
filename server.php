<?php
require_once("helper.php");


class ServerHelper extends MyHelper {
	private $bLocalhost;

	/**
	 * Setup
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function setup() {
		// check if localhost
		$local = preg_match("/localhost$/", $_SERVER["SERVER_NAME"]);  // match end so we can have multiple localhost
		$lan   = preg_match("/^192\.168\./", $_SERVER["SERVER_NAME"]);
		$this->bLocalhost = ($local || $lan);
		$this->logger->trace("isLocalhost = {$this->isLocalhost}");

		if($this->isLocalhost()) {
			error_reporting(E_ALL);
			ini_set("display_errors", "1");
			ini_set("display_startup_errors", "1");
		}
	}

	public function isLocalhost() {
		return $this->bLocalhost;
	}
}


// singleton
$theServerHelper = new ServerHelper();
?>
