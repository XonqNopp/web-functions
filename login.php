<?php
require("helper.php");
require("body.php");
require("cookie.php");
require("crypto.php");
require("html.php");


$kGuest = 0;
$kUser = 1;
$kAdmin = 2;
$kSuper = 3;


class LoginHelper extends MyHelper {
	private $sex = NULL;
	private $cookie = "skipper";
	private $userLevel;

	/**
	 * Setup
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function setup($sex, $filename) {
		global $kGuest;
		$this->userLevel = $kGuest;

		$this->sex = $sex;

		if(!isset($_SESSION[$this->sex->session]) || $_SESSION[$this->sex->session] === NULL || $_SESSION[$this->sex->session] == "") {
			$_SESSION[$this->sex->session] = $this->sex->GuestValue;
		}

		$this->eatCookie($filename);
	}

		/**
		 * Set user level from session.
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public function setUserLevel() {
			switch($_SESSION[$this->sex->session]) {
				case $this->sex->LoggedValue:
					global $kUser;
					$this->userLevel = $kUser;
					break;

				case $this->sex->AdminValue:
					global $kAdmin;
					$this->userLevel = $kAdmin;
					break;

				case $this->sex->SuperValue:
					global $kSuper;
					$this->userLevel = $kSuper;
					break;

				default:
					global $kGuest;
					$this->userLevel = $kGuest;
					break;
			}

			// Update admin flag for logger
			$this->logger->userIsAdmin($this->userIsAdmin);
		}

		public function userIsGuest() {
			global $kGuest;
			return $this->userLevel == $kGuest;
		}
	//
		public function userIsLogged() {
			global $kUser;
			return $this->userLevel >= $kUser;
		}
	//
		public function userIsAdmin() {
			global $kAdmin;
			return $this->userLevel >= $kAdmin;
		}
	//
		public function userIsSuper() {
			global $kSuper;
			return $this->userLevel >= $kSuper;
		}
	//
		// if not allowed, make guest to index
		public function notAllowed($url="index.php", $admin=0) {
			$cond = $this->userIsGuest();
			if($admin == 1) {
				$cond = $this->userIsAdmin();
			} elseif($admin == 2) {
				$cond = $this->userIsSuper();
			}

			if(!$cond) {
				return;
			}

			global $theHtmlHelper;
			$theHtmlHelper->headerLocation($url);
		}
	//
		// admin link (to login)
		public function adminLink($pic=NULL, $css="adm", $logPagePrefix="/") {
			$this->logger->trace("adminLink(pic=$pic, css=$css, logPagePrefix=$logPagePrefix)");

			global $theBodyHelper;

			$text = "Log in";
			$url = "login";
			if($this->userIsLogged()) {
				$text = "Log out";
				$url = "logout";
				$this->logger->trace("adminLink is logged");
			}

			$from = "";
			if($this->filePath != "" && $this->filePath != "/") {
				$from = $this->filePath . "/";
			}
			$from .= $this->filename;

			$img = "";
			if($pic !== NULL) {
				$img .= $theBodyHelper->anchor(
					"$logPagePrefix$url.php?from=$from",
					"<img src=\"$pic\" alt=\"$text\" title=\"$text\" />",
					$text
				);
				$img .= "<br />\n";
			}

			$back = "<div class=\"$css\">\n";
			$back .= $img;
			$back .= $theBodyHelper->anchor("$logPagePrefix$url.php?from=$from", $text);
			$back .= "</div>\n";
			return $back;
		}
	//
		/**
		 * Write cookie
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		private function bakeCookie() {
			$this->logger->trace("bakeCookie()");
			global $theCookieHelper;
			global $theCryptoHelper;
			$theCookieHelper->bake(
				$this->cookie,
				$theCryptoHelper->hache($_SESSION[$this->sex->session] . $this->sex->sugar)
			);
		}
	//
		/**
		 * check login cookie
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public function eatCookie($redirect="index.php") {
			$this->logger->trace("cookie($redirect)");

			if(!$this->userIsGuest() || !isset($_COOKIE[$this->cookie])) {
				return;
			}

			global $theCryptoHelper;

			$this->logger->debug("loginCookie user is guest and found cookie");
			$cookietxt = $_COOKIE[$this->cookie];
			$this->logger->debug("loginCookie fortune is $cookietxt");

			$sugar = $this->sex->sugar;

			$bBakeNburn = false;

			$value = NULL;
			if($theCryptoHelper->hache($this->sex->LoggedValue . $sugar, $cookietxt)) {
				$value = $this->sex->LoggedValue;

			} elseif($theCryptoHelper->hache($this->sex->AdminValue . $sugar, $cookietxt)) {
				$value = $this->sex->AdminValue;

			} elseif($theCryptoHelper->hache($this->sex->SuperValue . $sugar, $cookietxt)) {
				$value = $this->sex->SuperValue;

			}

			if($value !== NULL) {
				$_SESSION[$this->sex->session] = $value;
				$this->setUserLevel();

				$bBakeNburn = true;
			}

			if($bBakeNburn) {
				$this->logger->debug("loginCookie match found");
				$this->bakeCookie();

				if($redirect == "") {
					return;
				}

				global $theHtmlHelper;
				$theHtmlHelper->headerLocation($redirect);
				return;
			}

			$this->logger->debug("loginCookie not matching, invaliding...");
			global $theCookieHelper;
			$theCookieHelper->burn($this->cookie);
		}
	//
		/**
		 * Login successful
		 *
		 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
		 * @SuppressWarnings(PHPMD.Superglobals)
		 * @SuppressWarnings(PHPMD.ElseExpression)
		 */
		public function loginSuccessful($level=0, $redirect="index.php", $bWriteCookie=True) {
			$this->logger->trace("loginSuccessful(level=$level, redirect=$redirect, bWriteCookie=$bWriteCookie)");

			if($level == 0) {
				$value = $this->sex->LoggedValue;

			} elseif($level == 1) {
				$value = $this->sex->AdminValue;

			} elseif($level == 2) {
				$value = $this->sex->SuperValue;

			} else {
				$this->logger->fatal("Level not approved");
			}

			$_SESSION[$this->sex->session] = $value;
			$this->setUserLevel();

			if($bWriteCookie) {
				$this->bakeCookie();
			}

			if($redirect == "") {
				return;
			}

			global $theHtmlHelper;
			$theHtmlHelper->headerLocation($redirect);
		}
	//
		/**
		 * Log out
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public function logOut($goto="index.php") {
			if(isset($_GET["from"]) && $_GET["from"] != "") {
				$goto = $_GET["from"];
			}

			$sessionName = session_name();
			$_SESSION = array();

			global $theCookieHelper;
			$theCookieHelper->burn($this->cookie);
			$theCookieHelper->burn($sessionName);

			session_unset();
			session_destroy();

			global $theHtmlHelper;
			$theHtmlHelper->headerLocation($goto);
		}
}


// singleton
$theLoginHelper = LoginHelper();
?>
