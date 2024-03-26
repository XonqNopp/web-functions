<?php
session_start();  // This must always be the first line executed
session_regenerate_id();

error_reporting(E_ALL);
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");

require_once("helper.php");

require_once("body.php");
require_once("cookie.php");
require_once("crypto.php");
require_once("css.php");
require_once("database.php");
require_once("file.php");
require_once("form.php");
require_once("html.php");
require_once("js.php");
require_once("language.php");
require_once("login.php");
require_once("server.php");
require_once("table.php");
require_once("text.php");
require_once("time.php");
require_once("utils.php");


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
		//$shortName = preg_replace("/\.php$/", "", $filename);

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

			global $theServerHelper;
			$this->serverHelper = $theServerHelper;
			$this->serverHelper->setup();

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
		$this->bodyHelper->teardown();
		$this->cookieHelper->teardown();
		$this->cryptoHelper->teardown();
		$this->cssHelper->teardown();
		$this->dbHelper->teardown();
		$this->dbText->teardown();
		$this->fileHelper->teardown();
		$this->formHelper->teardown();
		$this->jsHelper->teardown();
		$this->languageHelper->teardown();
		$this->loginHelper->teardown();
		$this->serverHelper->teardown();
		$this->tableHelper->teardown();
		$this->textHelper->teardown();
		$this->timeHelper->teardown();
		$this->utilsHelper->teardown();

		// HTML last
		$this->htmlHelper->teardown();
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
