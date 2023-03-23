<?php
require_once("helper.php");


class ServerHelper extends MyHelper {
    private $isLocalhost;

    /**
     * Setup
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function setup() {
        if(preg_match("/^www\./", $_SERVER["SERVER_NAME"])) {
            // Remove leading www
            $url = preg_replace("/^www\./", "", $_SERVER["SERVER_NAME"]);
            header("Location: $url");
            exit();
        }

        // check if localhost
        $local = preg_match("/localhost$/", $_SERVER["SERVER_NAME"]);  // match end so we can have multiple localhost
        $lan   = preg_match("/^192\.168\./", $_SERVER["SERVER_NAME"]);
        $this->isLocalhost = ($local || $lan);
        $this->logger->trace("isLocalhost = {$this->isLocalhost()}");

        if($this->isLocalhost()) {
            error_reporting(E_ALL);
            ini_set("display_errors", "1");
            ini_set("display_startup_errors", "1");
        }
    }

    public function isLocalhost() {
        return $this->isLocalhost;
    }
}


// singleton
$theServerHelper = new ServerHelper();
?>
