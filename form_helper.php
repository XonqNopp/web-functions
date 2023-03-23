<?php
require_once("helper.php");

require_once("body_helper.php");
require_once("database_helper.php");
require_once("language_helper.php");
require_once("time_helper.php");


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
        public function tag($method="post", $action=NULL, $leeloo=false, $more="") {
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

            if($leeloo) {
                $more .= " enctype=\"multipart/form-data\"";
            }

            return "<form method=\"$method\" action=\"$action\"$more>\n";
        }
    //
        // make first letter upper case
        public function highFive($word) {
            $this->logger->trace("highFive($word)");
            $back = strtoupper(substr($word, 0, 1)) . substr($word, 1);
            return $back;
        }
    //
        /**
         * Submit buttons.
         *
         * Args:
         *     condition (bool): if True, make update+delete buttons; if False make insert button.
         *     popUpText (string): text to prompt to user to ask confirmation for erasure. If NULL uses "delete".
         *     cancelUrl (string): if NULL uses index.php; if "file" stays on the same PHP file.
         *     closeTag (bool): True to append </form>
         *     add (string): text for the "insert" button; if NULL uses "add".
         *     css (string): CSS class.
         *     allowErase (bool): if False, no erase button.
         *
         * Returns:
         *     string: HTML for submit buttons
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.ElseExpression)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         */
        public function subButt($condition, $popUpText, $cancelUrl=NULL, $closeTag=True, $add=NULL, $css="SubButt", $allowErase=true) {
            global $theLogopedist;

            if($cancelUrl === NULL) {
                $cancelUrl = "index.php";
            } elseif($cancelUrl == "file") {
                $cancelUrl = $this->filename;
            }

            $wojs = $theLogopedist->translate("without") . " javascript";

            $back = "";
            $back .= $this->logger->htmlBlock("subButt start");
            $back .= "<div class=\"$css\">\n";

            if($condition) {
                $this->logger->trace("subButt editing");

                // update
                $update = $this->highFive($theLogopedist->translate("update"));
                $back .= "<input type=\"submit\" name=\"submit\" value=\"$update\"";
                $back .= " onclick=\"SubmitForm()\"";
                $back .= " disabled=\"disabled\"";
                $back .= " />\n";

                if($allowErase) {
                    // erase
                    $erase = $this->highFive($theLogopedist->translate("erase"));

                    // js needs quotes
                    if($popUpText === NULL || $popUpText == "") {
                        $popUpText = $erase;
                    }

                    $popUpText = "'" . addslashes($popUpText) . "'";
                    $this->logger->trace("subButt(popUpText=$popUpText)");

                    $back .= "<input type=\"submit\" name=\"erase\" value=\"$erase\" onclick=\"return ConfirmErase($popUpText";
                    if(!$theLogopedist->checkSessionLang("english")) {
                        $back .= ", false";
                    }
                    $back .= ")\" />\n";
                }

            } else {
                $this->logger->trace("subButt new entry");

                // add
                if($add === NULL) {
                    $add = $this->highFive($theLogopedist->translate("add"));
                }
                $back .= "<input type=\"submit\" name=\"submit\" value=\"$add\" onclick=\"SubmitForm()\" />\n";
            }

            // reset
            $reset  = $this->highFive($theLogopedist->translate("reset"));
            $back .= "<input type=\"reset\" value=\"$reset\" onclick=\"ResetForm()\" />\n";

            // cancel
            $cancel = $this->highFive($theLogopedist->translate("cancel"));
            $back .= "<input type=\"button\" name=\"cancel\" value=\"$cancel\" onclick=\"window.location='$cancelUrl';\" />\n";

            global $theBodyBuilder;
            $back .= "<noscript>" . $theBodyBuilder->anchor($cancelUrl, "$cancel $wojs", true) . "</noscript>\n";

            $back .= "</div>\n";

            if($closeTag) {
                $this->logger->trace("subButt /form");
                $back .= "</form>\n";
            }

            $back .= $this->logger->htmlBlock("subButt finished");
            return $back;
        }
}


// singleton
$theFormHelper = new FormHelper();
?>
