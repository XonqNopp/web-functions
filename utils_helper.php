<?php
require_once("helper.php");


class UtilsHelper extends MyHelper {
    public function setup() {
        // Empty method so we can still call
    }

        public function arraySequential2Associative($seqArray) {
            $assocArray = array();

            foreach($seqArray as $value) {
                $assocArray[$value] = $value;
            }

            return $assocArray;
        }
    //
        // Rest In Pieces, curl
        public function ripCurl($url) {
            $this->logger->trace("ripCurl($url)");

            $ripopt = array(
                CURLOPT_HEADER         => false,
                CURLOPT_NOBODY         => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_FRESH_CONNECT  => true,
                CURLOPT_USERAGENT      => "Mozilla/5.0 (X11)"
            );

            $rip = curl_init($url);
            curl_setopt_array($rip, $ripopt);
            $ripcurl = curl_exec($rip);
            curl_close($rip);
            return $ripcurl;
        }
    //
        // Convert array to table display
        public function array2columns($dateArray, $cols) {
            $this->logger->trace("array2columns(..., $cols)");
            $numDate = count($dateArray);
            if($numDate <= 0) {
                return array();
            }

            $back = array();
            $maxrow = 1;
            $maxcol = $numDate;
            if($cols <= $numDate) {
                $maxcol = $cols;
                $maxrow = floor($numDate / $maxcol) + ($numDate % $maxcol > 0);
            }

            $this->logger->debug("array2columns (maxrow=$maxrow, maxcol=$maxcol)");

            for($theRow = 0; $theRow < $maxrow; $theRow++) {
                $back[] = array();
                for($theCol = 0; $theCol < $maxcol; $theCol++) {
                    $dateIndex = $theCol * $maxrow + $theRow;
                    $back[$theRow][$theCol] = NULL;
                    if($dateIndex < count($dateArray)) {
                        $this->logger->debug("array2columns $dateIndex = ($theRow,$theCol)");
                        $back[$theRow][$theCol] = $dateArray[$dateIndex];
                    }
                }
            }
            return $back;
        }
    //
        // Send mail
        public function sendMail($from, $toEmail, $subject, $message) {
            $this->logger->trace("sendMail($from, $toEmail, $subject, ...)");

            // check if 'from' is valid email
            if(!preg_match("/^.*@.*\..*$/", $from)) {
                $this->logger->fatal("sendMail: from email invalid");
            }

            // check if 'toEmail' is valid email
            if(!preg_match("/^.*@.*\..*$/", $toEmail)) {
                $this->logger->fatal("sendMail: toEmail email invalid");
            }

            // wrap message to 70
            $message = wordwrap($message, 70);

            // send
            mail($toEmail, $subject, $message, "From: $from");
        }
}


// singleton
$theUtilsHelper = new UtilsHelper();
?>
