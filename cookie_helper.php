<?php
require_once("helper.php");

require_once("crypto_helper.php");
require_once("server_helper.php");


// login, language
class CookieHelper extends MyHelper {
    private $filePath = NULL;
    private $separator = "%";

    public function setup($filePath) {
        $this->filePath = $filePath;
    }

        // Write
        public function bake($cookie, $value, $expire=NULL) {
            if($cookie === NULL || $cookie == "") {
                return;
            }

            if($expire === NULL) {
                $expire = time() + 3600 * 24 * 300;  // 300 days
            }

            global $theBatman;
            $value = $value . $this->separator . $theBatman->hache($value);

            setcookie($cookie, $value, $expire, "/");
        }
    //
        // Invalidate
        public function burn($cookie) {
            $this->bake($cookie, "", time() - 3600);
        }
    //
        /**
         * Get the content of a cookie.
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function retrieve($cookie) {
            if(!isset($_COOKIE[$cookie])) {
                return NULL;
            }

            $result = $_COOKIE[$cookie];
            list($value, $mac) = explode($this->separator, $result, 2);

            global $theBatman;
            if(!$theBatman->hache($value, $mac)) {
                $this->logger->warning("Corrupted cookie: $value");
                return NULL;
            }

            return $value;
        }
}


// singleton
$theOven = new CookieHelper();
?>
