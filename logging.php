<?php
$kFatal = 0;
$kError = 1;
$kWarning = 2;
$kInfo = 3;
$kConfig = 4;
$kDebug = 5;
$kTrace = 6;


/**
 * Logger with all functionalities.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Logger {
    private $level;

    private $oldLevel;
    private $oldText;
    private $oldBefore;
    private $oldErrors = 0;

    public $htmlOpen;
    public $inBody;

    public $comChar = "#";
    public $blockChar = "*";

    private $isUserAdmin = false;
    private $fromGet = NULL;

        public function __construct() {
            global $kError;

            $this->level = $kError;  // default

            $this->oldLevel = array();
            $this->oldText = array();
            $this->oldBefore = array();

            $this->htmlOpen = false;
            $this->inBody = false;
        }
    //
        // set admin
        public function userIsAdmin($admin) {
            $this->isUserAdmin = $admin;
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
        // push log message to stack
        private function pushToStack($level, $text, $before, $push=NULL) {
            if($push === NULL) {
                $push = true;
            }

            if(!$push) {
                return -1;
            }

            $this->oldLevel[]  = $level;
            $this->oldText[]   = $text;
            $this->oldBefore[] = $before;

            return -1;
        }
    //
        private function echoDiv($text, $class) {
            echo "<div class=\"$class\">$text</div>\n";
        }
    //
        /**
         * log message (all kind)
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        public function log($level, $text, $before="", $push=NULL) {
            global $kFatal;
            global $kError;
            global $kWarning;

            if($level == $kFatal) {
                $this->echoDiv($text, "error");
                exit(1);  // this is a fatal error
            }

            if($before == "") {
                $before = "PHP class " . __CLASS__;
            }

            if($level > $this->level) {
                return $this->pushToStack($level, $text, $before, $push);
            }

            if(!$this->htmlOpen) {
                // HTML not open yet, cannot output HTML comments
                return $this->pushToStack($level, $text, $before, $push);
            }

            if(!$this->inBody && $level <= $kWarning) {
                // htmlOpen: HTML open but not yet in body, must check because cannot output <div> before <body>
                // inBody: if body is open, we can echo whatever
                return $this->pushToStack($level, $text, $before, $push);
            }

            if($level == $kError) {
                $this->echoDiv($text, "error");
                $this->oldErrors++;
                return 1;
            }

            if($level == $kWarning) {
                $this->echoDiv($text, "warning");
                return 1;
            }

            echo $this->htmlCom("$before($level)::$text");
            return 1;  // needed in logStack
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

            if($level === NULL) {
                // Default max log
                $level = $kTrace;
            }

            $this->trace("levelUp($level)");

            $old = $this->level;

            if($level == $old) {
                return $old;
            }

            if($level < $kFatal || $level > $kTrace) {
                return $old;
            }

            if($level >= $kWarning && $this->isUserAdmin) {
                // Enable more PHP debug
                error_reporting(E_ALL);
                ini_set("display_errors", "1");
                ini_set("display_startup_errors", "1");
            }

            $this->level = $level;

            $this->logStack();

            $this->trace("levelUp up! $this->level ->$level");

            return $old;
        }
    //
        // output the log stack
        public function logStack($stackLevel=NULL) {
            global $kFatal;
            if($stackLevel === NULL) {
                $stackLevel = $kFatal;
            }

            $this->trace("logStack($stackLevel) this={$this->level}");

            if($stackLevel < $this->level) {
                $stackLevel = $this->level;
            }

            $this->debug("logStack level=$stackLevel");
            $oldLevel = $this->level;

            if($stackLevel > $oldLevel) {
                $this->level = $stackLevel;
            }

            $this->debug("logStack oldLevel=$oldLevel");

            // Set oldText array to last element
            end($this->oldText);

            $maxLogId = key($this->oldText);
            $this->debug("logStack maxLogId=$maxLogId");

            for($i = 0; $i < $maxLogId; $i++) {
                if(!isset($this->oldLevel[$i])) {
                    continue;
                }

                $level = $this->oldLevel[$i];

                if($level > $stackLevel) {
                    continue;
                }

                if($this->log($level, $this->oldText[$i], $this->oldBefore[$i], false) <= 0) {
                    continue;
                }

                unset($this->oldLevel[$i]);
                unset($this->oldText[$i]);
                unset($this->oldBefore[$i]);
            }

            if($oldLevel != $stackLevel) {
                $this->level = $oldLevel;
            }

            $this->trace("logStack end");
        }
    //
        /**
         * Set log level from GET magic values.
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function setLogLevelFromGet($fromGet=NULL) {
            if($fromGet !== NULL) {
                $this->fromGet = $fromGet;
            }

            if($this->fromGet === NULL) {
                // Not ready to comply
                return;
            }

            global $kFatal;
            global $kTrace;
            for($iLevel = $kTrace; $iLevel > $kFatal; --$iLevel) {
                $key = $this->fromGet->keys[$iLevel];
                $value = $this->fromGet->values[$iLevel];
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

            $back = $this->oldErrors;

            foreach($this->oldLevel as $v) {
                if($v <= $kError) {
                    $back++;
                }
            }

            return $back;
        }
}


// singleton
$theLogger = new Logger();
?>
