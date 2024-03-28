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
require_once("text_helper.php");
require_once("time_helper.php");
require_once("utils_helper.php");


/**
 * PhPage with helpers.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PhPage extends MyHelper {
    // Member to store initLocal data for use in pages (outside functions)
    public $miscInit = NULL;

    // helpers
    public $bodyHelper;
    public $cookieHelper;
    public $cryptoHelper;
    public $cssHelper;
    public $dbHelper;
    public $dbText;
    public $fileHelper;
    public $formHelper;
    public $htmlHelper;
    public $jsHelper;
    public $languageHelper;
    public $loginHelper;
    public $serverHelper;
    public $tableHelper;
    public $textHelper;
    public $timeHelper;
    public $utilsHelper;

    /**
     * construct
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct($rootPath="") {
        parent::__construct();

        // set file name and path
        $filename = basename($_SERVER["SCRIPT_NAME"]);
        $filePath = dirname($_SERVER["SCRIPT_NAME"]);

            // Process init vars before setting up helpers (need to setup crypto helper)
            global $theCryptoHelper;
            $this->cryptoHelper = $theCryptoHelper;
            $this->cryptoHelper->setup($rootPath);
            $initLocal = $this->cryptoHelper->getInitLocal();

            $this->miscInit = $initLocal->misc;

        // Get log level from GET (needs initLocal first)
        $this->logger->setLogLevelFromGet($initLocal->loggerFromGet);

            // Process cookie before settin up helpers
            global $theCookieHelper;
            $this->cookieHelper = $theCookieHelper;
            $this->cookieHelper->setup($filePath);
        //
            // helpers (singletons so they can be used inside each other)
            // server helper enables debug on localhost, place it first
            global $theServerHelper;
            $this->serverHelper = $theServerHelper;
            $this->serverHelper->setup();

            // alphabetical from here
            global $theBodyHelper;
            $this->bodyHelper = $theBodyHelper;
            $this->bodyHelper->setup();

            global $theCssHelper;
            $this->cssHelper = $theCssHelper;
            $this->cssHelper->setup($rootPath);

            global $theDbHelper;
            $this->dbHelper = $theDbHelper;
            $this->dbHelper->setup(
                $initLocal->ddb->server,
                $initLocal->ddb->username,
                $initLocal->ddb->password,
                $initLocal->ddb->DBname,
                $filename,
                $filePath
            );

            global $theDatabaseText;
            $this->dbText = $theDatabaseText;  // not a helper, no setup

            global $theFileHelper;
            $this->fileHelper = $theFileHelper;
            $this->fileHelper->setup();

            global $theFormHelper;
            $this->FormHelper = $theFormHelper;
            $this->FormHelper->setup($filename);

            global $theHtmlHelper;
            $this->htmlHelper = $theHtmlHelper;
            $this->htmlHelper->setup();

            global $theJsHelper;
            $this->jsHelper = $theJsHelper;
            $this->jsHelper->setup($rootPath);

            global $theLanguageHelper;
            $this->languageHelper = $theLanguageHelper;
            $this->languageHelper->setup($filename, $initLocal->AvailLangs);

            global $theLoginHelper;
            $this->loginHelper = $theLoginHelper;
            $this->loginHelper->setup($initLocal->sex, $filename);

            global $theTableHelper;
            $this->tableHelper = $theTableHelper;
            $this->tableHelper->setup();

            global $theTextHelper;
            $this->textHelper = $theTextHelper;
            $this->textHelper->setup();

            global $theTimeHelper;
            $this->timeHelper = $theTimeHelper;
            $this->timeHelper->setup();

            global $theUtilsHelper;
            $this->utilsHelper = $theUtilsHelper;
            $this->utilsHelper->setup();
    }

    // desctruct
    public function __destruct() {
        $this->teardownHelper($this->bodyHelper);
        $this->teardownHelper($this->cookieHelper);
        $this->teardownHelper($this->cryptoHelper);
        $this->teardownHelper($this->cssHelper);
        $this->teardownHelper($this->dbHelper);
        // $this->teardownHelper($this->dbText);  // not a helper, no teardown
        $this->teardownHelper($this->fileHelper);
        $this->teardownHelper($this->formHelper);
        $this->teardownHelper($this->jsHelper);
        $this->teardownHelper($this->languageHelper);
        $this->teardownHelper($this->loginHelper);
        $this->teardownHelper($this->serverHelper);
        $this->teardownHelper($this->tableHelper);
        $this->teardownHelper($this->textHelper);
        $this->teardownHelper($this->timeHelper);
        $this->teardownHelper($this->utilsHelper);

        // HTML last
        $this->teardownHelper($this->htmlHelper);
    }

    private function teardownHelper($helper) {
        if($helper === NULL) {
            // It can happen that we abort and some helpers are not setup yet.
            return;
        }

        $helper->teardown();
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
