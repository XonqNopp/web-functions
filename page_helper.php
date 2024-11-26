<?php
session_start();  // This must always be the first line executed
session_regenerate_id();

require_once("helper.php");

require_once("body_helper.php");
require_once("cookie_helper.php");
require_once("crypto_helper.php");
require_once("css_helper.php");
require_once("database_helper.php");
require_once("file_helper.php");
require_once("form_helper.php");
require_once("html_helper.php");
require_once("js_helper.php");
require_once("language_helper.php");
require_once("login_helper.php");
require_once("server_helper.php");
require_once("table_helper.php");
require_once("time_helper.php");
require_once("utils_helper.php");


/**
 * PhPage with helpers.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PhPage extends MyHelper {
    // make logger public
    public $logger;

    // Member to store initLocal data for use in pages (outside functions)
    public $miscInit = NULL;

    // helpers
    public $batman;
    public $bobbyTable;
    public $bodyBuilder;
    public $butler;
    public $cssHelper;
    public $dbText;
    public $fileHelper;
    public $formHelper;
    public $htmlHelper;
    public $jsHelper;
    public $loginHelper;
    public $logopedist;
    public $oven;
    public $serverHelper;
    public $timeHelper;
    public $utilsHelper;
    public $waitress;

        public function __construct($rootPath="") {
            parent::__construct();

                // Process init vars before setting up helpers (need to setup crypto helper to process them)
                global $theBatman;
                $this->batman = $theBatman;
                $this->batman->setup($rootPath);
                $initLocal = $this->batman->getInitLocal();

                $this->miscInit = $initLocal->misc;

            // Get log level from GET (needs initLocal first)
            $this->logger->setLogLevelFromGet($initLocal->loggerFromGet);

            $this->setup($rootPath, $initLocal);
        }
    //
        // desctruct
        public function __destruct() {
            $this->tearDown();
        }
    //
        /**
         * Set up
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        private function setup($rootPath, $initLocal) {
            // crypto helper already set up

            // set file name and path
            $scriptName = $_SERVER["SCRIPT_NAME"];
            $filename = basename($scriptName);
            $filePath = dirname($scriptName);

                // Process cookie before setting up helpers
                global $theOven;
                $this->oven = $theOven;
                $this->oven->setup($filePath);

            // helpers (singletons so they can be used inside each other)

                // server helper enables debug on localhost, place it first
                global $theServerHelper;
                $this->serverHelper = $theServerHelper;
                $this->serverHelper->setup();

            // alphabetical from here
                global $theBobbyTable;
                $this->bobbyTable = $theBobbyTable;
                $this->bobbyTable->setup(
                    $initLocal->ddb->server,
                    $initLocal->ddb->username,
                    $initLocal->ddb->password,
                    $initLocal->ddb->DBname,
                    $filename,
                    $filePath
                );
            //
                global $theBodyBuilder;
                $this->bodyBuilder = $theBodyBuilder;
                $this->bodyBuilder->setup($filename);
            //
                global $theButler;
                $this->butler = $theButler;
                $this->butler->setup();
            //
                global $theCssHelper;
                $this->cssHelper = $theCssHelper;
                $this->cssHelper->setup($rootPath, $filename);
            //
                global $theDatabaseText;
                $this->dbText = $theDatabaseText;
                // not a helper, no setup
            //
                global $theFileHelper;
                $this->fileHelper = $theFileHelper;
                $this->fileHelper->setup();
            //
                global $theFormHelper;
                $this->formHelper = $theFormHelper;
                $this->formHelper->setup($filename);
            //
                global $theHtmlHelper;
                $this->htmlHelper = $theHtmlHelper;
                $this->htmlHelper->setup();
            //
                global $theJsHelper;
                $this->jsHelper = $theJsHelper;
                $this->jsHelper->setup($rootPath);
            //
                global $theLoginHelper;
                $this->loginHelper = $theLoginHelper;
                $this->loginHelper->setup($initLocal->sex, $scriptName, $filename);
            //
                global $theLogopedist;
                $this->logopedist = $theLogopedist;
                $this->logopedist->setup($filename, $initLocal->AvailLangs);
            //
                global $theTimeHelper;
                $this->timeHelper = $theTimeHelper;
                $this->timeHelper->setup();
            //
                global $theUtilsHelper;
                $this->utilsHelper = $theUtilsHelper;
                $this->utilsHelper->setup();
            //
                global $theWaitress;
                $this->waitress = $theWaitress;
                $this->waitress->setup();
        }
    //
        /**
         * Tear down.
         *
         * Note: must be public as we inherit from public.
         */
        public function tearDown() {
            // alphabetical from here
            $this->teardownHelper($this->batman);
            $this->teardownHelper($this->bobbyTable);
            $this->teardownHelper($this->bodyBuilder);
            $this->teardownHelper($this->butler);
            $this->teardownHelper($this->cssHelper);
            // $this->teardownHelper($this->dbText);  // not a helper, no teardown
            $this->teardownHelper($this->fileHelper);
            $this->teardownHelper($this->formHelper);
            $this->teardownHelper($this->jsHelper);
            $this->teardownHelper($this->loginHelper);
            $this->teardownHelper($this->logopedist);
            $this->teardownHelper($this->oven);
            $this->teardownHelper($this->serverHelper);
            $this->teardownHelper($this->timeHelper);
            $this->teardownHelper($this->utilsHelper);
            $this->teardownHelper($this->waitress);

            // HTML last
            $this->teardownHelper($this->htmlHelper);
        }
    //
        private function teardownHelper($helper) {
            if($helper === NULL) {
                // It can happen that we abort and some helpers are not setup yet.
                return;
            }

            $helper->teardown();
        }
    //
        // change log level
        public function logLevelUp($level) {
            $this->logger->levelUp($level);
        }
}


// MWE
/* if(main) {
$test = new PhPage();
$test->levelUp(6);
$test->htmlHelper->setTitle("test");
$test->htmlHelper->hotBooty();
echo "<p>It works!</p>\n";
unset($test);
/**/
?>
