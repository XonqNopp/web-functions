<?php
require_once("helper.php");

require_once("language_helper.php");


/**
 * Time helper to deal with dates and times.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TimeHelper extends MyHelper {
    private $monthsArray = array();
    private $monthLanguage = "";

    public function setup() {
        // Empty method so we can still call
    }

        /**
         * Init months array: we need to redo it if language changes
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        private function initMonths() {
            if($_SESSION["language"] != $this->monthLanguage) {
                // Language changed, need to redo translation
                $this->monthsArray = array();  // reset
                $this->logger->debug("initMonths: language changed, redo");
            }

            if($this->monthsArray !== array()) {
                // Already done
                return;
            }

            global $theLogopedist;

            $this->monthsArray = array(
                $theLogopedist->translate("January"),
                $theLogopedist->translate("February"),
                $theLogopedist->translate("March"),
                $theLogopedist->translate("April"),
                $theLogopedist->translate("May"),
                $theLogopedist->translate("June"),
                $theLogopedist->translate("July"),
                $theLogopedist->translate("August"),
                $theLogopedist->translate("September"),
                $theLogopedist->translate("October"),
                $theLogopedist->translate("November"),
                $theLogopedist->translate("December"),
            );

            $this->monthLanguage = $_SESSION["language"];
        }
    //
        // minute to hours (int)
        public function minutes2HoursInt($minutes) {
            return intval($minutes / 60);
        }
    //
        // minutes to rest of minutes
        public function minutes2MinutesRest($minutes) {
            return $minutes % 60;
        }
    //
        // display minutes as h:mm
        public function minutesDisplay($minutes) {
            return sprintf("%d:%02d", $this->minutes2HoursInt($minutes), $this->minutes2MinutesRest($minutes));
        }
    //
        /**
         * getNow
         *
         * @SuppressWarnings(PHPMD.MissingImport)
         */
        public function getNow() {
            $this->logger->trace("getNow()");
            $back = new stdClass();
            $now = localtime(time(), true);
            $back->year   = $now["tm_year"] + 1900;
            $back->month  = $now["tm_mon"]  +    1;
            $back->day    = $now["tm_mday"];
            $back->hour   = $now["tm_hour"];
            $back->minute = $now["tm_min"];
            $back->second = $now["tm_sec"];
            return $this->obj2datetime($back);
        }
    //
        /**
         * Get the month(s).
         *
         * If numMonth is an integer between 1 and 12, returns just the requested month as string.
         * Otherwise you get the full 12 months in an array.
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function months($numMonth=0) {
            $this->logger->trace("months($numMonth)");

            $this->initMonths();

            if($numMonth < 1 || $numMonth > 12) {
                return $this->monthsArray;
            }

            return $this->monthsArray[$numMonth - 1];
        }
    //
        /**
         * Convert "YYYY-MM-DD" to object
         *
         * @SuppressWarnings(PHPMD.MissingImport)
         */
        public function str2date($string) {
            $back = new stdClass();
            $back->year  = substr($string, 0, 4) + 0;
            $back->month = substr($string, 5, 2) + 0;
            $back->day   = substr($string, 8, 2) + 0;
            $back->date = $string;
            return $back;
        }
    //
        /**
         * Convert "HH:MM:SS" to object
         *
         * @SuppressWarnings(PHPMD.MissingImport)
         */
        public function str2time($string) {
            // time is HH:MM:SS so count is 8. If we have more, we have date before so we skip it.
            $start = 0;
            if(strlen($string) > 8) {
                $start = 11;
            }

            $back = new stdClass();
            $back->hour   = substr($string, $start+0, 2) + 0;
            $back->minute = substr($string, $start+3, 2) + 0;
            $back->second = NULL;
            if(strlen($string) == 8) {
                $back->second = substr($string, $start+6, 2) + 0;
            }
            $back->time = $string;
            return $back;
        }
    //
        public function str2datetime($string) {
            $date = $this->str2date($string);
            $time = $this->str2time($string);

            $date->hour = $time->hour;
            $date->minute = $time->minute;
            $date->second = $time->second;
            $date->time = $time->time;

            $date->timestamp = "{$date->date} {$date->time}";

            return $date;
        }
    //
        public function obj2date($obj) {
            $obj->date = sprintf("%04d", $obj->year) . "-" . sprintf("%02d", $obj->month) . "-" . sprintf("%02d", $obj->day);
            return $obj;
        }
    //
        public function obj2time($obj) {
            $format = "%02d";
            $obj->time = sprintf($format, $obj->hour) . ":" . sprintf($format, $obj->minute);
            if($obj->second !== NULL) {
                $obj->time .= ":" . sprintf($format, $obj->second);
            }
            return $obj;
        }
    //
        public function obj2datetime($obj) {
            $date = $this->obj2date($obj);
            $time = $this->obj2time($obj);

            $date->hour = $time->hour;
            $date->minute = $time->minute;
            $date->second = $time->second;
            $date->time = $time->time;

            $date->timestamp = "{$date->date} {$date->time}";

            return $date;
        }
    //
        public function obj2timeMinutes($obj) {
            $obj->timeMinutes = $obj->hour * 60 + $obj->minute + $obj->second / 60;
            return $obj;
        }
}


// singleton
$theTimeHelper = new TimeHelper();
?>
