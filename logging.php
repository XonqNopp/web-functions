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
                echo "<div class=\"error\">$text</div>\n";
                exit(1);// this is a fatal error
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
                // inBody: if body open can echo whatever
                // htmlOpen: HTML open but not yet in body, must check because cannot output <div> before <body>
                return $this->pushToStack($level, $text, $before, $push);
            }

            if($level == $kError) {
                echo "<div class=\"error\">$text</div>\n";
                $this->oldErrors++;
                return 1;
            }

            if($level == $kWarning) {
                echo "<div class=\"warning\">$text</div>\n";
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
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function levelUp($level=NULL, $stack=true) {
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

            if($stack) {
                $this->logStack();
            }

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

            $this->trace("logStack($stackLevel)");

            if($stackLevel <= $this->level) {
                $stackLevel = $this->level;
            }

            $this->debug("logStack level=$stackLevel");
            $oldLevel = $this->level;

            if($stackLevel > $oldLevel) {
                $oldLevel = $this->levelUp($stackLevel, false);
            }

            $this->debug("logStack oldLevel=$oldLevel");

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

            if($oldLevel < $stackLevel) {
                $this->levelUp($oldLevel, false);
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
            $level = $kFatal;

            global $kTrace;
            for($iLevel = $kTrace; $iLevel > $kFatal; --$iLevel) {
                $key = $this->fromGet->keys[$iLevel];
                $value = $this->fromGet->values[$iLevel];
                if(isset($_GET[$key]) && $_GET[$key] == $value) {
                    $level = $iLevel;
                    break;
                }
            }

            $this->levelUp($level);
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
