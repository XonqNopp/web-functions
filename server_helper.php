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
        $localAddr = "127.0.0.1";
        $serverNameLocalhost = preg_match("/localhost$/", $_SERVER["SERVER_NAME"]);  // match end so we can have multiple localhost
        $serverNameLocal = preg_match("/\.local$/", $_SERVER["SERVER_NAME"]);  // match end so we can have multiple localhost
        $serverNameLan   = preg_match("/^192\.168\./", $_SERVER["SERVER_NAME"]);
        $serverAddr = $_SERVER["SERVER_ADDR"] == $localAddr;
        $remoteAddr = $_SERVER["REMOTE_ADDR"] == $localAddr;
        $this->isLocalhost = ($serverNameLocalhost || $serverNameLocal || $serverNameLan || $serverAddr || $remoteAddr);
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
