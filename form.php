<?php
require("helper.php");
require("body.php");
require("database.php");
require("language.php");
require("text.php");
require("time.php");


class FormHelper extends MyHelper {
	private $filename;

	public function setup($filename) {
		$this->filename = $filename;
	}

		/**
		 * Get the tag for a form.
		 *
		 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
		 */
		public function tag($method="post", $action=NULL, $bLeeloo=false, $more="") {
			if($action === NULL) {
				$action = $this->filename;
			}

			if($method != "post" && $method != "get") {
				$this->logger->warning("formTag method not valid, default post");
				$method = "post";
			}

			$this->logger->trace("formTag($method, $action)");

			if($more != "") {
				$more = " $more";
			}

			if($bLeeloo) {
				$more .= " enctype=\"multipart/form-data\"";
			}

			return "<form method=\"$method\" action=\"$action\"$more>\n";
		}
	//
		/**
		 * submit buttons
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
		 * @SuppressWarnings(PHPMD.ElseExpression)
		 */
		public function subButt($condition, $popUpText, $cancelUrl=NULL, $bCloseTag=True, $add=NULL, $css="SubButt", $bEraseAllowed=true) {
			global $theLanguageHelper;
			global $theTextHelper;

			if($cancelUrl === NULL) {
				$cancelURL = "index.php";
			} elseif($cancelUrl == "file") {
				$cancelUrl = $this->filename;
			}

			// js needs quotes
			$popUpText = "'" . addslashes($popUpText) . "'";

			$this->logger->trace("subButt(popUpText=$popUpText)");

			$wojs = $theLanguageHelper->translate("without") . " javascript";

			$back = "";
			$back .= $this->logger->htmlBlock("subButt start");
			$back .= "<div class=\"$css\">\n";

			if($condition) {
				$this->logger->trace("subButt editing");

				// update
				$update = $theTextHelper->highFive($theLanguageHelper->translate("update"));
				$back .= "<input type=\"submit\" name=\"submit\" value=\"$update\"";
				$back .= " onclick=\"SubmitForm()\"";
				$back .= " disabled=\"disabled\"";
				$back .= " />\n";

				if($bEraseAllowed) {
					// erase
					$erase  = $theTextHelper->highFive($theLanguageHelper->translate("erase"));
					$back .= "<input type=\"submit\" name=\"erase\" value=\"$erase\" onclick=\"return ConfirmErase($popUpText";
					global $theLanguageHelper;
					if(!$theLanguageHelper->checkSessionLang("english")) {
						$back .= ", false";
					}
					$back .= ")\" />\n";
				}

			} else {
				$this->logger->trace("subButt new entry");

				// add
				if($add === NULL) {
					$add = $theTextHelper->highFive($theLanguageHelper->translate("add"));
				}
				$back .= "<input type=\"submit\" name=\"submit\" value=\"$add\" onclick=\"SubmitForm()\" />\n";
			}

			// reset
			$reset  = $theTextHelper->highFive($theLanguageHelper->translate("reset"));
			$back .= "<input type=\"reset\" value=\"$reset\" onclick=\"ResetForm()\" />\n";

			// cancel
			$cancel = $theTextHelper->highFive($theLanguageHelper->translate("cancel"));
			$back .= "<input type=\"button\" name=\"cancel\" value=\"$cancel\" onclick=\"window.location='$cancelURL';\" />\n";

			global $theBodyHelper;
			$back .= "<noscript>" . $theBodyHelper->anchor($cancelUrl, "$cancel $wojs", true) . "</noscript>\n";

			$back .= "</div>\n";

			if($bCloseTag) {
				$this->logger->trace("subButt /form");
				$back .= "</form>\n";
			}

			$back .= $this->logger->htmlBlock("subButt finished");
			return $back;
		}
}


// singleton
$theFormHelper = FormHelper();
?>
