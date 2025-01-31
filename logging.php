<?php
$kFatal = 0;
$kError = 1;
$kWarning = 2;
$kInfo = 3;
$kConfig = 4;
$kDebug = 5;
$kTrace = 6;


$logLevel = $kError;  // default

$oldLevel = array();
$oldText = array();
$oldBefore = array();

$oldErrors = 0;

$htmlOpen = false;
$inBody = false;
$isUserAdmin = false;

$fromGet = NULL;


/**
 * Logger with all functionalities.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Logger {
    private $attachedClass;

    public $comChar = "#";
    public $blockChar = "*";

        public function __construct($class=NULL) {
            $this->attachedClass = $class;
        }
    //
        // Open HTML
        public function openHtml() {
            global $htmlOpen;
            $htmlOpen = true;
        }
    //
        // Open body
        public function autopsy() {
            global $inBody;
            $inBody = true;
        }
    //
        // set admin
        public function userIsAdmin($admin) {
            global $isUserAdmin;
            $isUserAdmin = $admin;
        }
    //
        // HTML comment
        public function htmlCom($text) {
            return "<!-- {$this->comChar}{$this->comChar}{$this->comChar} $text {$this->comChar}{$this->comChar}{$this->comChar} -->\n";
        }
    //
        // HTML block comment
        public function htmlBlock($text) {
            $string = "<!-- ";
            $string .= "{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}";
            $string .= "{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}";
            $string .= " $text ";
            $string .= "{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}";
            $string .= "{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}{$this->blockChar}";
            $string .= " -->\n";
            return $string;
        }
    //
        /**
         * push log message to stack
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        private function pushToStack($level, $text, $before, $push=true) {
            if(!$push) {
                return;
            }

            global $oldLevel;
            global $oldText;
            global $oldBefore;
            $oldLevel[]  = $level;
            $oldText[]   = $text;
            $oldBefore[] = $before;
        }
    //
        private function echoDiv($text, $class) {
            echo "<div class=\"$class\">$text</div>\n";
        }
    //
        /**
         * log message (all kind)
         *
         * Args:
         *     level (int): level at which to log the message
         *     text (string): message to log
         *     before (string): text to prepend to message
         *     push (bool): false to not push message to log stack
         *
         * Returns:
         *     int: 1 if message is logged, -1 if pushed to stack
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         */
        public function log($level, $text, $before="", $push=true) {
            global $kFatal;
            global $kError;
            global $kWarning;
            global $logLevel;
            global $htmlOpen;
            global $inBody;

            if($level == $kFatal) {
                $this->echoDiv($text, "error");
                exit(1);  // this is a fatal error
            }

            if($before == "" && $this->attachedClass !== NULL) {
                $before = "PHP class {$this->attachedClass}";
            }

            if(
                ($level > $logLevel)
                || !$htmlOpen  // HTML not open yet, cannot output HTML comments
                || (!$inBody && $level <= $kWarning)
                // htmlOpen: HTML open but not yet in body, must check because cannot output <div> before <body>
                // inBody: if body is open, we can echo whatever
            ) {
                $this->pushToStack($level, $text, $before, $push);
                return -1;
            }

            if($level == $kError) {
                $this->echoDiv($text, "error");
                global $oldErrors;
                $oldErrors++;
                return 1;
            }

            if($level == $kWarning) {
                $this->echoDiv($text, "warning");
                return 1;
            }

            echo $this->htmlCom("$before($level)::$text");
            return 1;
        }
    //
        // fatal (stops script)
        public function fatal($text) {
            global $kFatal;
            $this->log($kFatal, $text);
        }
    //
        // error
        public function error($text) {
            global $kError;
            $this->log($kError, $text);
        }
    //
        // warning
        public function warning($text) {
            global $kWarning;
            $this->log($kWarning, $text);
        }
    //
        // info
        public function info($text) {
            global $kInfo;
            $this->log($kInfo, $text);
        }
    //
        // config
        public function config($text) {
            global $kConfig;
            $this->log($kConfig, $text);
        }
    //
        // debug
        public function debug($text) {
            global $kDebug;
            $this->log($kDebug, $text);
        }
    //
        // trace
        public function trace($text) {
            global $kTrace;
            $this->log($kTrace, $text);
        }
    //
        /**
         * change log level
         */
        public function levelUp($level=NULL) {
            global $kFatal;
            global $kWarning;
            global $kTrace;
            global $logLevel;

            if($level === NULL) {
                // Default max log
                $level = $kTrace;
            }

            $this->trace("levelUp($level)");

            $old = $logLevel;

            if($level == $old) {
                return $old;
            }

            if($level < $kFatal || $level > $kTrace) {
                return $old;
            }

            global $isUserAdmin;
            if($level >= $kWarning && $isUserAdmin) {
                // Enable more PHP debug
                error_reporting(E_ALL);
                ini_set("display_errors", "1");
                ini_set("display_startup_errors", "1");
            }

            $logLevel = $level;

            $this->logStack();

            $this->trace("levelUp up! $logLevel -> $level");

            return $old;
        }
    //
        // output the log stack
        public function logStack($stackLevel=NULL) {
            global $kFatal;
            global $logLevel;

            if($stackLevel === NULL) {
                $stackLevel = $kFatal;
            }

            $this->trace("logStack($stackLevel) logLevel={$logLevel}");

            if($stackLevel < $logLevel) {
                $stackLevel = $logLevel;
            }

            $this->debug("logStack level=$stackLevel");
            $previousLevel = $logLevel;

            if($stackLevel > $previousLevel) {
                $logLevel = $stackLevel;
            }

            $this->debug("logStack previousLevel=$previousLevel");

            // Set oldText array to last element
            global $oldText;
            end($oldText);

            $maxLogId = key($oldText);
            $this->debug("logStack maxLogId=$maxLogId");

            global $oldLevel;
            global $oldBefore;
            for($i = 0; $i < $maxLogId; $i++) {
                if(!isset($oldLevel[$i])) {
                    continue;
                }

                $level = $oldLevel[$i];

                if($level > $stackLevel) {
                    continue;
                }

                if($this->log($level, $oldText[$i], $oldBefore[$i], false) <= 0) {
                    continue;
                }

                unset($oldLevel[$i]);
                unset($oldText[$i]);
                unset($oldBefore[$i]);
            }

            if($previousLevel != $stackLevel) {
                $logLevel = $previousLevel;
            }

            $this->trace("logStack end");
        }
    //
        /**
         * Set log level from GET magic values.
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function setLogLevelFromGet($newFromGet=NULL) {
            global $fromGet;
            if($newFromGet !== NULL) {
                $fromGet = $newFromGet;
            }

            if($fromGet === NULL) {
                // Not ready to comply
                return;
            }

            global $kFatal;
            global $kTrace;
            for($iLevel = $kTrace; $iLevel > $kFatal; --$iLevel) {
                $key = $fromGet->keys[$iLevel];
                $value = $fromGet->values[$iLevel];
                if(isset($_GET[$key]) && $_GET[$key] == $value) {
                    $this->levelUp($iLevel);
                    return;
                }
            }
        }
    //
        // Count real errors
        public function countErrors() {
            global $kError;

            global $oldErrors;
            $back = $oldErrors;

            global $oldLevel;
            foreach($oldLevel as $v) {
                if($v <= $kError) {
                    $back++;
                }
            }

            return $back;
        }
}


// All classes in this repo inheriting from helper have their own instance.
// We provide a singleton to be used by other classes (for instance form_fields.php).
$theLogger = new Logger();
?>
