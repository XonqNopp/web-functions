<?php
require_once("helper.php");


class CryptoHelper extends MyHelper {
    public $sugar = NULL;
    private $initLocal = NULL;
    private $sha = "sha512";

    public function setup($rootPath) {
        $this->loadInitLocal($rootPath);
    }

        // hache passwords
        public function hache($phrase, $compare="") {
            $this->logger->trace("hache(phrase)");

            $hash = hash_hmac($this->sha, $phrase, $this->initLocal->hashSecret);

            if($compare == "") {
                return $hash;
            }

            return hash_equals($hash, $compare);
        }
    //
        /**
         * decrypt
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        public function decrypt($encrypted, $password) {
            $cipher = "aes-256-cbc";

            $ivLength = openssl_cipher_iv_length($cipher);
            $initVector = substr($encrypted, 0, $ivLength);

            $hmac = substr($encrypted, $ivLength, $sha2len=32);

            $rawEncrypted = substr($encrypted, $ivLength + $sha2len);

            $decrypted = openssl_decrypt($rawEncrypted, $cipher, $password, OPENSSL_RAW_DATA, $initVector);

            $calcMac = hash_hmac("sha256", $rawEncrypted, $password, true);

            if($decrypted == "" || !hash_equals($hmac, $calcMac)) {
                // timing attack
                die("Timing attack detected, aborting");
            }

            return $decrypted;
        }
    //
        // read file
        public function readFile($filename) {
            $fileHandle = fopen($filename, "r");
            $content = trim(fread($fileHandle, filesize($filename)));
            fclose($fileHandle);
            return $content;
        }
    //
        // read encrypted file
        public function readEncryptedFile($filename) {
            return base64_decode($this->readFile($filename));
        }
    //
        /**
         * init Local: load encrypted data specific to website
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         * @SuppressWarnings(PHPMD.EvalExpression)
         */
        private function loadInitLocal($rootPath) {
            $this->logger->trace("loadInitLocal()");

                // Decrypt
                $this->logger->trace("loadInitLocal: decrypt");

                // Check if all required files present
                $encryptedFilename = "functions_local/init_local.aes";
                $keyFilename = "yptok";

                if($rootPath != "") {
                    $encryptedFilename = "$rootPath/$encryptedFilename";
                    $keyFilename = "$rootPath/$keyFilename";
                }

                if(!file_exists($encryptedFilename) || !file_exists($keyFilename)) {
                    die("Missing encrypted credentials");
                }

                $password = $this->readFile($keyFilename);
                $encrypted = $this->readEncryptedFile($encryptedFilename);

                $decrypted = $this->decrypt($encrypted, $password);

                $initLocal = NULL;

                eval($decrypted);
                unset($decrypted);

            // Tweak
            $initLocal->sex->sugar = " " . $initLocal->sex->sugar;  // better with space

            $this->initLocal = $initLocal;
            $this->sugar = $initLocal->sex->sugar;
        }
    //
        public function getInitLocal() {
            return $this->initLocal;
        }
}


// singleton
$theBatman = new CryptoHelper();
?>
