<?php
/*** Created: Wed 2013-08-07 14:32:04 CEST
 *
 * log level 0:fatal, 1:error, 2:warn, 3:info, 4:config, 5:debug, 6:trace
 *
 * TODO:
 *
 *** v1.3 ***
 * split into several files
 F ln_3: make varargin and add option for changing com char to mark visually big log blocks (formfield...)
 F FormField:
     +autocorrect=off
	 +autocapitalize=words
	 +option for text after field
	 +option for checkbox before field with disabled field (js)
	 check if enablesubmit is ok
	 date+time add all args (js)
 F SQLenum2field etc
 F change random entry to SELECT ORDER BY RAND LIMIT 1
 F searchbox.php?
 F IE warning
 b Not sure if marquee done right (not used in nidji)
 F onchange triggers onbeforeunload
 b improve 'id' in subbutt
 b js change in websites
 F add <html manifest="/off.appcache"> option for offline web app
 F QueryInsert and QueryUpdate not tested
 F change mysqli to PDO
 b HTMLblock single arg
 b git st not working online
 *
 *
 *** v1.4 ***
 *
 *
 *** v2.0 ***
 F get language first time from UA
 F consider magic keywords and magic methods:
 * http://us3.php.net/manual/en/language.constants.predefined.php
 * http://php.net/manual/en/language.oop5.magic.php
 F cardinalize: make multi-lingual
 F rss/atom : see pmoret.ch/photoblog/index.php
 *   Check if possible to make atom (my ex-RSS) simply in the class:
 *   http://www.tutorialspoint.com/rss/what-is-atom.htm
 *   http://www.scriptol.com/rss/comparison-atom-rss-tags.php
 F ggMaps API (see w3s)
 F check all that can be set only once to be vars set in construct with get methods
 *
 *
 */
session_start();
session_regenerate_id();

//error_reporting(E_ALL);
//ini_set("display_errors", "1");
//ini_set("display_startup_errors", "1");

class PhPage {
	//// Class PhPage: handle the whole stuff about pages in PHP
		//// Attributes:
		private $rootPath  = "";// set in construct
		private $FileName  = "";// set in construct
		private $FilePath  = "";// set in construct
		private $ShortName = "";// set in construct
		//// debug
		private $debug        = false;
		private $LogLevel     = 1;// display fatal+error
		private $OldLogText   = array();
		private $OldLogBefore = array();
		private $OldLogLevel  = array();
		private $OldErrors    = 0;
		//// DB
		private $mysqli = null;
		public $ddb    = null;// initLocal
		//// HTML header
		private $open        = false;// will say if HTML is open
		private $headless    = false;// will say if body is open
		private $meta        = array();
		private $favicon_pic = "/pictures/favicon.png";
		private $iOS_pic     = "";
		private $iOS_startup = "";
		private $title       = "";
		private $bodyguards  = "";
		private $validHTML   = true;
		private $validCSS    = true;
		//// values for sessions
		private $sex = null;// initLocal
		public $miscInit = null;  // initLocal
		//// languages
		private $longEnglish = "english";
		private $longFrench = "francais";
		private $longGerman = "deutsch";
		private $longItalian = "italiano";
		private $longWolof = "wolof";
		private $longMandinka = "mandinka";
		private $longUndef = "LANGUAGE_NOT_DEFINED";
		public $long = array();  // define in init
		//// special arrays: key is script filename, value is path (if any)
		private $CSS = array(
			"basejump"      => "functions", //     common CSS for all my websites
			"bungeejumping" => "functions", // responsive CSS for all my websites
			"wingsuit"      => "",          //     common CSS at root of single website
			"bridge"        => "",          // responsive CSS at root of single website
			"parachute"     => "",          //     common CSS only for files in same dir
			"rope"          => ""           // responsive CSS only for files in same dir
		);
		private $js  = array();
		private $more_js = "";
		//// specials:
		public $LaTeX = "<span class=\"latex\">L<sup>a</sup>T<sub>e</sub>X</span>";
	//
		/*** Methods ***/
			/*** construct
			 * If atom compatible, one arg to tell atom/html
			 */
			//
			public function __construct($rootPath="") {
				//// init
				$this->rootPath = $rootPath;
					// Init all known anguages
					$this->long = array(
						"english" => $this->longEnglish,
						"french" => $this->longFrench,
						"german" => $this->longGerman,
						"italian" => $this->longItalian,
						"wolof" => $this->longWolof,
						"mandinka" => $this->longMandinka,
						// undef:
						"undef" => $this->longUndef);
				//
					//// Process init vars
					$this->initLocal();
				//
					/// language (cookie before writing output)
					$this->LanguageCookieGet();
				//
					//// debug
					$this->GET2log();
				//
					//// Store parameters to session
					$this->Store2Session();
				//
					//// set file name and path
					$this->SetFileName();
					$this->SetFilePath();
				//
					//// sessions
					if(!isset($_SESSION[$this->sex->session]) || $_SESSION[$this->sex->session] == "") {
						$_SESSION[$this->sex->session] = $this->sex->GuestValue;
					}
				//
			}
		//
			//// init Local
			public function initLocal() {
				$this->ln_3(6, "initLocal()");

					// Decrypt
					$this->ln_3(6, "initLocal: decrypt");
					// Check if all required files present
					$encryptedFilename = "functions_local/initLocal.aes";
					$keyFile = "yptok";
					if($this->rootPath != "") {
						$encryptedFilename = $this->rootPath . "/$encryptedFilename";
						$keyFile = $this->rootPath . "/$keyFile";
					}
					if(!file_exists($encryptedFilename) || !file_exists($keyFile)) {
						echo("Missing encrypted credentials");
						exit(1);
					}

					$initLocal = null;
					$cipher = "aes-256-cbc";

					$k = fopen($keyFile, "r");
					$password = trim(fread($k, filesize($keyFile)));
					fclose($k);

					$il = fopen($encryptedFilename, "r");
					$encrypted64 = trim(fread($il, filesize($encryptedFilename)));
					fclose($il);
					$encrypted = base64_decode($encrypted64);

					$ivLength = openssl_cipher_iv_length($cipher);
					$iv = substr($encrypted, 0, $ivLength);

					$hmac = substr($encrypted, $ivLength, $sha2len=32);

					$rawEncrypted = substr($encrypted, $ivLength + $sha2len);

					$decrypted = openssl_decrypt($rawEncrypted, $cipher, $password, $options=OPENSSL_RAW_DATA, $iv);

					$calcMac = hash_hmac("sha256", $rawEncrypted, $password, $as_binary=true);

					if($decrypted == "" || !hash_equals($hmac, $calcMac)) {
						/// timing attack
						echo("Timing attack detected, aborting");
						exit(1);
					}
					eval($decrypted);
					unset($decrypted);
				//
					// Store
					$this->ln_3(6, "initLocal: store");
					// Get data in here
					$this->ddb        = $initLocal->ddb;
					$this->sex        = $initLocal->sex;
					$this->sex->sugar = " " . $this->sex->sugar;// better with space
					$this->miscInit = $initLocal->misc;
					$avail = $initLocal->AvailLangs;
					if($avail !== array() && is_array($avail)) {
						$availStr = "";
						foreach($avail as $l) {
							$availStr .= " $l";
						}
						$this->ln_3(6, "initLocal setting AvailLangs:$availStr");
						$this->setAvailLangs($avail);
						if(!isset($_SESSION["language"])) {
							$this->ln_3(6, "initLocal language not defined, making default: $avail[0]");
							$_SESSION["language"] = $avail[0];
						}
					}
				$this->ln_3(6, "initLocal done");
			}
		//
			/*** RSS stuff not done ***/
			public function RSS() {
				exit;
				$back = "";
				$back .= "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
				$back .= "<rss version=\"2.0\">\n";
				$back .= "<channel>\n";
				$back .= "<title>$title</title>\n";
				$back .= "<link>$url</link>\n";
				$back .= "<description>$description</description>\n";
				if( $copyright != "" ) {
					$back .= "<copyright>$copyright</copyright>\n";
				}
				$back = "</channel>\n";
				$back .= "</rss>\n";
				return $back;
			}
		//
			/*** desctruct ***/
			public function __destruct() {
				$back = "";
				$back .= $this->MakeFoot();
				$back .= $this->DeadBody();
				echo $back;
				$this->FinishHim();
			}
		//
			/*** Headers STUFF ***/
				/*** Init HTML: open doctype and html ***/
				public function initHTML($manifest = false) {
					$this->ln_3(6, "initHTML()");
					if(!$this->open) {
						$foetus = "";
						$foetus .= $this->doctypetag();
						$foetus .= $this->htmltag($manifest);
						$foetus .= "<!-- Hey, why do you check the source code? ;-) -->\n";
						echo $foetus;
						$this->open = true;
						$this->LogStack();// now that open==true
						$this->ln_3(6, "initHTML() end");
					} else {
						$this->ln_3(3, "initHTML(): already opened HTML");
					}
				}
			//
				/*** Doctype ***/
				public function doctypetag() {
					$this->ln_3(6, "doctypetag()");
					$back = "<!doctype html>\n";
					return $back;
				}
			//
				/*** HTML tag ***/
				public function htmltag($manifest = false) {
					$back = "<html";
					if($manifest) {
 						$back .= "manifest=\"/off.appcache\"";
					}
					$back .= ">\n";
					$this->ln_3(6, "htmltag()");
					return $back;
				}
			//
				/*** charset ***/
				public function MandatoryMeta() {
					$this->ln_3(6, "MandatoryMeta()");
					$back = "<meta charset=\"utf-8\" />\n";
					$back .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />\n";
					return $back;
				}
			//
				//// meta STUFF
					//// Get 'em all
					public function METAR() {
						$this->ln_3(6, "METAR()");
						$back = "";
						foreach($this->meta as $name => $content) {
							//if($name == "language" && $content == "") {
								//$content = $_SESSION["language"];
							//}
							if($content != "") {
								$back .= "<meta name=\"$name\" content=\"$content\" />\n";
							}
						}
						return $back;
					}
				//
					//// Add one
					public function metaphysics($name, $content) {
						$this->ln_3(6, "metaphysics($name, $content)");
						$this->meta[$name] = $content;
					}
				//
					//// special lines
					public function weird_meta() {
						$this->ln_3(6, "weird_meta()");
						$this->metaphysics("editor", "VIm");
						$this->metaphysics("maniacal laughter", "bwuah hahahahahahahah");
						//// found on procmail.org
					}
				//
					/*** set keywords ***/
					public function SetKeywords($keywords) {
						$this->ln_3(6, "SetKeywords(...)");
						$this->metaphysics("keywords", $keywords);
					}
				//
					/*** set description ***/
					public function SetDescription($description) {
						$this->ln_3(6, "SetDescription(...)");
						$this->metaphysics("description", $description);
					}
				//
				//
			//
				/*** favicon ***/
					/*** set favicon pic ***/
					public function SetFavicon($pic) {
						$this->ln_3(6, "SetFavicon($pic)");
						$this->favicon_pic = $pic;
					}
				//
					//// set iOS home screen icon (min 57x57)
					public function Set_iOSpic($pic) {
						$this->ln_3(6, "Set_iOSpic($pic)");
						$this->iOS_pic = $pic;
					}
				//
					//// set iOS startup pic
					public function Set_iOSstart($pic) {
						$this->ln_3(6, "Set_iOSstart($pic)");
						$this->iOS_startup = $pic;
					}
				//
					/*** output ***/
					public function favicon() {
						$this->ln_3(6, "favicon()");
						$back = "";
						if($this->favicon_pic != "") {
							$this->ln_3(6, "favicon pic not empty");
							$favicon_pic = $this->favicon_pic;
							$favicon_ext = $this->GetExt($favicon_pic, false);
							$back .= "<link rel=\"icon\" type=\"image/$favicon_ext\" href=\"$favicon_pic\" />\n";
						}
						if($this->iOS_pic != "") {
							$back .= "<link rel=\"apple-touch-icon\" href=\"{$this->iOS_pic}\" />\n";
						} elseif($this->favicon_pic != "") {
							$back .= "<link rel=\"apple-touch-icon\" href=\"{$this->favicon_pic}\" />\n";
						}
						if($this->iOS_startup != "") {
							$back .= "<link rel=\"apple-touch-startup-image\" href=\"{$this->iOS_startup}\" />\n";
						} elseif($this->iOS_pic != "") {
							$back .= "<link rel=\"apple-touch-startup-image\" href=\"{$this->iOS_pic}\" />\n";
						} elseif($this->favicon_pic != "") {
							$back .= "<link rel=\"apple-touch-startup-image\" href=\"{$this->favicon_pic}\" />\n";
						}
						return $back;
					}
				//
				//
			//
				/*** CSS STUFF ***/
					/*** clear CSS array ***/
					public function CSS_Clear() {
						$this->ln_3(6, "CSS_Clear()");
						$this->CSS = array();
					}
				//
					/*** push stylesheet ***/
					public function CSS_Push($stylesheet, $path = "") {
						$this->ln_3(6, "CSS_Push($stylesheet)");
						if(array_key_exists($stylesheet, $this->CSS)) {
							$this->ln_3(1, "CSS error: stylesheet $stylesheet already in array");
							return false;
						}
						$this->CSS[$stylesheet] = $path;
					}
				//
					/*** Change path of stylesheet ***/
					public function CSS_Path($stylesheet, stdClass $varargin = NULL) {
						/*** varargin ***/
						$newpath = "";
						$notempty = false;
						if($varargin !== NULL) {
							foreach($varargin as $k => $v) {$$k = $v;}
						}
						/*** /varargin ***/
						$this->ln_3(6, "CSS_Path($stylesheet, '$newpath', " . (int) $notempty . ")");
						if(!array_key_exists($stylesheet, $this->CSS)) {
							$this->ln_3(1, "CSS error: stylesheet not found in array");
							return false;
						}
						$oldpath = $this->CSS[$stylesheet];
						if($oldpath == "" && ($notempty || $newpath != "")) {
							$oldpath = ".";
						}
						if($newpath != "") {
							$this->ln_3(5, "CSS_Path stylesheet $stylesheet replacing oldpath=$oldpath by newpath=$newpath");
							$this->CSS[$stylesheet] = $newpath;
						}
						$this->ln_3(6, "CSS_Path end");
						return $this->CSS[$stylesheet];
					}
				//
					/*** Add prefix to path of stylesheet ***/
					public function CSS_PrePath($stylesheet, $prefix) {
						$this->ln_3(6, "CSS_PrePath($stylesheet, $prefix)");
						$csspath = $this->CSS_Path($stylesheet);
						$this->ln_3(5, "CSS_PrePath path was $csspath");
						if($csspath == "") {
							$csspath = $prefix;
						} else {
							$csspath = "$prefix/$csspath";
						}
						$args = new stdClass();
						$args->newpath = $csspath;
						$this->ln_3(5, "CSS_PrePath setting $csspath");
						$this->CSS_Path($stylesheet, $args);
						$this->ln_3(6, "CSS_PrePath end");
					}
				//
					/*** Change path to x dir further ***/
					public function CSS_ppPath($stylesheet, $n = 1) {
						$this->ln_3(6, "CSS_ppPath($stylesheet, $n)");
						for($i = 1; $i <= $n; $i++) {
							$this->CSS_PrePath($stylesheet, "..");
						}
					}
				//
					/*** Change path to x dir further for basejump ***/
					public function CSS_ppJump($n = 1) {
						$this->ln_3(6, "CSS_ppJump($n)");
						$this->CSS_ppPath("basejump", $n);
						$this->CSS_ppPath("bungeejumping", $n);
					}
				//
					/*** Change path to x dir further for wingsuit ***/
					public function CSS_ppWing($n = 1) {
						$this->ln_3(6, "CSS_ppWing($n)");
						$this->CSS_ppPath("wingsuit", $n);
						$this->CSS_ppPath("bridge", $n);
					}
				//
					/*** CSS line ***/
					public function CSS_lines() {
						$this->ln_3(6, "CSS_lines()");
						//// Add own CSS to stack
						$this->CSS_Push($this->ShortName);
						$back = "";
						foreach($this->CSS as $stylesheet => $path) {
							$stylesheet = "$stylesheet.css";
							if($path != "") {
								$stylesheet = "$path/$stylesheet";
							}
							$this->ln_3(5, "CSS_lines stylesheet=$stylesheet");
							if(file_exists("$stylesheet")) {
								$back .= "<link rel=\"stylesheet\" href=\"$stylesheet\" />\n";
							} else {
								$this->ln_3(5, "CSS_lines stylesheet not found");
							}
						}
						return $back;
					}
				//
				//
			//
				/*** javascripts STUFF ***/
					/*** push script ***/
					public function js_Push($script, $path = "") {
						$this->ln_3(6, "js_Push($script, path='$path')");
						if(array_key_exists($script, $this->js)) {
							$this->ln_3(1, "js error: script $script already in array");
							return false;
						}
						$this->js[$script] = $path;
					}
				//
					/*** Change path of js ***/
					public function js_Path($script, stdClass $varargin = NULL) {
						/*** varargin ***/
						$newpath = "";
						$notempty = false;
						if($varargin !== NULL) {
							foreach($varargin as $k => $v) {$$k = $v;}
						}
						/*** /varargin ***/
						$this->ln_3(6, "js_Path($script, $newpath, " . (int) $notempty . ")");
						if(!array_key_exists($script, $this->js)) {
							$this->ln_3(1, "js error: script not found in array");
							return false;
						}
						$oldpath = $this->js[$script];
						if($oldpath == "" && ($notempty || $newpath != "")) {
							$oldpath = ".";
						}
						if($newpath != "") {
							$this->ln_3(5, "js_Path script $script replacing oldpath=$oldpath by newpath=$newpath");
							$this->js[$script] = $newpath;
						}
						$this->ln_3(6, "js_Path end");
						return $this->js[$script];
					}
				//
					/*** javascript line ***/
					public function js_lines() {
						$this->ln_3(6, "js_lines()");
						$back = "";
						foreach($this->js as $script => $path) {
							$script = "js$script.js";
							if($path != "") {
								$script = "$path/$script";
							}
							$this->ln_3(5, "js_lines script=$script");
							if(file_exists("$script")) {
								$back .= "<script src=\"$script\"></script>\n";
							} else {
								$this->ln_3(5, "js_lines script not found");
							}
						}
						return $back;
					}
				//
					/*** enablesubmit ***/
					public function js_EnableSubmit() {
						$this->ln_3(6, "js_EnableSubmit()");
						$this->js_Push("enablesubmit", $this->TruthWayLife());
					}
				//
					/*** confirmerase ***/
					public function js_ConfirmErase() {
						$this->ln_3(6, "js_ConfirmErase()");
						$this->js_Push("confirmerase", $this->TruthWayLife());
					}
				//
					//// forms
					public function js_Form($unload = true) {
						$this->ln_3(6, "js_Form()");
						//$this->js_EnableSubmit();
						//$this->js_ConfirmErase();
						$this->js_Push("4forms", $this->TruthWayLife());
						if($unload) {
							$this->BeforeUnload();
						}
					}
				//
					//// BeforeUnload
					public function BeforeUnload() {
						$this->ln_3(6, "BeforeUnload()");
						$this->SetBodyGuards("onbeforeunload=\"return ConfirmCancel()\"");
					}
				//
					//// add a js script manually
					public function js_AddScript($script) {
						$this->more_js .= $script;
					}
				//
				//
			//
				/*** title STUFF ***/
					/*** set title ***/
					public function SetTitle($title, stdClass $varargin = NULL) {
						$this->ln_3(6, "SetTitle($title)");
						/*** varargin ***/
						$class = "";
						$id = "";
						if($varargin !== NULL) {
							foreach($varargin as $k => $v) {$$k = $v;}
						}
						/*** /varargin ***/
						$this->title = $title;
						$h1 = "";
						if($id != "") {
							$h1 = "id=\"$id\"";
						}
						if($class != "") {
							if($h1 != "") {
								$h1 .= " ";
							}
							$h1 .= "class=\"$class\"";
						}
						if($h1 != "") {
							$h1 = " $h1";
						}
						return "<h1$h1>$title</h1>\n";
					}
				//
					/*** title line ***/
					public function titleline() {
						$this->ln_3(6, "titleline()");
						$back = "";
						$display_title = "XonqNopp";
						if($this->title != "") {
							$display_title = $this->title;
						}
						$back .= "<title>$display_title</title>\n";
						return $back;
					}
				//
				//
			//
				/*** set bodyguards ***/
				public function SetBodyguards($args) {
					$this->ln_3(6, "SetBodyguards($args)");
					if($this->headless) {
						$this->ln_3(1, "Cannot set bodyguards, already out");
					}
					$this->bodyguards .= " $args";
				}
			//
				/*** make head ***/
				public function MakeHead() {
					$this->ln_3(6, "MakeHead()");
					$back = "";
					$this->ln_3(6, "MakeHead back empty");
					if(!$this->headless) {
						$back .= $this->HTMLblock("MakeHead start");
						$back .= "<head>\n";
						$this->ln_3(6, "MakeHead head OK");
						$back .= $this->MandatoryMeta();
						$this->ln_3(6, "MakeHead MandatoryMeta OK");
						$back .= $this->METAR();
						$this->ln_3(6, "MakeHead meta lines OK");
						$back .= $this->favicon();
						$this->ln_3(6, "MakeHead favicon OK");
						$back .= $this->CSS_lines();
						$this->ln_3(6, "MakeHead CSS OK");
						$back .= $this->js_lines();
						$back .= $this->more_js;
						$this->ln_3(6, "MakeHead javascript OK");
						$back .= $this->titleline();
						$this->ln_3(6, "MakeHead titleline OK");
						$back .= $this->HTMLblock("MakeHead done");
					}
					$this->ln_3(6, "MakeHead head ready");
					echo $back;
				}
			//
				/*** Head to body ***/
				public function Decapite() {
					$this->ln_3(6, "Decapite()");
					$back = "";
					//
					if(!$this->headless) {
						$back .= "</head>\n";
						$this->ln_3(6, "Decapite head done");
						$back .= "<body$this->bodyguards>\n";// must think about this because has args
						$this->ln_3(6, "Decapite body start");
						//// add warning for noscript
						$this->ln_3(6, "Decapite warning noscript");
						$back .= "<noscript><div id=\"noscript\">\n";
						$back .= "You have disabled javascript. This website should work correctly except when filling forms.\n";
						$back .= "</div></noscript>\n";
						//// add warning for IE
						/*
						if($_SESSION["IE"]) {
							$this->ln_3(6, "Decapite warning IE");
							$back .= "<div id=\"IEwarn\">You are using Internet Explorer. This browser has been many times reported to have lacks of security and of compatibility. You may consider using a better browser such as Firefox.</div>\n";
						}
						 */
						echo $back;
						$this->headless = true;
						$this->LogStack(1);// now that headless==true
					}
					$this->ln_3(6, "Decapite end");
				}
			//
				//// init HTML+body
				public function HotBooty($manifest = false) {
					$this->ln_3(6, "HotBooty()");
					$this->initHTML($manifest);
					$this->MakeHead();
					$this->Decapite();
				}
			//
			//
		//
			//// Footer STUFF
				//// invalid HTML
				public function invalidHTML() {
					$this->ln_3(6, "invalidHTML()");
					$this->validHTML = false;
				}
			//
				//// invalid CSS
				public function invalidCSS() {
					$this->ln_3(6, "invalidCSS()");
					$this->validCSS = false;
				}
			//
				//// W3
				public function W3valid() {
					$this->ln_3(6, "W3valid()");
					$back = "";
					if($this->validHTML || $this->validCSS) {
						$this->ln_3(6, "W3valid not empty");
						if($this->validHTML) {
							$this->ln_3(6, "W3valid HTML");
							$back .= "<a target=\"_blank\" href=\"http://validator.w3.org/check?uri=referer\" title=\"valid HTML5\">\n";
							$back .= "<img src=\"/functions/pics/html5.png\" alt=\"valid HTML5\" />\n";
							$back .= "</a>\n";
						}
						if($this->validCSS) {
							$this->ln_3(6, "W3valid CSS");
							$back .= "<a target=\"_blank\" href=\"http://jigsaw.w3.org/css-validator/check?url=referer&amp;profile=css3\" title=\"valid CSS\">\n";
							$back .= "<img src=\"/functions/pics/css3.png\" alt=\"Valid CSS\" />\n";
							$back .= "</a>\n";
						}
					}
					return $back;
				}
			//
				// firefox
				public function firefox() {
					$this->ln_3(6, "firefox()");
					$back = "";
					$back .= "<a target=\"_blank\" href=\"http://firefox.com\" title=\"optimized for Firefox\">\n";
					$back .= "<img src=\"/functions/pics/firefox.png\" alt=\"firefox\" />\n";
					$back .= "</a>\n";
					return $back;
				}
			//
				//// build footer (ca fait les pieds)
				public function MakeFoot() {
					$this->ln_3(6, "MakeFoot()");
					$back = "";
					$back .= "<div id=\"LeftFoot\">\n";
					$back .= $this->git_st();
					$back .= $this->W3valid();
					$back .= $this->firefox();
					$back .= "</div>\n";
					return $back;
				}
			//
				//// Finish
				public function FinishHim() {
					/*** close DB ***/
					$this->DB_Close();
					/*** close HTML ***/
					$this->Bye();
				}
			//
				//// Dead body
				public function DeadBody() {
					return "</body>\n";
				}
			//
				//// Bye bye
				public function Bye() {
					if($this->open) {
						echo "</html>";
						//// no new line
						$this->open = false;
					}
				}
			//
			//
		//
			/*** log+error STUFF ***/
			//
				/*** fatal error (which stops script) ***/
				public function FatalError($text) {
					$this->ln_3(0, $text);
				}
			//
				/*** normal error ***/
				public function NewError($text) {
					$this->ln_3(1, $text);
				}
			//
				/*** warning ***/
				public function NewWarning($text) {
					$this->ln_3(2, $text);
				}
			//
				/*** log message (all kind) ***/
				public function ln_3($level, $text, $before = "", $Push = true) {
					//// 0:fatal 1:error 2:warning 3:info 4:config 5:debug 6:trace
					$back = 0;
					if(
						$level <= $this->LogLevel
						&& (
							$this->headless// if body open can echo whatever
							|| (
								$this->open// HTML open but not yet in body, must check:
								&& (
									$level > 2// because cannot output divs before <body>
									|| $level == 0// because fatal!
								)
							)
						)
					) {
						if($level == 0) {
							echo "<div class=\"error\">$text</div>\n";
							$this->OldErrors++;
							exit(1);// this is a fatal error
						} elseif($level == 1) {
							echo "<div class=\"error\">$text</div>\n";
							$this->OldErrors++;
						} elseif($level == 2) {
							echo "<div class=\"warning\">$text</div>\n";
						} else {
							if($before == "") {
								$before = "PHP class " . __CLASS__;
							}
							echo $this->HTMLcom("$before($level)::$text");
						}
						$back = 1;
					} else {
						if($Push) {
							$this->OldLogText[]   = $text;
							$this->OldLogBefore[] = $before;
							$this->OldLogLevel[]  = $level;
							$back = -1;
						}
					}
					return $back;// needed in LogStack
				}
			//
				/*** change log level ***/
				public function LogLevelUp($level, $stack = true) {
					//// 0:fatal 1:error 2:warning 3:info 4:config 5:debug 6:trace
					$this->ln_3(6, "LogLevelUp($level)");
					$old = $this->LogLevel;
					if($level > 0 && $level <= 6 && $level != $this->LogLevel) {
						if($level >= 2) {
							error_reporting(E_ALL);
							ini_set("display_errors", "1");
							ini_set("display_startup_errors", "1");
						}
						$this->LogLevel = $level;
						if($stack) {
							$this->LogStack();
						}
						$this->ln_3(6, "LogLevelUp up! $this->LogLevel ->$level");
					}
					return $old;
				}
			//
				/*** output the log stack ***/
				public function LogStack($StackLevel = 0) {
					$this->ln_3(6, "LogStack($StackLevel)");
					if($StackLevel == 0) {
						$StackLevel = $this->LogLevel;
					}
					$this->ln_3(5, "LogStack level=$StackLevel");
					$old_level = $this->LogLevel;
					if($StackLevel > $old_level) {
						$old_level = $this->LogLevelUp($StackLevel, false);
					} else {
						$StackLevel = $old_level;
					}
					$this->ln_3(5, "LogStack old_level=$old_level");
					end($this->OldLogText);
					$MaxLogId = key($this->OldLogText);
					$this->ln_3(5, "LogStack MaxLogId=$MaxLogId");
					for($i = 0; $i < $MaxLogId; $i++) {
						if(isset($this->OldLogLevel[$i])) {
							$level = $this->OldLogLevel[$i];
							if($level <= $StackLevel) {
								if($this->ln_3($level, $this->OldLogText[$i], $this->OldLogBefore[$i], false) > 0) {
									unset($this->OldLogLevel[$i]);
									unset($this->OldLogText[$i]);
									unset($this->OldLogBefore[$i]);
								}
							}
						}
					}
					if($old_level < $StackLevel) {
						$this->LogLevelUp($old_level, false);
					}
					$this->ln_3(6, "LogStack end");
				}
			//
				//// HTML comment
				public function HTMLcom($text, $print = false) {
					$ComChar = "#";
					$back = "";
					$back .= "<!--";
					$back .= "$ComChar$ComChar$ComChar";
					$back .= " $text ";
					$back .= "$ComChar$ComChar$ComChar";
					$back .= "-->";
					$back .= "\n";
					if($print) {
						echo $back;
					}
					return $back;
				}
			//
				//// HTML block comment
				public function HTMLblock($text, $start = true) {
					$ComChar = "*";
					$back = "";
					$back .= "<!--";
					$back .= "$ComChar$ComChar$ComChar$ComChar$ComChar";
					$back .= "$ComChar$ComChar$ComChar$ComChar$ComChar";
					$back .= " $text ";
					$back .= "$ComChar$ComChar$ComChar$ComChar$ComChar";
					$back .= "$ComChar$ComChar$ComChar$ComChar$ComChar";
					$back .= "-->\n";
					return $back;
				}
			//
				/*** GET log ***/
				public function GET2log() {
					//// 0:fatal 1:error 2:warning 3:info 4:config 5:debug 6:trace
					if(isset($_GET["pneu"]) && $_GET["pneu"] == "bleu") {
						$this->LogLevelUp(6);
					} elseif(isset($_GET["high"]) && $_GET["high"] == "five") {
						$this->LogLevelUp(5);
					} elseif(isset($_GET["fantastic"]) && $_GET["fantastic"] == "maplesyrup") {
						$this->LogLevelUp(4);
					} elseif(isset($_GET["bogoss"]) && $_GET["bogoss"] == "five") {
						$this->LogLevelUp(3);
					} elseif(isset($_GET["nein"]) && $_GET["nein"] == "eeloven") {
						$this->LogLevelUp(2);
					} elseif(isset($_GET["neo"]) && $_GET["neo"] == "whiterabbit") {
						$this->LogLevelUp(1);
					}
				}
			//
				/*** Count real errors ***/
				public function CountErrors() {
					$back = $this->OldErrors;
					foreach($this->OldLogLevel as $v) {
						if($v <= 1) {
							$back++;
						}
					}
					return $back;
				}
			//
			//
		//
			/*** Date and time STUFF ***/
				/*** convert date ***/
				public function ConvertDate($input, $HasDate = true, $HasTime = false) {
					$arg = "...";
					if(is_string($input)) {
						$arg = $input;
					}
					$this->ln_3(6, "ConvertDate($arg)");
					$back = new stdClass();
					$start = $HasDate ? 11 : 0;
					if(is_string($input)) {
						if($HasDate) {
							$back->str_year  = substr($input, 0, 4);
							$back->str_month = substr($input, 5, 2);
							$back->str_day   = substr($input, 8, 2);
							$back->year  = $back->str_year + 0;
							$back->month = $back->str_month + 0;
							$back->day   = $back->str_day + 0;
						}
						if($HasTime) {
							$back->str_hour   = substr($input, $start+0, 2);
							$back->str_minute = substr($input, $start+3, 2);
							$back->str_second = substr($input, $start+6, 2);
							$back->hour   = $back->str_hour + 0;
							$back->minute = $back->str_minute + 0;
							$back->second = $back->str_second + 0;
						}
					} elseif(is_object($input)) {
						$back = $input;
						if($HasDate) {
							$back->str_year  = sprintf("%04d", $back->year);
							$back->str_month = sprintf("%02d", $back->month);
							$back->str_day   = sprintf("%02d", $back->day);
						}
						if($HasTime) {
							$back->str_hour   = sprintf("%02d", $back->hour);
							$back->str_minute = sprintf("%02d", $back->minute);
							$back->str_second = sprintf("%02d", $back->second);
						}
					}
					if($HasDate) {
						$month_txt = $this->Months($back->month);
						$back->dateCHtxt = "$back->day $month_txt $back->year";
						$back->dateCHnum = "$back->str_day.$back->str_month.$back->str_year";
						$back->dateUStxt = "$month_txt $back->day, $back->year";
						$back->dateISO   = "$back->str_year-$back->str_month-$back->str_day";
						//// ISO8601
						$back->timestamp = $back->dateISO;
						$back->date = $back->dateISO;
					}
					if($HasTime) {
						$back->timeHMS = "$back->str_hour:$back->str_minute:$back->str_second";
						$back->timeHM  = "$back->str_hour:$back->str_minute";
						$back->timeH   = "{$back->str_hour}h$back->str_minute";
						$back->timestamp = $back->timeHMS;
						$back->time = $back->timeHMS;
					}
					if($HasDate && $HasTime) {
						$back->timestamp = "$back->dateISO $back->timeHMS";
					}
					$this->ln_3(5, "ConvertDate treated $back->timestamp");
					return $back;
				}
			//
				/*** months ***/
				public function Months($num_month = 0) {
					$this->ln_3(6, "Months($num_month)");
					//// using switch because array calls harpercollins for all
					switch($num_month) {
						case 1:
							return $this->HarperCollins("January");
							break;
						case 2:
							return $this->HarperCollins("February");
							break;
						case 3:
							return $this->HarperCollins("March");
							break;
						case 4:
							return $this->HarperCollins("April");
							break;
						case 5:
							return $this->HarperCollins("May");
							break;
						case 6:
							return $this->HarperCollins("June");
							break;
						case 7:
							return $this->HarperCollins("July");
							break;
						case 8:
							return $this->HarperCollins("August");
							break;
						case 9:
							return $this->HarperCollins("September");
							break;
						case 10:
							return $this->HarperCollins("October");
							break;
						case 11:
							return $this->HarperCollins("November");
							break;
						case 12:
							return $this->HarperCollins("December");
							break;
						default:
							return array(
								$this->HarperCollins("January"),
								$this->HarperCollins("February"),
								$this->HarperCollins("March"),
								$this->HarperCollins("April"),
								$this->HarperCollins("May"),
								$this->HarperCollins("June"),
								$this->HarperCollins("July"),
								$this->HarperCollins("August"),
								$this->HarperCollins("September"),
								$this->HarperCollins("October"),
								$this->HarperCollins("November"),
								$this->HarperCollins("December")
							);
							break;
					}
				}
			//
				/*** minute to hours (int) ***/
				public function minutes2HoursInt($minutes) {
					return intval($minutes / 60);
				}
			//
				/*** minutes to rest of minutes ***/
				public function minutes2MinutesRest($minutes) {
					return $minutes % 60;
				}
			//
				/*** display minutes as h:mm ***/
				public function minutesDisplay($minutes) {
					return sprintf("%d:%02d", $this->minutes2HoursInt($minutes), $this->minutes2MinutesRest($minutes));
				}
			//
				/*** GetNow ***/
				public function GetNow() {
					$this->ln_3(6, "GetNow()");
					$back = new stdClass();
					$now = localtime(time(), true);
					$back->year   = $now["tm_year"] + 1900;
					$back->month  = $now["tm_mon"]  +    1;
					$back->day    = $now["tm_mday"];
					$back->hour   = $now["tm_hour"];
					$back->minute = $now["tm_min"];
					$back->second = $now["tm_sec"];
					//// Get all
					$back = $this->ConvertDate($back, true, true);
					return $back;
				}
			//
			//
		//
			/*** Form STUFF ***/
				/*** form tag ***/
				public function FormTag(stdClass $varargin = NULL) {
					/*** varargin ***/
					$method = "post";
					$action = $this->FileName;
					$leeloo = false;
					$more = "";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					if($method != "post" && $method != "get") {
						$this->ln_3(2, "FormTag method not valid, default post");
						$method = "post";
					}
					$this->ln_3(6, "FormTag($method, $action)");
					if($more != "") {
						$more = " $more";
					}
					if($leeloo) {
						$more .= " enctype=\"multipart/form-data\"";
					}
					return "<form method=\"$method\" action=\"$action\"$more>\n";
				}
			//
				/*** submit buttons ***/
				public function SubButt($condition, $popUpText, stdClass $varargin = NULL) {
					/*** varargin ***/
					$erase_allowed = true;
					$css = "SubButt";
					$add    = $this->HighFive($this->HarperCollins("add"));
					$CloseTag = false;
					$cancelURL = "index.php";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					// js needs quotes
					$popUpText = "'" . addslashes($popUpText) . "'";

					$this->ln_3(6, "SubButt(popUpText=$popUpText)");
					$update = $this->HighFive($this->HarperCollins("update"));
					$erase  = $this->HighFive($this->HarperCollins("erase"));
					$reset  = $this->HighFive($this->HarperCollins("reset"));
					$cancel = $this->HighFive($this->HarperCollins("cancel"));
					$wojs = $this->HarperCollins("without") . " javascript";
					$back = "";
					$back .= $this->HTMLblock("SubButt start");
					$back .= "<div class=\"$css\">\n";
					if($condition) {
						$this->ln_3(6, "SubButt editing");
						//// update
						$back .= "<input type=\"submit\" name=\"submit\" value=\"$update\"";
						$back .= " onclick=\"SubmitForm()\"";
						if(!$_SESSION["mobile"]) {//// 'cause iOS sucks :-P
							$back .= " disabled=\"disabled\"";
						}
						$back .= " />\n";
						if($erase_allowed) {
							//// erase
							$back .= "<input type=\"submit\" name=\"erase\" value=\"$erase\" onclick=\"return ConfirmErase($popUpText";
							if($this->GetSessionLang() != $this->GetEnglish()) {
								$back .= ", false";
							}
							$back .= ")\" />\n";
						}
					} else {
						$this->ln_3(6, "SubButt new entry");
						//// add
						$back .= "<input type=\"submit\" name=\"submit\" value=\"$add\"";
						$back .= " onclick=\"SubmitForm()\"";
						$back .= " />\n";
					}
					//// reset
					$back .= "<input type=\"reset\" value=\"$reset\" onclick=\"ResetForm()\" />\n";
					//// cancel
					$back .= "<input type=\"button\" name=\"cancel\" value=\"$cancel\" onclick=\"window.location='$cancelURL';\" />\n";
					$back .= "<noscript><a href=\"$cancelURL\" title=\"$cancel $wojs\">$cancel $wojs</a></noscript>\n";
					$back .= "</div>\n";
					if($CloseTag) {
						$this->ln_3(6, "SubButt /form");
						$back .= "</form>\n";
					}
					$back .= $this->HTMLblock("SubButt finished");
					$this->ln_3(6, "SubButt //end//");
					return $back;
				}
			//
				/*** display a field for a form ***/
				public function FormField(stdClass $varargin) {
						$pi = 3.14159265359;
						/*** varargin ***/
						$title = "";
						$value = NULL;
						$list = array();// array (seq/assoc) for select, radio, checkbox
						$listQuery = NULL;
						$listQueryValue = NULL;
						$listQueryTitle = NULL;
						$keyval = false;
						$css = "";
						$posttitle = "";
						$size = 0;
						$rows = 0;
						$cols = 0;
						$required = false;
						$readonly = false;
						$disabled = false;
						$doSecond = true;
						$js = true;// true for enablesubmit (default)
						$vlist = false;// true for vertical list (idem)
						$div = true;
						$paragraph = false;
						$autofocus = false;
						$PreCheckbox = false;
						$colon = true;
						$datalist = array();
						$ListID = "";
						$min = $pi;
						$max = $pi;
						$step = $pi;
						$WithEmpty = false;
						$MaxFileSize = 0;
						$noCheckArray = false;
						$yearFirst = -5;
						$yearLast = -5;
						$jsChanged = true;
						foreach($varargin as $k => $v) {$$k = $v;}
						$mandatory = array(
							"type",  // text, password, radio, checkbox, select, textarea, Date, Time
							"name"  // HTML name
						);
						foreach($mandatory as $m) {
							if(!isset($$m)) {
								$this->ln_3(6, "FormField($type, $name)");
								$this->ln_3(0, "FormField: $m required");
								exit;
							}
						}
						/*** /varargin ***/
					$this->ln_3(6, "FormField($type, $name)");
					//
						/*** values for checkbox/radio ***/
						$inputtype = "checkbox";
						$checkarray = "[]";
						if($noCheckArray) {
							$checkarray = "";
						}
						if($type == "radio") {
							$this->ln_3(3, "radio are not mobile-thought...");
							$inputtype = "radio";
							$checkarray = "";
						}
					//
						/*** CSS ***/
						$cssclass = " class=\"$name\"";
						if($css != "") {
							$cssclass = " class=\"$css\"";
						}
						$this->ln_3(5, "FormField cssclass=$cssclass");
					//
						/*** ARGV ***/
						$moreargs = "";
							/*** size ***/
							if($size > 0) {
								$moreargs .= " size=\"$size\"";
							}
						//
							/*** rows ***/
							if($rows > 0) {
								$moreargs .= " rows=\"$rows\"";
							}
						//
							/*** cols ***/
							if($cols > 0) {
								$moreargs .= " cols=\"$cols\"";
							}
						//
							/*** readonly ***/
							if($readonly) {
								$moreargs .= " readonly=\"readonly\"";
							}
						//
							/*** disabled ***/
							if($disabled) {
								$moreargs .= " disabled=\"disabled\"";
							}
						//
							/*** required ***/
							if($required) {
								$moreargs .= " required";
							}
						$this->ln_3(5, "FormField moreargs=$moreargs");
					//
						//// js
						$onjs = "";
						if($js) {
							$jsfunc = "FieldAction()";

							$onjs  = "";
							if($jsChanged) {
								$onjs .= " onchange=\"FieldChanged()\"";
							} else {
								$onjs .= " onchange=\"$jsfunc\"";
							}
							$onjs .= " oninput=\"$jsfunc\"";
							$onjs .= " onpaste=\"$jsfunc\"";
							$onjs .= " oncut=\"$jsfunc\"";
							$onjs .= " onblur=\"$jsfunc\"";
							$onjs .= " onkeyup=\"$jsfunc\"";
						}
					//
						//// AF
						$af = "";
						if($autofocus) {
							$af = " autofocus";
						}
					//
						// DB list
						if($list == array() && $listQuery != NULL) {
							$items = $this->DB_QueryManage($listQuery);
							if($items->num_rows > 0) {
								while($item = $items->fetch_object()) {
									$list[$item->$listQueryValue] = $item->$listQueryTitle;
								}
							}
							$items->close();
						}
					////// Let's go!
					$back = "";
					if($div) {
						$this->ln_3(5, "FormField setting div around");
						$back .= "<div$cssclass>\n";
					} elseif($paragraph) {
						$this->ln_3(5, "FormField setting p around");
						$back .= "<p$cssclass>\n";
					}
					if($title != "") {
						$this->ln_3(5, "FormField setting title=$title");
						$back .= "<label for=\"$name\">$title</label>&nbsp;";
						if($colon) {
							$back .= ": ";
						}
					}
					switch($type) {
						case "hidden":
							$this->ln_3(6, "FormField hidden");
							$back .= "<input";
							$back .= " id=\"$name\"";
							$back .= " type=\"$type\"";
							$back .= " name=\"$name\"";
							if($value != NULL) {
								$back .= " value=\"" . stripslashes($value) . "\"";
							}
							$back .= $moreargs;
							$back .= " />\n";
							break;
						case "text":
						case "search":// with a cross to cancel input
						case "email":
						case "url":
							$this->ln_3(6, "FormField $type");
							$back .= "<input";
							$back .= " id=\"$name\"";
							$back .= " type=\"$type\"";
							$back .= " name=\"$name\"";
							if($value != NULL) {
								$back .= " value=\"" . stripslashes($value) . "\"";
							}
							if($ListID != "") {
								$back .= " list=\"$ListID\"";
								$this->ln_3(6, "FormField using list id=$ListID");
							}
							$back .= $af;
							$back .= $onjs;
							$back .= $moreargs;
							$back .= " />\n";
							if($ListID != "" && $datalist !== array()) {
								$this->ln_3(6, "FormField building list id=$ListID");
								$back .= "<datalist id=\"$ListID\">\n";
								foreach($datalist as $d) {
									$back .= "<option value=\"$d\" />\n";
								}
								$back .= "</datalist>\n";
							}
							break;
						case "password":
							$this->ln_3(6, "FormField $type");
							$back .= "<input";
							$back .= " id=\"$name\"";
							$back .= " type=\"$type\"";
							$back .= " name=\"$name\"";
							//if($value != NULL) {
								//$back .= " value=\"" . stripslashes($value) . "\"";
							//}
							$back .= " value=\"\"";
							$back .= $af;
							$back .= $onjs;
							$back .= $moreargs;
							$back .= " />\n";
							break;
						case "radio":
						case "checkbox":
							$this->ln_3(6, "FormField radio/checkbox");
							foreach($list as $key => $val) {
								if($vlist) {
									$back .= "<br />\n";
								} else {
									$back .= "&nbsp;\n";
								}
								$back .= "<input";
								$back .= " type=\"$inputtype\"";
								$back .= " id=\"{$name}_$key\"";
								$back .= " name=\"$name$checkarray\"";
								$back .= " value=\"$key\"";
								if($value != NULL) {
									if(is_array($value)) {
										foreach($value as $valve) {
											if($valve == $key) {
												$back .= " checked=\"checked\"";
											}
										}
									} else {
										if($value == $key) {
											$back .= " checked=\"checked\"";
										}
									}
								}
								$back .= $onjs;
								$back .= $moreargs;
								$back .= " />";
								$back .= "<label";
								$back .= " for=\"${name}_$key\">";
								$back .= "&nbsp;$val";
								$back .= "</label>\n";
							}
							break;
						case "select":
							$this->ln_3(6, "FormField select");
							$back .= "<select ";
							$back .= "id=\"$name\" ";
							$back .= "name=\"$name\"";
							$back .= $af;
							$back .= $onjs;
							$back .= $moreargs;
							$back .= ">\n";
							if($WithEmpty) {
								$back .= "<option value=\"\">--</option>\n";
							}
							if($list === array_values($list) && !$keyval) {
								//// Is sequential
								$this->ln_3(6, "FormField values sequential");
								foreach($list as $o) {
									$back .= "<option value=\"$o\" ";
									if($o == $value) {
										$back .= "selected=\"selected\"";
									}
									$back .= ">$o</option>\n";
								}
							} else {
								//// Is associative
								$this->ln_3(6, "FormField values associative");
								foreach($list as $key => $val) {
									$back .= "<option value=\"$key\" ";
									if($key == $value) {
										$back .= "selected=\"selected\"";
									}
									$back .= ">$val</option>\n";
								}
							}
							$back .= "</select>\n";
							break;
						case "Date":
							//// Date
							$this->ln_3(6, "FormField Date");
							$now = $this->GetNow();
							$banana = $now;
							if($value != "" && $value != NULL) {
								$banana = $this->ConvertDate($value);
							}
							$this->ln_3(5, "FormField today=$banana->year-$banana->month-$banana->day");
								//// Day
								$days = array();
								for($i = 1; $i < 32; $i++) {
									$days[] = sprintf("%02d", $i);
								}
								$args = new stdClass();
								$args->type = "select";
								$args->title = "";
								$args->name = "${name}_day";
								$args->value = $banana->day;
								$args->list = $days;
								$args->autofocus = $autofocus;
								$args->required = $required;
								$args->js = $js;
								$args->div = false;
								$args->WithEmpty = $WithEmpty;
								$back .= $this->FormField($args);
							//
								//// Month
								$months = array();
								for($i = 1; $i < 13; $i++) {
									$months["$i"] = $this->Months($i);
								}
								$args->name = "${name}_month";
								$args->value = $banana->month;
								$args->list = $months;
								$back .= $this->FormField($args);
							//
								//// Year
								if($yearFirst <= 0) {
									$yearFirst = $now->year + $yearFirst;
								}
								if($banana->year < $yearFirst) {
									$yearFirst = $banana->year;
								}

								if($yearLast <= 0) {
									$yearLast = $now->year - $yearLast;
								}
								if($banana->year > $yearLast) {
									$yearLast = $banana->year;
								}

								$years = array();
								for($i = $yearLast; $i >= $yearFirst; $i--) {
									$years[] = $i;
								}
								if($years[count($years)-1] == $now->year) {
									$years = array_reverse($years);
								}
								$args->name = "${name}_year";
								$args->value = $banana->year;
								$args->list = $years;
								$back .= $this->FormField($args);
							break;
						case "Time":
							//// Time
							$this->ln_3(6, "FormField Time");
							$banana = $this->GetNow();
							if($value != "" && $value != NULL) {
								$banana = $this->ConvertDate($value, true, true);
							}
							$this->ln_3(5, "FormField now=$banana->hour:$banana->minute:$banana->second");
								//// Hour
								$hours = array();
								for($i = 0; $i < 24; $i++) {
									$hours[] = sprintf("%02d", $i);
								}
								$args = new stdClass();
								$args->type = "select";
								$args->title = $title;
								$args->name = "${name}_hour";
								$args->value = $banana->hour;
								$args->list = $hours;
								$args->autofocus = $autofocus;
								$args->required = $required;
								$args->js = $js;
								$args->div = false;
								$args->WithEmpty = $WithEmpty;
								$back .= $this->FormField($args);
							//
							$back .= ":";
							//
								//// Minute
								$minutes = array();
								for($i = 0; $i < 60; $i++) {
									$minutes[] = sprintf("%02d", $i);
								}
								$args->name = "${name}_minute";
								$args->value = $banana->minute;
								$args->list = $minutes;
								$back .= $this->FormField($args);
							//
								//// Second (optional)
								if($doSecond) {
									$back .= ":";
									$this->ln_3(6, "FormField Time with second");
									$seconds = array();
									for($i = 0; $i < 60; $i++) {
										$seconds[] = sprintf("%02d", $i);
									}
									$args->name = "${name}_second";
									$args->value = $banana->second;
									$args->list = $seconds;
									$back .= $this->FormField($args);
								}
							break;
						case "textarea":
							$this->ln_3(6, "FormField textarea");
							if($title != "") {
								$back .= "<br />\n";
							}
							$back .= "<textarea ";
							//$back .= "id=\"$name\" ";
							//$back .= "class=\"tnr\" ";
							$back .= "name=\"$name\"";
							$back .= $onjs;
							$back .= $af;
							$back .= $moreargs;
							$back .= ">";// no \n because it ends in DB
							if($value != NULL) {
								$back .= "$value";// no \n because it ends in DB
							}
							$back .= "</textarea>\n";
							break;
						case "file":
							$this->ln_3(6, "FormField file");
							if($MaxFileSize > 0) {
								if($MaxFileSize == 1) {
									$MaxFileSize = 5242880;
								}
								$back .= "<input";
								$back .= " type=\"hidden\"";
								$back .= " name=\"MAX_FILE_SIZE\"";
								$back .= " value=\"$MaxFileSize\"";
								$back .= " />\n";
							}
							$back .= "<input";
							$back .= " id=\"$name\"";
							$back .= " type=\"file\"";
							$back .= " name=\"$name\"";
							$back .= $moreargs;
							$back .= "/>\n";
							break;
						case "date":
						case "datetime":
						case "time":
						case "datetime-local":
						//case "month":
						//case "week":
							$this->ln_3(6, "FormField $type");
							$back .= "<input";
							$back .= " id=\"$name\"";
							$back .= " type=\"$type\"";
							$back .= " name=\"$name\"";
							if($value != NULL) {
								$back .= " value=\"" . stripslashes($value) . "\"";
							}
							$back .= $af;
							$back .= $onjs;
							$back .= $moreargs;
							$back .= "/>\n";
							break;
						case "color":// color picker
							$this->ln_3(6, "FormField $type");
							$back .= "<input";
							$back .= " id=\"$name\"";
							$back .= " type=\"$type\"";
							$back .= " name=\"$name\"";
							if($value != NULL) {
								$back .= " value=\"" . stripslashes($value) . "\"";
							}
							$back .= $af;
							$back .= $onjs;
							$back .= $moreargs;
							$back .= "/>\n";
							break;
						case "number":// with min and max
						case "range":// cursor on bar (without marks?)
							$this->ln_3(6, "FormField $type");
							$back .= "<input";
							$back .= " id=\"$name\"";
							$back .= " type=\"$type\"";
							$back .= " name=\"$name\"";
							if($value != NULL) {
								$back .= " value=\"" . stripslashes($value) . "\"";
							}
							if($min != $pi) {
								$back .= " min=\"$min\"";
							}
							if($max != $pi) {
								$back .= " max=\"$max\"";
							}
							if($step != $pi) {
								$back .= " step=\"$step\"";
							}
							$back .= $af;
							$back .= $onjs;
							$back .= $moreargs;
							$back .= "/>\n";
							break;
						default:
						case "image":// for buttons
						case "button":// for JS
						//case "tel":
							$this->ln_3(1, "FormField: $type not yet implemented");
							break;
					}
					if($posttitle != "") {
						$back .= "&nbsp;$posttitle";
					}
					if($div) {
						$back .= "</div>\n";
					} elseif($paragraph) {
						$back .= "</p>\n";
					}
					$this->ln_3(6, "FormField end");
					return $back;
				}
			//
			//
		//
			/*** languages STUFF ***/
				/*** Get available languages ***/
				public function GetAvailLangs($index = -1) {
					$this->ln_3(6, "GetAvailLangs($index)");
					if(!isset($_SESSION["AvailLangs"])) {
						// Default to english+french
						$_SESSION["AvailLangs"] = array($this->longEnglish, $this->longFrench);
					}

					$result = "";
					$resultStr = "";
					if($index >= 0) {
						$result = $_SESSION["AvailLangs"][$index];
						$resultStr = $result;
					} else {
						$result = $_SESSION["AvailLangs"];
						foreach($result as $r) {
							$resultStr .= " $r";
						}
					}
					$this->ln_3(5, "GetAvailLangs: $resultStr");
					return $result;
				}
			//
				// Set available languages
				public function setAvailLangs($languages) {
					$this->ln_3(6, "setAvailLangs(...)");
					if(is_array($languages) && $languages !== array()) {
						$new = array();
						for($iLang = 0; $iLang < count($languages); $iLang++) {
							$language = $languages[$iLang];
							if(array_key_exists($language, $this->long)) {
								$language = $this->long[$language];
							}
							$new[] = $language;
						}
						if($new !== $this->GetAvailLangs()) {
							$newStr = "";
							foreach($new as $n) {
								$newStr .= " $n";
							}
							$this->ln_3(5, "setAvailLangs: $newStr");
							$_SESSION["AvailLangs"] = $new;
							if(!in_array($_SESSION["language"], $new)) {
								$this->ChangeSessionLang($new[0]);
							}
						}
					}
				}
			//
				// Get language string
				private function getLanguage($string) {
					if(in_array($string, $this->GetAvailLangs())) {
						return $string;
					} else {
						return $this->longUndef;
					}
				}
			//
				/*** Get english string ***/
				public function GetEnglish() {
					return $this->getLanguage($this->longEnglish);
				}
			//
				/*** Get french string ***/
				public function GetFrench() {
					return $this->getLanguage($this->longFrench);
				}
			//
				/*** Get wolof string ***/
				public function GetWolof() {
					return $this->getLanguage($this->longWolof);
				}
			//
				/*** Get mandinka string ***/
				public function GetMandinka() {
					return $this->getLanguage($this->longMandinka);
				}
			//
				/*** cookie language ***/
				public function LanguageCookieGet($default = "english", $var = "language") {
					/*** language cookie GET ***/
					$this->ChangeSessionLang();
					/*** set cookie for next visit (before any output) ***/
					$expire = time() + 3600 * 24 * 300;// 300 days
					setcookie($var, $_SESSION[$var], $expire, $this->CookieRecipe());
				}
			//
				/*** internal dictionary ***/
				/*** input (english) and output lower case only ***/
				public function HarperCollins($word, $female = false) {
					$this->ln_3(6, "HarperCollins($word, female=" . (int) $female . ")");
					$this->ln_3(5, "HarperCollins trying to translate to {$_SESSION["language"]}");
					$back = $word;
					switch($word) {
						case "add":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "ajouter";
							} elseif($_SESSION["language"] == $this->GetWolof()) {
								$back = "dolli";
							} elseif($_SESSION["language"] == $this->GetMandinka()) {
								$back = "kafu";
							}
							break;
						case "April":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "avril";
							}
							break;
						case "August":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "ao&ucirc;t";
							}
							break;
						case "cancel":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "annuler";
							} elseif($_SESSION["language"] == $this->GetWolof()) {
								$back = "fomm";
							} elseif($_SESSION["language"] == $this->GetMandinka()) {
								$back = "bayoo";
							}
							break;
						case "date":
							//if($_SESSION["language"] == $this->GetFrench()) {
								//$back = "date";
							//}
							break;
						case "December":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "d&eacute;cembre";
							}
							break;
						case "delete":
						case "erase":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "effacer";
							} elseif($_SESSION["language"] == $this->GetWolof()) {
								$back = "far";
							} elseif($_SESSION["language"] == $this->GetMandinka()) {
								$back = "djan djan";
							}
							break;
						case "February":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "f&eacute;vrier";
							}
							break;
						case "found":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "trouv&eacute;";
								if($female) {
									$back .= "e";
								}
							}
							break;
						case "January":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "janvier";
							}
							break;
						case "July":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "juillet";
							}
							break;
						case "June":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "juin";
							}
							break;
						case "last":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "dernier";
								if($female) {
									$back = "derni&egrave;re";
								}
							}
							break;
						case "May":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "mai";
							}
							break;
						case "March":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "mars";
							}
							break;
						case "next":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "prochain";
								if($female) {
									$back .= "e";
								}
							}
							break;
						case "nothing":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "rien";
							}
							break;
						case "November":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "novembre";
							}
							break;
						case "October":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "octobre";
							}
							break;
						case "reset":
							break;
						case "September":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "septembre";
							}
							break;
						case "time":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "heure";// special for time form
							}
							break;
						case "today":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "aujourd'hui";
							}
							break;
						case "tomorrow":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "demain";
							}
							break;
						case "update":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "editer";
							}
							break;
						case "welcome":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "bienvenue";
							}
							break;
						case "with":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "avec";
							}
							break;
						case "without":
							if($_SESSION["language"] == $this->GetFrench()) {
								$back = "sans";
							}
							break;
						default:
							break;
					}
					$this->ln_3(5, "HarperCollins($word) => $back");
					return $back;
				}
			//
				/*** change session language ***/
				public function ChangeSessionLang($newlang="", $default="", $session="language") {
					$this->ln_3(6, "ChangeSessionLang($newlang, $default, $session)");
					$oldlang = "";
					//$newlang = "";
					if(isset($_GET["language"])) {
						$this->ln_3(6, "ChangeSessionLang GET");
						if(
							$_GET["language"] != ""
							&& in_array($_GET["language"], $this->GetAvailLangs())
						) {
							$this->ln_3(6, "ChangeSessionLang GET is valid");
							$newlang = $_GET["language"];
							$this->ln_3(5, "ChangeSessionLang(GET) => $newlang");
						}
					}
					if($newlang == "") {
						if(
							isset($_COOKIE[$session])
							&& $_COOKIE[$session] != ""
							&& in_array($_COOKIE[$session], $this->GetAvailLangs())
						) {
							$newlang = $_COOKIE[$session];
							$this->ln_3(5, "ChangeSessionLang(COOKIE) => $newlang");
						} elseif(
							$default != ""
							&& in_array($default, $this->GetAvailLangs())
						) {
							$newlang = $default;
							$this->ln_3(5, "ChangeSessionLang(DEFAULT) => $newlang");
						}
					}
					if($newlang != "" && in_array($newlang, $this->GetAvailLangs())) {
						$odlo = "''";
						if(isset($_SESSION[$session])) {
							$oldlang = $_SESSION[$session];
							$odlo = $oldlang;
						}
						if($newlang != $oldlang) {
							$_SESSION[$session] = $newlang;
							$this->ln_3(5, "ChangeSessionLang $oldlang=>$newlang");
						}
					}
					$this->ln_3(6, "ChangeSessionLang done");
					return $oldlang;
				}
			//
				/*** languages ***/
				public function Languages(stdClass $varargin = NULL) {
					/*** varargin ***/
					$css = "language";
					$picpath = "";
					$moreurl = "";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					$this->ln_3(6, "Languages($css, $picpath, $moreurl)");
					/*** /varargin ***/
					$which = $this->GetAvailLangs();
					if(count($which) == 1) {
						$this->ln_3(5, "Languages single language not necessary");
						return "";
					}
					$back = "";
					$back .= "<div id=\"$css\">\n";
						/*** Set moreURL if not empty ***/
						if($moreurl != "") {
							$moreurl = "&amp;$moreurl";
						}
					//
						/*** Set default pic path and ext ***/
						if($picpath == "") {
							$picpath = "/pictures/languages";
						}
						$picext = "png";
					//
						/*** Check if actual language is in array ***/
						$foundit = 0;
						foreach($which as $l) {
							if($_SESSION["language"] == $l) {
								$foundit = 1;
							}
						}
						if(!$foundit) {
							$_SESSION["language"] = $which[0];
						}
					//
						/*** Load the required languages ***/
						foreach($which as $l) {
							if($_SESSION["language"] != $l) {
								$pic = "$picpath/$l.png";
								$back .= "<a href=\"$this->FileName?language=$l$moreurl\"><img class=\"limg\" title=\"$l\" alt=\"$l\" src=\"$pic\" /></a>\n";
							}
						}
					//
					$back .= "</div>\n";
					$this->ln_3(6, "Languages end");
					return $back;
				}
			//
				/*** Compare session language ***/
				public function CheckSessionLang($lang) {
					$this->ln_3(6, "CheckSessionLang($lang)");
					return ($_SESSION["language"] == $lang);
				}
			//
				/*** Get session language ***/
				public function GetSessionLang() {
					$this->ln_3(6, "GetSessionLang()");
					return $_SESSION["language"];
				}
			//
			//
		//
			/*** DB STUFF ***/
				/*** Init DB ***/
				public function initDB($DB = "") {
					$this->ln_3(6, "initDB($DB)");
					if($this->mysqli === NULL) {
						if(!$mysqli = $this->DB_Connection()) {
							echo "Problem with database";
							exit();
						}
						$this->mysqli = $mysqli;
					} else {
						$this->ln_3(5, "initDB: DB already defined");
					}
				}
			//
				/*** Connection ***/
				public function DB_Connection($which = "collections") {
					$this->ln_3(6, "DB_Connection()");
					$mysqli = false;
					$server = "";
					$us = "";
					$pd = "";
					if($this->LocalHost()) {
						$server = "localhost";
						$us = "phpmyadmin";
						$pd = "root";
					} else {
						$server = $this->ddb->server;
						$us = $this->ddb->username;
						$pd = $this->ddb->password;
					}
					$this->ln_3(6, "DB_Connection($which)");
					$mysqli = new mysqli($server, $us, $pd, $this->ddb->DBname);
					if($error_num = mysqli_connect_errno()) {
						echo "<div id=\"error\">Connection error: " . mysqli_connect_error() . " ($error_num)</div>";
						exit();
					}
					return $mysqli;
				}
			//
				/*** close ***/
				public function DB_Close() {
					if($this->mysqli != null) {
						$this->mysqli->close();
						$this->mysqli = null;
					}
				}
			//
				/*** display error ***/
				public function DB_DispErr($query, $file = "", $line = 0) {
					$this->ln_3(6, "DB_DispErr(...)");
					$errorprint = "Probl&egrave;me dans la gestion de la base de donn&eacute;e.";
					$adminerror = "";
					if($file != "") {
						$adminerror .= "In file $file ";
					} else {
						$adminerror .= "In file $this->FileName ($this->FilePath) ";
					}
					if($line != 0) {
						$adminerror .= "at line $line ";
					}
					if($adminerror != "") {
						$adminerror .= ": <br />\n";
					}
					$errors = "";
					if($query) {
						if($query->errno != "") {
							$errors .= "Erreur No $query->errno : $query->error.\n";
						}
					}
					if($this->mysqli->errno != "") {
						if($errors != "") {
							$errors .= "<br />\n";
						}
						$errors .= "Erreur No {$this->mysqli->errno} : {$this->mysqli->error}.\n";
					}
					$adminerror .= $errors;
					if($this->UserIsAdmin()) {
						$errorprint .= "<br />\n$adminerror";
					}
					$this->ln_3(0, $errorprint);
				}
			//
				/*** query prepare ***/
				public function DB_QueryPrepare($query) {
					$this->ln_3(6, "DB_QueryPrepare(...)");
					if(!$back = $this->mysqli->prepare($query)) {
						$this->DB_DispErr($back);
						$this->ln_3(0, "Could not prepare mysqli");
						return false;
					}
					return $back;
				}
			//
				/*** query manage ***/
				public function DB_QueryManage($query, $filename = "", $lineno = 0) {
					$this->ln_3(6, "DB_QueryManage(...)");
					if(!$back = $this->mysqli->query($query)) {
						$this->DB_DispErr($back, $filename, $lineno);
					}
					return $back;
				}
			//
				//// query manage with external id provided
				public function DB_IdManage($query, $id, $filename = "", $lineno = 0) {
					$this->ln_3(6, "DB_IdManage(..., $id)");
					$sql = $this->DB_QueryPrepare($query, $filename, $lineno);
					$sql->bind_param("i", $id);
					$this->DB_ExecuteManage($sql, $filename, $lineno);
					return $sql;
				}
			//
				//// query insert
				public function DB_QueryInsert($table, $fields, $filename = "", $lineno = 0) {
					$this->ln_3(6, "DB_QueryInsert($table, ...)");
					$query = "INSERT INTO `" . $this->ddb->DBname . "`.`$table` (";
					$first = true;
					$endquery = "";
					$params = array();
					foreach($fields as $k => $v) {
						if($first) {
							$first = false;
						} else {
							$query .= ", ";
							$endquery .= ", ";
						}
						$query   .= "`$k`";
						$endquery .= "?";
						$params[] = &$v->type;
						$params[] = &$v->value;
					}
					$query .= ") VALUES($endquery)";
					$sql = $this->DB_QueryPrepare($query);
					call_user_func_array(array($sql, "bind_param"), $params);
					$this->DB_ExecuteManage($sql);
					return $sql->insert_id;
				}
			//
				//// query update
				public function DB_QueryUpdate($table, $fields, $id, $filename = "", $lineno = 0) {
					$this->ln_3(6, "DB_QueryUpdate($table, ..., $id)");
					$query = "UPDATE `" . $this->ddb->DBname . "`.`$table` (";
					$first = true;
					$params = array();
					foreach($fields as $k => $v) {
						if($first) {
							$first = false;
						} else {
							$query .= ", ";
							$endquery .= ", ";
						}
						$query   .= "SET `$k` = ?";
						$params[] = &$v->type;
						$params[] = &$v->value;
					}
					$query .= "WHERE `$table`.`id` = ? LIMIT 1";
					$idtype = "i";
					$params[] = &$idtype;
					$params[] = &$id;
					$sql = $this->DB_QueryPrepare($query);
					call_user_func_array(array($sql, "bind_param"), $params);
					$this->DB_ExecuteManage($sql);
					return $id;
				}
			//
				/*** execute manage ***/
				public function DB_ExecuteManage($query, $filename = "", $lineno = 0) {
					$this->ln_3(6, "DB_ExecuteManage(...)");
					if(!$back = $query->execute()) {
						$this->DB_DispErr($query, $filename, $lineno);
					}
					return $back;
				}
			//
				//// select all
				public function DB_SelectAll($table, $orders = array(), $more = "") {
					$query = "SELECT * FROM `$table`";
					if(count($orders) > 0) {
						if(count($orders) == 1 && $orders[0] == "rand") {
							$query .= " ORDER BY RAND()";
						} else {
							for($i = 0; $i < count($orders); $i++) {
								if($i == 0) {
									$query .= " ORDER BY";
								} else {
									$query .= ", ";
								}
								$field = $orders[$i];
								$way = "ASC";
								if(substr($field, 0, 4) == "DESC") {
									$way = "DESC";
									$field = substr($field, 4);
								}
								$query .= "`$field` $way";
							}
							foreach($orders as $o => $r) {
							}
						}
					}
					if($more != "") {
						$query .= " $more";
					}
					return $this->DB_QueryManage($query);
				}
			//
				//// select ID
				public function DB_SelectId($table, $id, $field = "id") {
					return $this->DB_IdManage("SELECT * FROM `$table` WHERE `$field` = ?", $id);
				}
			//
				/*** get a random entry from a given DB table ***/
				public function DB_RandomEntry($table) {
					$this->ln_3(6, "DB_RandomEntry($table)");
					/*** Get id max ***/
					$maxid = $this->DB_QueryManage("SELECT MAX(`id`) AS maxid FROM `$table`");
					$max_id = $maxid->fetch_object();
					$maxid->close();
					$idmax = $max_id->maxid;
					$this->ln_3(5, "DB_RandomEntry idmax=$idmax");
					/*** Get a valid random entry ***/
					$num_rows = 0;
					do {
						$number = rand(1, $idmax);
						$this->ln_3(5, "DB_RandomEntry number=$number");
						$entry = $this->DB_QueryManage("SELECT * from `$table` WHERE `id` = $number");
						$num_rows = $entry->num_rows;
						$entry->close();
						$this->ln_3(5, "DB_RandomEntry num_rows=$num_rows");
					} while($num_rows == 0);
					$this->ln_3(5, "DB_RandomEntry final number=$number");
					$this->ln_3(6, "DB_RandomEntry end");
					return $number;
				}
			//
				/*** get next or last entries ***/
				public function DB_NextLast($tables, $next = true, $female = false) {
					$this->ln_3(6, "DB_NextLast(..., next=" . (int) $next . ", female=" . (int) $female . ")");
					$basequery = "SELECT `date`, `date` FROM `";
					$query = "";
					if(!is_array($tables)) {
						$query = "$basequery$tables` ";
					} else {
						$next = false;
						for($i = 0; $i < count($tables); $i++) {
							if($query != "") {
								$query .= "UNION ";
							}
							$query .= "$basequery{$tables[$i]}` ";
						}
					}
					$query .= "HAVING DATEDIFF(`date`, CURDATE()) ";
					//$this->ln_3(5, "DB_NextLast query=$query");
					$next_events = $this->DB_QueryManage("$query> 1 ORDER BY `date` ASC;");
					$tomorrow_events = $this->DB_QueryManage("$query= 1 ORDER BY `date` ASC;");
					$today_events = $this->DB_QueryManage("$query= 0 ORDER BY `date` ASC;");
					$last_events = $this->DB_QueryManage("$query< 0 ORDER BY `date` DESC;");
					$num_next = $next_events->num_rows;
					$num_tomorrow = $tomorrow_events->num_rows;
					$num_today = $today_events->num_rows;
					$num_last = $last_events->num_rows;
					$this->ln_3(5, "DB_NextLast num_next=$num_next");
					$this->ln_3(5, "DB_NextLast num_tomorrow=$num_tomorrow");
					$this->ln_3(5, "DB_NextLast num_today=$num_today");
					$this->ln_3(5, "DB_NextLast num_last=$num_last");
					$the_next = null;
					$the_tomorrow = null;
					$the_today = null;
					$the_last = null;
					if($num_next > 0) {
						$the_next = $next_events->fetch_object();
					}
					if($num_tomorrow > 0) {
						$the_tomorrow = $tomorrow_events->fetch_object();
					}
					if($num_today > 0) {
						$the_today = $today_events->fetch_object();
					}
					if($num_last > 0) {
						$the_last = $last_events->fetch_object();
					}
					$next_events->close();
					$tomorrow_events->close();
					$today_events->close();
					$last_events->close();
					//
					$back = new stdClass();
					$back->special = "";// this is for 'today' or 'tomorrow'
					$back->when = null;
					$next_txt = "next";
					$last_txt = "last";
					//
					if($the_today !== null) {
						$this->ln_3(6, "DB_NextLast is today");
						if($next) {
							$back->what = $this->HarperCollins($next_txt, $female);
						} else {
							$back->what = $this->HarperCollins($last_txt, $female);
						}
						$back->special = $this->HarperCollins("today");
						$back->when = $this->ConvertDate($the_today->date);
					} elseif($the_tomorrow !== null && $next) {
						$this->ln_3(6, "DB_NextLast is tomorrow");
						$back->what = $this->HarperCollins($next_txt, $female);
						$back->special = $this->HarperCollins("tomorrow");
						$back->when = $this->ConvertDate($the_tomorrow->date);
					} elseif($the_next !== null && $next) {
						$this->ln_3(6, "DB_NextLast is next");
						$back->what = $this->HarperCollins($next_txt, $female);
						$back->when = $this->ConvertDate($the_next->date);
					} elseif($the_last !== null) {
						$this->ln_3(6, "DB_NextLast is last");
						$back->what = $this->HarperCollins($last_txt, $female);
						$back->when = $this->ConvertDate($the_last->date);
					} else {
						$this->ln_3(6, "DB_NextLast empty");
						$back->what = "";
					}
					if($back->when !== null) {
						$back->when->month = $this->Months($back->when->month);
					}
					//
					$this->ln_3(5, "DB_NextLast what=$back->what");
					$this->ln_3(5, "DB_NextLast special=$back->special");
					if($back->when !== null) {
						$this->ln_3(5, "DB_NextLast when year ={$back->when->year}");
						$this->ln_3(5, "DB_NextLast when month={$back->when->month}");
						$this->ln_3(5, "DB_NextLast when day  ={$back->when->day}");
					} else {
						$this->ln_3(5, "DB_NextLast when=''");
					}
					$this->ln_3(6, "DB_NextLast end");
					return $back;
				}
			//
				/*** get total count of entries ***/
				public function DB_GetCount($tables) {
					$this->ln_3(6, "DB_GetCount($tables)");
					$query = "";
					if(!is_array($tables)) {
						$query = "SELECT COUNT(*) AS `the_count` FROM `$tables`";
					} elseif(count($tables) == 1) {
						$query = "SELECT COUNT(*) AS `the_count` FROM `$tables[0]`";
					} else {
						$basequery = "SELECT COUNT(*) AS `count` FROM `";
						for($i = 0; $i < count($tables); $i++) {
							if($query != "") {
								$query .= "UNION ALL ";
							}
							$query .= "$basequery{$tables[$i]}` ";
						}
						$query = "SELECT sum(a.count) AS the_count FROM ($query) a";
					}
					$count = $this->DB_QueryManage($query);
					$fetch_count = $count->fetch_object();
					$count->close();
					$the_count = $fetch_count->the_count;
					$this->ln_3(6, "DB_GetCount end");
					return $the_count;
				}
			//
				/*** optimize tables ***/
				public function DB_Optimize($tables) {
					if(!is_array($tables)) {
						$this->DB_QueryManage("OPTIMIZE TABLE `$table`");
					} else {
						for($i = 0; $i < count($tables); $i++) {
							$this->DB_QueryManage("OPTIMIZE TABLE `{$tables[$i]}`");
						}
					}
				}
			//
				/*** SQL sort alpha ***/
				public function DB_SortAlpha($field, $language = "") {
					$this->ln_3(6, "DB_SortAlpha($field)");
					$nodetfield = "${field}_nodet";
					$back = "";
					//// 3-letter words
					$back .= "IF(";
					$back .= "LEFT(`$field`, 4) = 'The ' ";
					$back .= "OR ";
					$back .= "LEFT(`$field`, 4) = 'Les ' ";
					$back .= "OR ";
					$back .= "LEFT(`$field`, 4) = 'Der ' ";
					$back .= "OR ";
					$back .= "LEFT(`$field`, 4) = 'Das ' ";
					if($language == "german") {
						$back .= "OR ";
						$back .= "LEFT(`$field`, 4) = 'Die ' ";
					}
					$back .= ", ";
					$back .= "MID(`$field`, 5),\n";
					//// 2-letter words
					$back .= "	";
					$back .= "IF( ";
					$back .= "LEFT(`$field`, 3) = 'Le ' ";
					$back .= "OR ";
					$back .= "LEFT(`$field`, 3) = 'La ' ";
					$back .= "OR ";
					$back .= "LEFT(`$field`, 3) = 'El ' ";
					if($language == "italian") {
						$back .= "OR ";
						$back .= "LEFT(`$field`, 3) = 'Il ' ";
					}
					$back .= "OR ";
					$back .= "LEFT(`$field`, 3) = 'An ' ";
					$back .= ", ";
					$back .= "MID(`$field`, 4),\n";
					//// single-letter character
					if($language == "english") {
						$back .= "		";
						$back .= "IF(";
						$back .= "LEFT(`$field`,2) = 'A '";
						$back .= ", ";
						$back .= "MID(`$field`,3),\n";
					}
					//// Coded characters
					$back .= "			";
					$back .= "IF(";
					$back .= "LEFT(`$field`, 8) = 'L\\\\&#039;'";
					$back .= ", ";
					$back .= "MID(`$field`, 9),\n";
					$back .= "				";
					$back .= "IF(";
					$back .= "LEFT(`$field`, 7) = 'L&#039;'";
					$back .= ", ";
					$back .= "MID(`$field`, 8), `$field` )\n";
					$back .= "			)\n";
					if($language == "english") {
						$back .= "		)\n";
					}
					$back .= "	)\n";
					$back .= ") AS `$nodetfield`\n";
					//$this->ln_3(5, "DB_SortAlpha: $back");
					$this->ln_3(6, "DB_SortAlpha end");
					return $back;
				}
			//
				/*** SQL sort num ***/
				public function DB_OrderAlpha($field, $way = "ASC") {
					$this->ln_3(6, "DB_OrderAlpha($field, $way)");
					$field = "${field}_nodet";
					if($way == "ASC") {
						$noway = "DESC";
					} else {
						$noway = "ASC";
					}
					$back = "";
					$back .= "`$field` IS NULL $noway, ";
					$back .= "`$field` = '' $noway, ";
					$back .= "SUBSTRING_INDEX(`$field`,' ',1) + 0 > 0 $noway, ";
					$back .= "SUBSTRING_INDEX(`$field`,' ',1) + 0 $way, ";
					$back .= "`$field` $way";
					//$this->ln_3(5, "DB_OrderAlpha: $back");
					$this->ln_3(6, "DB_OrderAlpha end");
					return $back;
				}
			//
				/*** SQL query with Sort+Order alpha ***/
				public function DB_QueryAlpha($table, $field, $way = "ASC", $language = "") {
					$this->ln_3(6, "DB_QueryAlpha($table, $field, $way)");
					$query = "";
					$query .= "SELECT *, ";
					$query .= $this->DB_SortAlpha($field, $language);
					$query .= "FROM `$table` ";
					$query .= "ORDER BY ";
					$query .= $this->DB_OrderAlpha($field, $way);
					return $this->DB_QueryManage($query);
				}
			//
				//// SQL query with sort and order improved
				public function DB_QueryXi($table, $fields, $language = "") {
					$this->ln_3(6, "DB_QueryXi($table)");
					$query = "";
					$queryselect  = "";
					$queryselect .= "SELECT *";
					$queryorder  = "";
					$queryorder .= "ORDER BY ";
					foreach($fields as $field => $way) {
						if($queryorder != "ORDER BY ") {
							$queryorder .= ", ";
						}
						if(substr($way, 0, 1) == "a") {
							$queryselect .= ", " . $this->DB_SortAlpha($field, $language);
							$way = substr($way, 1);
							if($way == "") {
								$way = "ASC";
							}
							$queryorder .= $this->DB_OrderAlpha($field, $way);
						} else {
							if($way == "") {
								$way = "ASC";
							}
							$queryorder .= " `$field` $way";
						}
					}
					$queryselect .= "FROM `$table`";
					$query = "$queryselect $queryorder";
					return $this->DB_QueryManage($query);
				}
			//
			//
		//
			/*** convert STUFF ***/
				/*** SQL2URL: from SQL to HTML readonly with conversion of links ***/
				public function SQL2URL($text) {
					$this->ln_3(6, "SQL2URL(...)");
					$back = $text;
					$back = preg_replace("/(https?:\/\/[^ \n<>]+)/", '<a target="_blank" href="\1" title="\1">\1</a>', $back);
					return $back;
				}
			//
				/*** SQL2field: from SQL to HTML field ***/
				public function SQL2field($text) {
					$this->ln_3(6, "SQL2field()");
					$back = htmlspecialchars_decode($text, ENT_NOQUOTES);
					return $back;
				}
			//
				/*** field2SQL: from field to SQL ***/
				public function field2SQL($text) {
					$this->ln_3(6, "field2SQL()");
					$back = $text;
					$back = htmlentities($back, ENT_QUOTES, "UTF-8");
					$back = stripslashes($back);
					return $back;
				}
			//
				/*** filename2SQL: from filename to SQL ***/
				public function filename2SQL($text) {
					$this->ln_3(6, "filename2SQL()");
					$ext     = $this->GetExt($text);
					$without = $this->WoExt($text);
					$back = $without;
					$back = preg_replace("/\r?\n/", "", $back);
					$back = $this->field2SQL($back);
					$back = preg_replace("/&([a-zA-Z])[a-z]+;/", '\1', $back);
					$back = $this->SQL2field($back);
					return "$back$ext";
				}
			//
				/*** SQL2txtarea: from SQL to textarea ***/
				public function SQL2txtarea($text) {
					$this->ln_3(6, "SQL2txtarea()");
					$back = $text;
					$back = preg_replace("/<br\s*\/?>/i", "", $back);/* PHP_EOL ? */
					$back = $this->SQL2field($back);
					return $back;
				}
			//
				/*** txtarea2SQL: from textarea to SQL ***/
				public function txtarea2SQL($text) {
					$this->ln_3(6, "txtarea2SQL()");
					$back = $this->field2SQL($text);
					$back = preg_replace("/(\r?\n)*$/", "", $back);
					$back = nl2br($back);
					return $back;
				}
			//
				/*** SQL2itemize: from SQL to textarea ***/
				public function SQL2itemize($text) {
					$this->ln_3(6, "SQL2itemize()");
					$this->ln_3(2, "SQL2itemize not tested");
					$back = $text;
					$back =  str_replace("</li>\n<li>", "\r\n", $back);
					$back = preg_replace("/<\/li>$/",   "",     $back);
					$back = preg_replace("/^<li>/",     "",     $back);
					$back = $this->SQL2field($back);
					return $back;
				}
			//
				/*** itemize2SQL: from textarea to SQL */
				public function itemize2SQL($text) {
					$this->ln_3(6, "itemize2SQL()");
					$this->ln_3(2, "itemize2SQL not tested");
					$back = $text;
					$back = $this->field2SQL($back);
					$br = array("\r\n", "\n", "\r");
					$back =  str_replace($br,   "</li>\n<li>", $back);
					$back = preg_replace("/^/", "<li>",        $back);
					$back = preg_replace("/$/", "</li>",       $back);
					return $back;
				}
			//
				/*** SQL2paragraph: from SQL to textarea ***/
				public function SQL2paragraph($text, $class = "") {
					$this->ln_3(6, "SQL2paragraph()");
					if($class != "") {
						$this->ln_3(2, "SQL2paragraph option class deprecated");
					}
					$back = $text;
					$back =  str_replace("</p>",        PHP_EOL, $back);
					$back =  str_replace("<p>",         "",      $back);
					$back = preg_replace("/<p [^>]*>/", "",      $back);
					$back =  str_replace("<br />",      "",      $back);
					$back = preg_replace("/\n$/",       "",      $back );
					$back = $this->SQL2field($back);
					return $back;
				}
			//
				/*** paragraph2SQL: from textarea to SQL ***/
				public function paragraph2SQL($text, $class = "") {
					$this->ln_3(6, "paragraph2SQL()");
					$back = $text;
					$back = $this->field2SQL($back);
					$tag = "";
					if($class != "") {
						$tag = " class=\"$class\"";
					}
					$br2 = array("\r\n\r\n\r\n\r\n", "\n\n\n\n", "\r\r\r\r", "\r\n\r\n\r\n", "\n\n\n", "\r\r\r", "\r\n\r\n", "\n\n", "\r\r");
					$back = preg_replace("/(\r?\n)*$/", "",            $back);
					$back =  str_replace($br2,          "</p><p$tag>", $back);
					$back = nl2br($back);
					$back = preg_replace("/<\/p><p/", "</p>\n<p", $back);
					$back = "<p$tag>$back</p>";
					if($back == "<p$tag></p>") {
						$back = "";
					}
					return $back;
				}
			//
				//// SQLenum2text
				//$fields = implode(", ", fields(explode(",", $fields)));
			//
				//// SQLenum2check
				//$fields = fields(explode(",", $fields));
			//
				//// check2SQLenum
				//$fields = implode(",", $_POST["fields"]);
			//
			//
		//
			/*** extension STUFF ***/
				/*** get extension of filename ***/
				public function GetExt($text, $dot = true) {
					$this->ln_3(6, "GetExt($text)");
					$back = "";
					if($text != "") {
						preg_match("/\.[^.]*$/", $text, $back);
						$back = strtolower($back[0]);
					}
					if(!$dot) {
						$back = substr($back, 1);
					}
					$this->ln_3(5, "GetExt = $back");
					return $back;
				}
			//
				/*** filename without extension ***/
				public function WoExt($text) {
					$this->ln_3(6, "WoExt($text)");
					$back = "";
					if($text != "") {
						preg_match("/(.*)\.[^\.]*$/", $text, $back);
						$back = $back[1];
					}
					$this->ln_3(5, "WoExt = $back");
					return $back;
				}
			//
			//
		//
			/*** User rights and login STUFF ***/
				/*** check if is guest ***/
				public function UserIsGuest() {
					$this->ln_3(6, "UserIsGuest()");
					$back = !$this->UserIsLogged();
					$this->ln_3(5, "UserIsGuest: " . (int) $back);
					return $back;
				}
			//
				/*** check if is logged ***/
				public function UserIsLogged() {
					$this->ln_3(6, "UserIsLogged()");
					$back = (
							$_SESSION[$this->sex->session] == $this->sex->LoggedValue
							||
							$this->UserIsAdmin()
						);
					$this->ln_3(5, "UserIsLogged: " . (int) $back);
					return $back;
				}
			//
				/*** check if is admin ***/
				public function UserIsAdmin() {
					$this->ln_3(6, "UserIsAdmin()");
					$back = (
							$_SESSION[$this->sex->session] == $this->sex->AdminValue
							||
							$this->UserIsSuper()
						);
					$this->ln_3(5, "UserIsAdmin: " . (int) $back);
					return $back;
				}
			//
				/*** check if is GI ***/
				public function UserIsSuper() {
					$this->ln_3(6, "UserIsSuper()");
					$back = ($_SESSION[$this->sex->session] == $this->sex->SuperValue);
					$this->ln_3(5, "UserIsSuper: " . (int) $back);
					return $back;
				}
			//
				/*** if not allowed, make guest to index ***/
				public function NotAllowed(stdClass $varargin = NULL) {
					/*** varargin ***/
					$path = "";
					$admin = 0;
					$url = "index.php";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					$cond = $this->UserIsGuest();
					if($admin == 1) {
						$cond = $this->UserIsAdmin();
					} elseif($admin == 2) {
						$cond = $this->UserIsSuper();
					}
					if($cond) {
						if($path != "") {
							$url = "$path/$url";
						}
						$this->HeaderLocation($url);
					}
				}
			//
				/*** admin link (to login) ***/
				public function AdminLink(stdClass $varargin = NULL) {
					/*** varargin ***/
					$pic = "divers/admin.png";
					$css = "adm";
					$path = "/";
					$picpath = "/";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					$this->ln_3(6, "AdminLink(..., $pic, $css, $picpath)");
					if($path != "" && $path != "/") {
						$path = "$path/";
					}
					if($picpath != "" && $picpath != "/") {
						$picpath = "$picpath/";
					}
					if($this->UserIsLogged()) {
						$text = "Log out";
						$url = "logout";
						$this->ln_3(6, "AdminLink is logged");
					} else {
						$text = "Log in";
						$url = "login";
						$this->ln_3(6, "AdminLink guest");
					}
					$from = $this->FilePath;
					if($from != "" && $from != "/") {
						$from .= "/";
					} else {
						$from = "";
					}
					$from .= $this->FileName;
					$back = "";
					$back .= "<div class=\"$css\">\n";
					$back .= "<a title=\"$text\" href=\"$path$url.php?from=$from\">\n";
					$back .= "<img src=\"{$picpath}pictures/$pic\" alt=\"$text\" title=\"$text\" />\n";
					$back .= "</a><br />\n";
					$back .= "$text\n";
					$back .= "</div>\n";
					$this->ln_3(6, "AdminLink end");
					return $back;
				}
			//
				//// Choose cookie path (because localhost sucks)
				public function CookieRecipe() {
					$this->ln_3(6, "CookieRecipe()");
					$back = "/";
					if($this->LocalHost()) {
						//// Set it to root+1 dir
						$back = preg_replace("/^(\/[^\/]+)\/.*$/", '\1', $this->FilePath);
						$this->ln_3(3, "CookieRecipe back=$back");
					}
					return $back;
				}
			//
				/*** Write cookie ***/
				public function BakeCookie(stdClass $varargin = NULL) {
					/*** varargin ***/
					$cookie = "skipper";
					$delay = 3600 * 24 * 30;
					$chips = "";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					$this->ln_3(6, "BakeCookie()");
					if($cookie != "") {
						$this->ln_3(6, "BakeCookie mixing");
						$sugar = $this->sex->sugar;
						if($chips != "") {
							$this->ln_3(5, "BakeCookie adding some chips in sugar");
							$sugar .= " $chips";
						}
						$value = $_SESSION[$this->sex->session] . $sugar;
						$expire = time() + $delay;
						setcookie($cookie, $this->hache($value), $expire, $this->CookieRecipe());
					}
					$this->ln_3(6, "BakeCookie baked");
				}
			//
				/*** check login cookie ***/
				public function LoginCookie(stdClass $varargin = NULL) {
					/*** varargin ***/
					$redirect = "index.php";
					$cookiename = "skipper";
					$chips = "";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					$this->ln_3(6, "LoginCookie($cookiename)");
					if($this->UserIsGuest() && isset($_COOKIE[$cookiename])) {
						$this->ln_3(5, "LoginCookie user is guest and found cookie");
						$cookietxt = $_COOKIE[$cookiename];
						$this->ln_3(5, "LoginCookie fortune is $cookietxt");
						$sugar = $this->sex->sugar;
						if($chips != "") {
							$sugar .= " $chips";
						}
						if(
							$this->hache($this->sex->LoggedValue . $sugar, $cookietxt)
							|| $this->hache($this->sex->AdminValue  . $sugar, $cookietxt)
							|| $this->hache($this->sex->SuperValue  . $sugar, $cookietxt)
						) {
							$this->ln_3(5, "LoginCookie match found");
							if($this->hache($this->sex->LoggedValue . $sugar, $cookietxt)) {
								$_SESSION[$this->sex->session] = $this->sex->LoggedValue;
							}
							if($this->hache($this->sex->AdminValue . $sugar, $cookietxt)) {
								$_SESSION[$this->sex->session] = $this->sex->AdminValue;
							}
							if($this->hache($this->sex->SuperValue . $sugar, $cookietxt)) {
								$_SESSION[$this->sex->session] = $this->sex->SuperValue;
							}
							$this->BakeCookie($varargin);
							if($redirect != "") {
								$this->HeaderLocation($redirect);
							}
						} else {
							$this->ln_3(5, "LoginCookie not matching, invaliding...");
							setcookie($cookiename, "", time() - 3600, $this->CookieRecipe());
						}
					}
					$this->ln_3(6, "LoginCookie end");
				}
			//
				/*** Login successful ***/
				public function LoginSuccessful(stdClass $varargin = NULL) {
					/*** varargin ***/
					$level = 0;
					$redirect = "index.php";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					$this->ln_3(6, "LoginSuccessful(...)");
					if($level == 0) {
						$value = $this->sex->LoggedValue;
					} elseif($level == 1) {
						$value = $this->sex->AdminValue;
					} elseif($level == 2) {
						$value = $this->sex->SuperValue;
					} else {
						$this->FatalError("Level not approved");
					}
					$_SESSION[$this->sex->session] = $value;
					$this->BakeCookie($varargin);
					if($redirect != "") {
						$this->HeaderLocation($redirect);
					}
				}
			//
				/*** Invalidate cookie ***/
				public function BurnCookie(stdClass $varargin = NULL) {
					/*** varargin ***/
					$redirect = "index.php";
					$cookie = "skipper";
					$delay = 3600;
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					if($cookie != "") {
						setcookie($cookie, "", time() - $delay, $this->CookieRecipe());
					}
					if($redirect != "") {
						$this->HeaderLocation($redirect);
					}
				}
			//
				/*** Log out ***/
				public function LogOut(stdClass $varargin = NULL) {
					/*** varargin ***/
					$cookie = "skipper";
					$goto = "";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					if(isset($_GET["from"]) && $_GET["from"] != "") {
						$goto = $_GET["from"];
					} elseif($goto == "") {
						$goto = "index.php";
					}
					$session_name = session_name();
					$coo = new stdClass();
					$coo->cookie = $cookie;
					$coo->redirect = "";
					$_SESSION = array();
					$this->BurnCookie($coo);
					$coo->cookie = $session_name;
					$this->BurnCookie($coo);
					session_unset();
					session_destroy();
					$this->HeaderLocation($goto);
				}
			//
			//
		//
			/*** Loading files STUFF ***/
				/*** embed file (PDF) ***/
				public function EmbedFile($filename, stdClass $varargin = NULL) {
					/*** varargin ***/
					$alt_txt = "";
					$picthumb = false;
					$picsize = 0;
					$pdfwidth = 1000;
					$pdfheight = 1000;
					$funcpath = "";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					$thumbpath = "functions_local/thumb.php";
					if($funcpath != "") {
						$thumbpath = "$funcpath/$thumbpath";
					}
					$this->ln_3(6, "EmbedFile($filename, alt=$alt_txt)");
					if($alt_txt == "") {
						$this->ln_3(5, "EmbedFile alt_txt empty, setting filename");
						$alt_txt = $filename;
					}
					$back = "";
					$ext = $this->GetExt($filename);
					$this->ln_3(5, "EmbedFile ext=$ext");
					//if(!file_exists($filename)) {
						//$this->ln_3(6, "EmbedFile file not found, return alt_text");
						//return $alt_txt;
					//}
					if($ext == ".pdf") {
						$this->ln_3(6, "EmbedFile is PDF");
						$back .= "<embed src=\"$filename\" type=\"application/pdf\" width=\"$pdfwidth\" height=\"$pdfheight\" />\n";
					} else {
						$this->ln_3(6, "EmbedFile is *not* PDF");
						if($picthumb) {
							$filename = preg_replace("/^(\.\.\/)+/", "", $filename);
							$this->ln_3(6, "EmbedFile using thumbnail");
							$picfile = "$thumbpath?picpath=$filename";
							if($picsize > 0) {
								$picfile .= "&amp;max=$picsize";
							}
						} else {
							$picfile = $filename;
						}
						$back .= "<img src=\"$picfile\" alt=\"$alt_txt\" />\n";
					}
					$this->ln_3(6, "EmbedFile end");
					return $back;
				}
			//
				/*** load a file ***/
				public function LoadFile(stdClass $varargin) {
					/*** varargin ***/
					$reduce = true;
					foreach($varargin as $k => $v) {$$k = $v;}
					$mandatory = array(
						"fieldname",
						"filename",
						"path",
						"maxfilesize",
						"maximgsize",
						"querybound"
					);
					foreach($mandatory as $m) {
						if(!isset($$m)) {
							$this->ln_3(0, "LoadFile: $m required");
							exit;
						}
					}
					/*** /varargin ***/
					$this->ln_3(6, "LoadFile($fieldname, $filename, $path)");
					//// Use to add in form: enctype="multipart/form-data"
					//// Have to use it through try-catch
					$fullname = "$path/$filename";
					$this->ln_3(5, "LoadFile fullname=$fullname");
					$tmp      = $_FILES[$fieldname]["tmp_name"];
					$filesize = $_FILES[$fieldname]["size"];
					$this->ln_3(5, "LoadFile tmp_name=$tmp");
					$this->ln_3(5, "LoadFile filesize=$filesize");
					if(!is_uploaded_file($tmp)) {
						//// Not uploaded
						$this->ln_3(1, "LoadFile ERROR not uploaded");
						return;
					}
					
					if(file_exists($fullname)) {
						//// Already file
						$this->ln_3(1, "LoadFile ERROR $filename already exists");
						return;
					}
					
					if(!move_uploaded_file($tmp, $fullname)) {
						$this->ln_3(1, "LoadFile ERROR cannot move file");
						return;
					}
					
					if(!$reduce && $filesize > $maxfilesize) {
						//// Too big
						$this->ln_3(1, "LoadFile ERROR file too big");
						return;
					}

					// All OK, proceed
					if($reduce) {
						$thumbsup = new stdClass();
						$thumbsup->maxsize = $maximgsize;
						$thumbsup->picpath = $fullname;
						$thumbsup->thumbpath = $fullname;
						//$thumbsup->moveoriginal = false;
						if(!$this->CreateThumb($thumbsup)) {
							$this->ln_3(1, "LoadFile ERROR cannot create thumbnail");
							unlink($fullname);
							return;
						}
					}

					//// All OK, treat SQL
					$this->ln_3(6, "LoadFile to SQL");
					if(!$querybound->execute()) {
						$this->DB_DispErr($querybound);
						unlink($fullname);
					}
					//$id = $querybound->insert_id;
				}
			//
				/*** create a thumbnail file ***/
				public function CreateThumb(stdClass $varargin) {
					/*** varargin ***/
					$moveoriginal = false;
					foreach($varargin as $k => $v) {$$k = $v;}
					$mandatory = array(
						"maxsize",
						"picpath",
						"thumbpath"
					);
					foreach($mandatory as $m) {
						if(!isset($$m)) {
							$this->ln_3(0, "CreateThumb: $m required");
							exit;
						}
					}
					/*** /varargin ***/
					$t1 = true;
					$t2 = true;
					$t3 = true;
					$size = getimagesize($picpath);
					$width  = $size[0];
					$height = $size[1];
					$ratio = $maxsize;
					if($width >= $height) {
						$ratio /= $width;
					} else {
						$ratio /= $height;
					}
					if($ratio < 1) {
						$newwidth  = $ratio * $width;
						$newheight = $ratio * $height;
						$thumb = imagecreatetruecolor($newwidth, $newheight);
						$ext = $this->GetExt($picpath);
						ini_set("memory_limit", "-1");
						ini_set("gd.jpeg_ignore_warning", "1");
						switch($ext) {
							case ".jpg" :
							case ".jpeg":
								$img = imagecreatefromjpeg($picpath);
								$t1 = imagecopyresized($thumb, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
								if($moveoriginal) {
									$t3 = rename($picpath, $thumbpath);
									$t2 = imagejpeg($thumb, $picpath, 100);
								} else {
									$t2 = imagejpeg($thumb, $thumbpath, 100);
								}
								break;
							case ".png":
								$img = imagecreatefrompng($picpath);
								$t1 = imagecopyresized($thumb, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
								if($moveoriginal) {
									$t3 = rename($picpath, $thumbpath);
									$t2 = imagepng($thumb, $picpath);
								} else {
									$t2 = imagepng($thumb, $thumbpath);
								}
								break;
							case ".gif" :
								$img = imagecreatefromgif($picpath);
								$t1 = imagecopyresized($thumb, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
								if($moveoriginal) {
									$t3 = rename($picpath, $thumbpath);
									$t2 = imagegif($thumb, $picpath);
								} else {
									$t2 = imagegif($thumb, $thumbpath);
								}
								break;
							default :
								//throw new Exception( "Format de photo non-reconnu.<br />\n" );
								//$t1 = false;
								//// Do nothing and let it as it is
								break;
						}
						imagedestroy($img);
						imagedestroy($thumb);
					//} else {
						//$t1 = copy($picpath, $thumbpath);
					}
					return $t1 && $t2 && $t3;
				}
			//
			//
		//
			/*** Admin+server STUFF ***/
				/*** set file name ***/
				public function SetFileName() {
					$this->FileName = basename($_SERVER["SCRIPT_NAME"]);
					$this->ShortName = preg_replace("/\.php$/", "", $this->FileName);
				}
			//
				/*** set file path ***/
				public function SetFilePath() {
					$this->FilePath = dirname($_SERVER["SCRIPT_NAME"]);
				}
			//
				//// Il est la verite le chemin et la vie
				public function TruthWayLife() {
					$back = $this->rootPath;
					if($back != "") {
						$back .= "/";
					}
					$back .= "functions";
					return $back;
				}
			//
				/*** check has www. ***/
				public function check_www() {
					/*** add www if not present (optional) ***/
					/*** SERVER_NAME, HTTP_HOST give base name of website ***/
					/*** SCRIPT_URI             give whole address of current page ***/
					/*** Be sure that the whole address is kept ***/
					$this->ln_3(6, "check_www()");
					$this->ln_3(3, "please use rather a htaccess rewrite rule");
					$srv  = $_SERVER["SERVER_NAME"];
					if(!$this->LocalHost()) {
						$full = $_SERVER["SCRIPT_URI"];
						if(!preg_match("/www\./", $srv)) {
							$this->ln_3(5, "check_www old=$full");
							$full = preg_replace("/http:\/\//", "http://www.", $full);
							$this->ln_3(5, "check_www new=$full");
							$this->HeaderLocation($full);
						}
					}
				}
			//
				/*** Store2Session ***/
				public function Store2Session() {
					$remote = $_SERVER["HTTP_USER_AGENT"];
					/*** random ***/
					if(!isset($_SESSION["Ford"])) {
						$_SESSION["Ford"] = "Prefect";
					}
					/*
					if($_SESSION["language"] == "") {
						if(preg_match("/en/", $_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
							$_SESSION["language"] = "english";
						}
					}
					 */
					/*** IE ***/
					if(!isset($_SESSION["IE"])) {
						$_SESSION["IE"] = false;
						if(preg_match("/MSIE/i", $remote)) {
							$_SESSION["IE"] = true;
						}
					}
					if(!isset($_SESSION["AvailLangs"])) {
						$_SESSION["AvailLangs"] = array("english", $this->GetFrench());
					}
					$stats = $this->WhoAreYou();
					$_SESSION["browser"] = $stats->browser;
					$_SESSION["OS"]      = $stats->OS;
					/*** mobile ***/
					if(!isset($_SESSION["mobile"])) {
						$_SESSION["mobile"] = $stats->mobile;
					}
				}
			//
				/*** Header for new Location but Checks if error ***/
				public function HeaderLocation($location = "index.php") {
					$this->ln_3(6, "HeaderLocation($location)");
					if($location != "") {
						$count = $this->CountErrors();
						if($count) {
							$this->ln_3(1, "Could not relocate to $location due to previous errors");
							$this->HotBooty();
							$this->LogStack(1);
							$this->FinishHim();
						} else {
							header("Location: $location");
						}
						/*** exits anyway because I do not want to proceed further ***/
						exit();
					}
				}
			//
				/*** Header for refresh ***/
				public function HeaderRefresh($timeout, $url = "") {
					$this->ln_3(6, "HeaderRefresh($timeout, url=$url)");
					if($url != "") {
						$timeout = "$timeout; url=$url";
					}
					header("Refresh: $timeout");
				}
			//
				/*** hache passwords ***/
				public function hache($phrase, $compare = "") {
					$this->ln_3(6, "hache(phrase)");
					$hash = hash("sha512", $phrase);
					if($compare == "") {
						return $hash;
					} else {
						return $hash === $compare;
					}
				}
			//
				/*** identify client ***/
				public function WhoAreYou() {
					$this->ln_3(6, "WhoAreYou()");
					$back = new stdClass();
						/*** Get IP ***/
						$IP = $_SERVER["REMOTE_ADDR"];
						$back->IP = $IP;
						$this->ln_3(5, "WhoAreYou       IP=$IP");
					//
						/*** Get user agent ***/
						$useragent = $_SERVER["HTTP_USER_AGENT"];
						//// Mozilla/Opera (platform; OS) gecko browser
						//$useragent = preg_replace("/; rv:[0-9.]+/", $useragent);
						//$useragent = preg_replace("/U; /", $useragent);
						//$useragent = preg_replace("/; (en(-us)?|fr)/", $useragent);
							/*** platform ***/
							$platform = "unknown";
							if(preg_match("/macintosh/i", $useragent)) {
								$platform = "mac";
							} elseif(preg_match("/ipad/i", $useragent)) {
								$platform = "iPad";
							} elseif(preg_match("/iphone/i", $useragent)) {
								$platform = "iPhone";
							} elseif(preg_match("/X11/", $useragent) || preg_match("/linux/i", $useragent)) {
								$platform = "Linux";
							} elseif(preg_match("/googlebot/i", $useragent)) {
								$platform = "GoogleBot";
							}
							$back->platform = $platform;
							$this->ln_3(5, "WhoAreYou platform=$platform");
						//
							/*** OS ***/
							$OS = "unknown";
							if(preg_match("/mac os x/i", $useragent)) {
								$OS = "Mac OS X";
							} elseif(preg_match("/ubuntu/i", $useragent)) {
								$OS = "Ubuntu";
							} elseif(preg_match("/debian/i", $useragent)) {
								$OS = "Debian";
							} elseif(preg_match("/fedora/i", $useragent)) {
								$OS = "Fedora";
							} elseif(preg_match("/freebsd/i", $useragent)) {
								$OS = "FreeBSD";
							} elseif(preg_match("/windows nt 5.1/i", $useragent)) {
								$OS = "Windows XP";
							} elseif(preg_match("/windows nt 6.0/i", $useragent)) {
								$OS = "Windows Vista";
							} elseif(preg_match("/windows nt 6.1/i", $useragent)) {
								$OS = "Windows 7";
							}
							$back->OS = $OS;
							$this->ln_3(5, "WhoAreYou       OS=$OS");
						//
							/*** browser ***/
							$browser = "unknown";
							if(preg_match("/firefox/i", $useragent)) {
								$browser = "Firefox";
							} elseif(preg_match("/chrome/i", $useragent)) {
								$browser = "Chrome";
							} elseif(preg_match("/opera mobi/i", $useragent)) {
								$browser = "Mobile Opera";
							} elseif(preg_match("/opera mini/i", $useragent)) {
								$browser = "Opera Mini";
							} elseif(preg_match("/opera/i", $useragent)) {
								$browser = "Opera";
							} elseif(preg_match("/netscape/i", $useragent)) {
								$browser = "Netscape";
							} elseif(preg_match("/epiphany/i", $useragent)) {
								$browser = "Epiphany";
							} elseif(preg_match("/MSIE/", $useragent)) {
								$browser = "IE";
							} elseif(preg_match("/MSPIE/", $useragent)) {
								$browser = "Mobile IE";
							} elseif(preg_match("/android/i", $useragent)) {
								$browser = "Android";
							} elseif(preg_match("/elinks/i", $useragent)) {
								$browser = "ELinks";
							} elseif(preg_match("/links/i", $useragent)) {
								$browser = "Links";
							} elseif(preg_match("/lynx/i", $useragent)) {
								$browser = "Lynx";
							} elseif(preg_match("/nokia/i", $useragent)) {
								$browser = "Nokia";
							} elseif(preg_match("/psp/i", $useragent)) {
								$browser = "PSP";
							} elseif(preg_match("/konqueror/i", $useragent)) {
								$browser = "Konqueror";
							} elseif(preg_match("/mobile\/[0-9A-Za-z]+ safari/i", $useragent)) {
								$browser = "Mobile Safari";
							} elseif(preg_match("/safari/i", $useragent)) {
								$browser = "Safari";// safari must be at end
							}
							$back->browser = $browser;
							$this->ln_3(5, "WhoAreYou  browser=$browser");
						//
							/*** mobile? ***/
							//$back->mobile = preg_match("/mobile/i", $useragent);
							$back->mobile = (preg_match("/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i",$useragent)||preg_match("/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i",substr($useragent,0,4)));// from detectmobilebrowser.com/mobile
						//
						//
					//
					return $back;
				}
			//
				/*** add to stats (counter) ***/
				/*** Structure of DB: id, timestamp(not null default now()), IP, arch, OS, browser ***/
				public function Add2stats($whichDB = "") {
					$this->ln_3(6, "Add2stats($whichDB)");
					if(!isset($_SESSION["sheep"])) {
						$this->ln_3(6, "Add2stats new sheep");
						$_SESSION["sheep"] = 42;
						$stats = $this->WhoAreYou();
						$add2stats = $this->DB_QueryPrepare("INSERT INTO `" . $this->ddb->DBname . "`.`webstats` (`IP`, `platform`, `OS`, `browser`) VALUES(?, ?, ?, ?);");
						$add2stats->bind_param("ssss", $stats->IP, $stats->platform, $stats->OS, $stats->browser);
						$this->DB_ExecuteManage($add2stats);
					}
					$this->ln_3(6, "Add2stats end");
				}
			//
				/*** get stats ***/
				public function GetStats($DB) {
					$this->ln_3(6, "GetStats($DB)");
					$query0 = "SELECT";
					$query0 .= " COUNT(*) AS sum";
					$query0 .= ", COUNT(DISTINCT `IP`) AS computer";
					$query0 .= " FROM `" . $this->ddb->DBname . "`.`webstats`";
					//// query = query0 . "having datediff < 1y"
					//// having datediff < 1m
					//// having datediff < 1w
					//// having datediff < 1d
					//// having datediff < 1h
					//// request query
					//// query = query . "where platform = ..."
					//// where OS = ...
					//// where browser = ...
					$sql = $this->DB_QueryManage($query0);
					$results = $sql->fetch_object();
					$sql->close();
					$back = new stdClass();
					$back->sum = $results->sum;
					//$back->arch = new stdClass();
					//$back->OS = new stdClass();
					//$back->browser = new stdClass();
					$this->ln_3(3, "GetStats only with sum");
					return $back;
				}
			//
				/*** Print visits ***/
				public function PrintVisits($DB, stdClass $text = null) {
					$this->ln_3(6, "PrintVisits($DB)");
					$before = "";
					$after = "";
					if(isset($text->before)) {
						$before = $text->before;
					}
					if(isset($text->after)) {
						$after = $text->after;
					}
					$this->Add2stats($DB);
					return "<div id=\"visits\">$before{$this->GetStats($DB)->sum}$after</div>\n";
				}
			//
				/*** check localhost ***/
				public function LocalHost() {
					$this->ln_3(6, "LocalHost()");
					$local = ($_SERVER["SERVER_NAME"] == "localhost");
					$lan   = preg_match("/^192\.168\./", $_SERVER["SERVER_NAME"]);
					return ($local || $lan);
				}
			//
				//// get git versions
				public function git_st() {
					$lastdate = "log -1 --pretty=format:\"%cd\" --date=short";
					$lasthash = "log -1 --pretty=format:\"%h\"";
					$wdate = exec("git $lastdate");
					$whash = exec("git $lasthash");
					$fdate = exec("cd " . $this->TruthWayLife() . " && git $lastdate");// 2>&1
					$fhash = exec("cd " . $this->TruthWayLife() . " && git $lasthash");
					//$wstr = "site&nbsp;$wdate&nbsp;$whash";
					//$fstr = "func&nbsp;$fdate&nbsp;$fhash";
					$wstr = "site:$whash($wdate)";
					$fstr = "func:$fhash($fdate)";
					$git_str = "$wstr&nbsp;-&nbsp;$fstr";
					$back = "";
					$back .= "<img src=\"/functions/pics/git.png\" alt=\"git versions: $git_str\" title=\"$git_str\" />\n";
					return $back;
				}
			//
			//
		//
			/*** Text STUFF ***/
				/*** cardinal ***/
				public function cardinalize($number) {
					$this->ln_3(6, "cardinalize($number)");
					$this->ln_3(3, "cardinalize english only");
					//if($lang == $this->GetFrench()) {
						//$sup = "e";
						//if($number == 1) {
							//$sup = "er";
						//}
					//} else {
						/*** this is now only in english ***/
						$sup = "th";
						if($number%10 == 1 && $number != 11) {
							$sup = "st";
						} elseif($number%10 == 2 && $number != 12) {
							$sup = "nd";
						} elseif($number%10 == 3 && $number != 13) {
							$sup = "rd";
						}
					//}
					return "$number<sup>$sup</sup>";
				}
			//
				/*** make first letter upper case ***/
				public function HighFive($word) {
					$this->ln_3(6, "HighFive($word)");
					$back = strtoupper(substr($word, 0, 1)) . substr($word, 1);
					return $back;
				}
			//
				/*** Make marquee (text goes by) ***/
				public function Marquee($text, $url = "", $urltitle = "") {
					$this->ln_3(6, "Maqruee($text)");
					$back  = "";
					$back .= $this->HTMLblock("Marquee start");
					$back .= "<div class=\"marquee\">\n";
					$back .= "<span>\n";
					if($url != "") {
						$this->ln_3(5, "Marquee url=$url");
						if($urltitle = "") {
							$urltitle = $text;
						}
						$this->ln_3(5, "Marquee urltitle=$urltitle");
						$back .= "<a href=\"$url\" title=\"$urltitle\">";
					}
					$back .= $text;
					if($url != "") {
						$back .= "</a>\n";
					}
					$back .= "</span>\n";
					$back .= "</div>\n";
					$back .= $this->HTMLblock("Marquee finished");
					$this->ln_3(6, "Marquee end");
					return $back;
				}
			//
				//// Get first letter without determinant
				public function FirstLetter($text) {
					return preg_replace("/^(Les |The |Der |Das |Le |La |An |El |L'|L&#039;|L\\&#039;)?([^ ]).*$/i", "$2", $text);
				}
			//
			//
		//
			/*** Other STUFF ***/
				/*** return whole page ***/
				public function show($body, stdClass $varargin = NULL) {
					$this->ln_3(6, "show(body) DEPRECATED use echo");
					echo $body;
				}
			//
				/*** go home ***/
				public function GoHome(stdClass $varargin = NULL) {
					/*** varargin ***/
					$page = "index";
					$id = "";
					$title = "Up";
					$rootpage = "";
					$roottitle = "Home";
					$css = "home";
					$pic_up = "/pictures/GoHome/up.png";
					$pic_home = "/pictures/GoHome/home.png";
					$previous_id = 0;
					$previous_title = "Previous";
					$previous_pic = "/pictures/GoHome/pa.png";
					$next_id = 0;
					$next_title = "Next";
					$next_pic = "/pictures/GoHome/na.png";
					if($varargin !== NULL) {
						foreach($varargin as $k => $v) {$$k = $v;}
					}
					/*** /varargin ***/
					$back = "";
					$back .= $this->HTMLblock("GoHome start");
					$this->ln_3(6, "GoHome($page, $id, $title, $rootpage, $roottitle, $css, $pic_up, $pic_home, previous, next)");
					/*** go home ***/
						/*** root page ***/
						$rooturl = "";
						if($rootpage != "") {
							$rooturl = "index.php";
							if(preg_match("/\.\.(\/\.\.)*/", $rootpage)) {
								/*** check if page==".." ***/
								$rooturl = $rootpage;// maybe can get the whole address through a $_SERVER field
								//$url = $_SERVER["SCRIPT_URI"];
								//$url = preg_replace( "/\/[^/]*$/", "", $url );
								if(preg_match("/\.\.$/", $rooturl)) {
									$rooturl = "$rooturl/index.php";
								} elseif(preg_match("/\.\.\/$/", $rooturl)) {
									$rooturl = "${rooturl}index.php";
								}
							} else {
								$rooturl = "$rootpage.php";
								//if($preg_match("\.\.", $url)) {
									//$rooturl = "../$rooturl";
								//}
							}
						}
						$this->ln_3(5, "GoHome rooturl=$rooturl");
					//
						/*** id ***/
						$which = "";
						if($id != "") {
							$which = "?id=$id";
							$this->ln_3(5, "GoHome which=$which");
						}
					//
					$url = "";
					if($page == "..") {
						/*** check if page==".." ***/
						$url = "../index.php";// maybe can get the whole address through a $_SERVER field
						//$url = $_SERVER["SCRIPT_URI"];
						//$url = preg_replace( "/\/[^/]*$/", "", $url );
						//$url .= $which;
					} else {
						$url = "$page.php$which";
					}
					$this->ln_3(5, "GoHome url=$url");
					//
					$back .= "<div id=\"$css\">\n";
					//
						/*** Previous ***/
						if($previous_id > 0) {
							$this->ln_3(6, "GoHome previous start");
							/*** previous ***/
								/*** id ***/
								$previouswhich = "?id=$previous_id";
								$this->ln_3(5, "GoHome previous which=$previouswhich");
							//
							$back .= "<a href=\"$this->FileName$previouswhich\">";
							$back .= "<img class=\"chome\" title=\"$previous_title\" alt=\"$previous_title\" src=\"$previous_pic\" />";
							$back .= "</a>\n";
							$this->ln_3(5, "GoHome previous stop");
						}
					//
						/*** Up ***/
						$this->ln_3(6, "GoHome up");
						$back .= "<a href=\"$url\">";
						$back .= "<img class=\"chome\" title=\"$title\" alt=\"$title\" src=\"$pic_up\" />";
						$back .= "</a>\n";
					//
						/*** Home ***/
						$this->ln_3(6, "GoHome checking if has home");
						if($rooturl != "") {
							$this->ln_3(6, "GoHome E.T. phone home");
							$back .= "<a href=\"$rooturl\">";
							$back .= "<img class=\"chome\" title=\"$roottitle\" alt=\"$roottitle\" src=\"$pic_home\" />";
							$back .= "</a>\n";
						}
					//
						/*** Next ***/
						if($next_id > 0) {
							$this->ln_3(6, "GoHome next start");
							/*** next ***/
								/*** id ***/
								$nextwhich = "?id=$next_id";
								$this->ln_3(5, "GoHome next which=$nextwhich");
							//
							$back .= "<a href=\"$this->FileName$nextwhich\">";
							$back .= "<img class=\"chome\" title=\"$next_title\" alt=\"$next_title\" src=\"$next_pic\" />";
							$back .= "</a>\n";
							$this->ln_3(6, "GoHome next stop");
						}
					//
					$back .= "</div>\n";
					$back .= $this->HTMLblock("GoHome finished");
					$this->ln_3(6, "GoHome end");
					return $back;
				}
			//
				/*** Rest In Pieces, curl ***/
				public function RIP_curl($url) {
					$this->ln_3(6, "RIP_curl($url)");
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
					foreach(curl_getinfo($rip) as $key => $val) {
						if($key == "certinfo") {
							foreach($val as $kk => $vv) {
								$this->ln_3(5, "RIP_curl getinfo $key::$kk: $vv");
							}
						} else {
							$this->ln_3(5, "RIP_curl getinfo $key: $val");
						}
					}
					curl_close($rip);
					$this->ln_3(6, "RIP_curl end");
					return $ripcurl;
				}
			//
				/*** Convert array to table display ***/
				public function array2columns($data_array, $cols) {
					$this->ln_3(6, "array2columns(..., $cols)");
					$back = array();
					$N = count($data_array);
					if($N > 0) {
						$maxrow = 0;
						$maxcol = 0;
						if($cols > $N) {
							$maxrow = 1;
							$maxcol = $N;
						} else {
							$maxcol = $cols;
							$maxrow = floor($N / $maxcol) + ($N % $maxcol > 0);
						}
						$this->ln_3(5, "array2columns ($maxrow,$maxcol)");
						for($the_row = 0; $the_row < $maxrow; $the_row++) {
							$back[] = array();
							for($the_col = 0; $the_col < $maxcol; $the_col++) {
								$i = $the_col * $maxrow + $the_row;
								if($i < count($data_array)) {
									$this->ln_3(5, "array2columns $i = ($the_row,$the_col)");
									$back[$the_row][$the_col] = $data_array[$i];
								} else {
									$this->ln_3(5, "array2columns null ($the_row,$the_col)");
									$back[$the_row][$the_col] = null;
								}
							}
						}
					}
					return $back;
				}
			//
				/*** Send mail ***/
				public function SendMail(stdClass $varargin) {
					$this->ln_3(6, "SendMail(...)");
					/*** varargin ***/
					foreach($varargin as $k => $v) {$$k = $v;}
					$mandatory = array(
						"from",
						"to",
						"subject",
						"message"
					);
					foreach($mandatory as $m) {
						if(!isset($$m)) {
							$this->ln_3(0, "SendMail: $m required");
							exit;
						}
					}
					/*** /varargin ***/
					//// check if 'from' is valid email
					if(!preg_match("/^.*@.*\..*$/", $from)) {
						$this->ln_3(0, "SendMail: from email invalid");
					}
					//// check if 'to' is valid email
					if(!preg_match("/^.*@.*\..*$/", $to)) {
						$this->ln_3(0, "SendMail: to email invalid");
					}
					//// wrap message to 70
					$message = wordwrap($message, 70);
					//// send
					mail($to, $subject, $message, "From: $from");
					$this->ln_3(5, "SendMail done");
				}
			//
			//
		//
		//
		/*** end methods ***/
	//
/*** end class PhPage ***/
}

/* if(main) {
require("initLocal.php");
$test = new PhPage($initLocal);
$test->LogLevelUp(6);
$test->CSS_ppJump();
$test->SetTitle("test");
$test->HotBooty();
echo "<p>It works!</p>\n";
unset($test);
/**/
?>
