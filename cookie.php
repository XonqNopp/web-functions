<?php
require("helper.php");
require("crypto.php");
require("server.php");


// login, language
class CookieHelper extends MyHelper {
	private $filePath = NULL;

	public function setup($filePath) {
		$this->filePath = $filePath;
	}

		// Choose cookie path (because localhost sucks)
	// TODO confirm with multiple localhost???
		public function recipe() {
			$this->logger->trace("recipe()");

			global $theServerHelper;
			if(!$theServerHelper->isLocalhost()) {
				return "/";
			}

			// Set it to root+1 dir
			$back = preg_replace("/^(\/[^\/]+)\/.*$/", '\1', $this->filePath);
			$this->logger->info("recipe back=$back");
			return $back;
		}
	//
		// Write
		public function bake($cookie, $value, $expire=NULL) {
			if($cookie === NULL || $cookie == "") {
				return;
			}

			if($expire === NULL) {
				$expire = time() + 3600 * 24 * 300;  // 300 days
			}

			setcookie($cookie, $value, $expire, $this->recipe());
		}

		// Invalidate
		public function burn($cookie) {
			$this->bake($cookie, "", time() - 3600);
		}
}


// singleton
$theCookieHelper = CookieHelper();
?>
