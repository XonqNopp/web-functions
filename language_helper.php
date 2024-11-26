<?php
require_once("helper.php");

require_once("body_helper.php");
require_once("cookie_helper.php");
require_once("translators_helper.php");


class LanguageHelper extends MyHelper {
    private $filename = "";

    private $session = "language";

    private $english = "english";
    private $french = "francais";
    private $wolof = "wolof";
    private $mandinka = "mandinka";

    public $long = array();  // define in init

    /**
     * Setup
     *
     * Args:
     *     filename (string)
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function setup($filename, $availLangs=array()) {
        $this->filename = $filename;

        $this->long = array(
            "english" => $this->english,
            "french" => $this->french,
            "wolof" => $this->wolof,
            "mandinka" => $this->mandinka,
        );

        if($availLangs !== NULL && is_array($availLangs) && $availLangs !== array()) {
            $this->setAvailLangs($availLangs);

            if(!isset($_SESSION["language"])) {
                $this->logger->trace("language not defined, making default: {$availLangs[0]}");
                $_SESSION["language"] = $availLangs[0];
            }
        }

        if(!isset($_SESSION["AvailLangs"])) {
            // Default to english+french
            $_SESSION["AvailLangs"] = array($this->english, $this->french);
        }

        // language (cookie before writing output)
        $this->languageCookieGet();
    }

        /**
         * Set available languages
         *
         * Args:
         *     languages (array)
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function setAvailLangs($languages) {
            $this->logger->trace("setAvailLangs(...)");

            if(!is_array($languages) || $languages === array()) {
                return;
            }

            $new = array();
            $numLanguages = count($languages);
            for($iLang = 0; $iLang < $numLanguages; $iLang++) {
                $language = $languages[$iLang];
                if(array_key_exists($language, $this->long)) {
                    $language = $this->long[$language];
                }
                $new[] = $language;
            }

            if(isset($_SESSION["AvailLangs"]) && $new == $_SESSION["AvailLangs"]) {
                return;
            }

            $printNew = implode(', ', $new);
            $this->logger->debug("setAvailLangs: new=$printNew");

            $_SESSION["AvailLangs"] = $new;

            if(!isset($_SESSION["language"]) || !$this->inAvailLangs($_SESSION["language"])) {
                $this->changeSessionLang($new[0]);
            }
        }
    //
        /**
         * Test if language is in available languages.
         *
         * Args:
         *     language (string): the desired language
         *
         * Returns:
         *     (bool) True if in array
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        private function inAvailLangs($language) {
            if($language === NULL) {
                return false;
            }

            if(!isset($_SESSION["AvailLangs"])) {
                return false;
            }

            if($_SESSION["AvailLangs"] === NULL) {
                return false;
            }

            return in_array($language, $_SESSION["AvailLangs"]);
        }
    //
        /**
         * Get language string
         *
         * Args:
         *     language (string): the desired language
         *
         * Returns:
         *     (string) language name if available
         */
        private function getLanguage($language) {
            if(!$this->inAvailLangs($language)) {
                return NULL;
            }

            return $language;
        }
    //
        // Get french string
        public function getFrench() {
            return $this->getLanguage($this->french);
        }
    //
        // Get wolof string
        public function getWolof() {
            return $this->getLanguage($this->wolof);
        }
    //
        // Get mandinka string
        public function getMandinka() {
            return $this->getLanguage($this->mandinka);
        }
    //
        /**
         * Set cookie for language for next visit (before any output).
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function languageCookieGet() {
            // language cookie GET
            $this->changeSessionLang();

            global $theOven;
            $theOven->bake($this->session, $_SESSION[$this->session]);
        }
    //
        /**
         * internal dictionary.
         *
         * input (english) and output lower case only
         *
         * Args:
         *     word (string): to be translated
         *     bFemale (bool): to make the translated word female
         *
         * Returns:
         *     (string) translated word
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function translate($word, $isFemale=false) {
            global $theTranslators;
            $back = $theTranslators->getWord($_SESSION["language"], $word, $isFemale);
            $this->logger->debug("translate($word, " . (int)$isFemale . ") --[{$_SESSION['language']}]-> $back");
            return $back;
        }
    //
        private function getSuper($super, $key) {
            if(!isset($super[$key])) {
                return NULL;
            }

            if($super[$key] == "") {
                return NULL;
            }

            return $super[$key];
        }
    //
        /**
         * change session language
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function changeSessionLang($newlang="") {
            $this->logger->trace("changeSessionLang($newlang)");

            global $theOven;
            $cookie = $theOven->retrieve($this->session);
            if($cookie !== NULL && $newlang == "" && $this->inAvailLangs($cookie)) {
                $newlang = $cookie;
                $this->logger->debug("changeSessionLang(COOKIE) => $newlang");
            }

            $get = $this->getSuper($_GET, "language");
            if($get !== NULL) {
                $this->logger->trace("changeSessionLang GET=$get");

                if($this->inAvailLangs($get)) {
                    $this->logger->trace("changeSessionLang GET is valid");
                    $newlang = $get;
                }
            }

            if($newlang == "") {
                $newlang = $_SESSION["AvailLangs"][0];
                $this->logger->debug("changeSessionLang(DEFAULT) => $newlang");
            }

            if(isset($_SESSION[$this->session]) && $_SESSION[$this->session] != $newlang) {
                $this->logger->debug("changeSessionLang {$_SESSION[$this->session]}=>$newlang");
            }

            $_SESSION[$this->session] = $newlang;
        }
    //
        /**
         * languages
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function languages() {
            $this->logger->trace("languages()");

            $picpath = "/pictures/languages";
            $css = "language";

            if(count($_SESSION["AvailLangs"]) == 1) {
                $this->logger->debug("languages single language not necessary");
                return "";
            }

            $back = "<div id=\"$css\">\n";

            // Check if actual language is in array
            if(!$this->inAvailLangs($_SESSION["language"])) {
                $_SESSION["language"] = $_SESSION["AvailLangs"][0];
            }

            // Load the required languages
            foreach($_SESSION["AvailLangs"] as $lang) {
                if($_SESSION["language"] == $lang) {
                    continue;
                }

                $pic = "$picpath/$lang.png";

                global $theBodyBuilder;
                $back .= $theBodyBuilder->imgAnchor("{$this->filename}?language=$lang", $pic, $lang, "limg");
            }

            $back .= "</div>\n";
            $this->logger->trace("languages end");
            return $back;
        }
    //
        /**
         * Compare session language
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public function checkSessionLang($lang) {
            $this->logger->trace("checkSessionLang($lang)");
            return ($_SESSION["language"] == $this->getLanguage($lang));
        }
}


// singleton
$theLogopedist = new LanguageHelper();
?>
