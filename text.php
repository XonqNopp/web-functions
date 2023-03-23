<?php
require("helper.php");


class TextHelper extends MyHelper {
	public $strLaTeX = "<span class=\"latex\">L<sup>a</sup>T<sub>e</sub>X</span>";

		/**
		 * get extension of filename
		 *
		 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
		 */
		public function getExt($filename, $bDot=true) {
			$this->logger->trace("getExt($filename)");

			if($filename == "") {
				return "";
			}

			$back = "";
			preg_match("/\.[^.]*$/", $filename, $back);
			$back = strtolower($back[0]);

			if(!$bDot) {
				$back = substr($back, 1);
			}

			$this->logger->debug("getExt($filename) = $back");
			return $back;
		}
	//
		// filename without extension
		public function woExt($filename) {
			$this->logger->trace("woExt($filename)");

			if($filename == "") {
				return "";
			}

			$back = "";
			preg_match("/(.*)\.[^\.]*$/", $filename, $back);
			$back = $back[1];
			$this->logger->debug("woExt($filename) = $back");
			return $back;
		}
	//
		// make first letter upper case
		public function highFive($word) {
			$this->logger->trace("highFive($word)");
			$back = strtoupper(substr($word, 0, 1)) . substr($word, 1);
			return $back;
		}
}


// singleton
$theTextHelper = TextHelper();
?>
