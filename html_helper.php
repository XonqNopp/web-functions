<?php
require_once("helper.php");

require_once("body_helper.php");
require_once("css_helper.php");
require_once("database_helper.php");
require_once("file_helper.php");
require_once("js_helper.php");


class HtmlHelper extends MyHelper {
    public $isValid = true;

    private $isOpened = false;
    private $isHeadless = false;
    private $bodyguards  = "";
    private $meta        = array();
    private $faviconPic  = "/pictures/favicon.png";
    private $iOsPic      = "";  // set iOS home screen icon (min 57x57)
    private $iOsStartup  = "";
    private $title       = "";

    public function setup() {
        // Empty method so we can still call
    }

    public function teardown() {
        $this->finishHim();
    }

        // invalidate
        public function invalidate() {
            $this->logger->trace("invalidate");
            $this->isValid = false;
        }
    //
        // W3
        private function getValid() {
            $this->logger->trace("getValid: isValid={$this->isValid}");

            if(!$this->isValid) {
                return "";
            }

            global $theBodyBuilder;
            return $theBodyBuilder->anchor(
                "http://validator.w3.org/check?uri=referer",
                "H",
                "valid HTML5",
                true
            );
        }
    //
        // Doctype
        private function doctypetag() {
            $this->logger->trace("doctypetag()");
            $back = "<!doctype html>\n";
            return $back;
        }
    //
        // HTML tag
        private function htmltag() {
            $back = "<html";
            $back .= ' lang="en"';
            $back .= ">\n";
            $this->logger->trace("htmltag()");
            return $back;
        }
    //
        // Init HTML: open doctype and html
        private function init() {
            $this->logger->trace("init()");

            if($this->isOpened) {
                $this->logger->info("init(): already opened HTML");
                return;
            }

            $foetus = "";
            $foetus .= $this->doctypetag();
            $foetus .= $this->htmltag();
            $foetus .= "<!-- Hey, why do you check the source code? ;-) -->\n";
            echo $foetus;

            $this->isOpened = true;

            $this->logger->htmlOpen = true;
            $this->logger->logStack();// now that open==true
            $this->logger->trace("init() end");
        }
    //
        // meta STUFF
            // mandatory meta
            private function mandatoryMeta() {
                $this->logger->trace("mandatoryMeta()");
                $back = "<meta charset=\"utf-8\" />\n";
                $back .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />\n";
                return $back;
            }
        //
            // Add one
            public function metaphysics($name, $content) {
                $this->logger->trace("metaphysics($name, $content)");
                $this->meta[$name] = $content;
            }
        //
            // Get 'em all
            private function getAllMeta() {
                $this->logger->trace("getAllMeta()");
                $back = "";
                foreach($this->meta as $name => $content) {
                    if($content != "") {
                        $back .= "<meta name=\"$name\" content=\"$content\" />\n";
                    }
                }
                return $back;
            }
    //
        // favicon
        private function favicon() {
            $this->logger->trace("favicon()");

            if($this->faviconPic == "") {
                // Cannot do anything if no favicon
                return "";
            }

            $this->logger->trace("favicon pic not empty");

            $back = "";

            global $theFileHelper;
            $faviconExt = $theFileHelper->getExt($this->faviconPic, false);

            $back .= "<link rel=\"icon\" type=\"image/$faviconExt\" href=\"{$this->faviconPic}\" />\n";

            $appleIcon = $this->iOsPic != "" ? $this->iOsPic : $this->faviconPic;
            $back .= "<link rel=\"apple-touch-icon\" href=\"$appleIcon\" />\n";

            $appleStartupIcon = $this->iOsStartup != "" ? $this->iOsStartup : $this->iOsPic;
            if($appleStartupIcon == "") {
                $appleStartupIcon = $this->faviconPic;
            }
            $back .= "<link rel=\"apple-touch-startup-image\" href=\"$appleStartupIcon\" />\n";

            return $back;
        }
    //
        // set title
        public function setTitle($title, $htmlId=NULL, $class=NULL) {
            $this->logger->trace("setTitle($title)");

            $this->title = $title;

            $htmlArgs = "";

            if($htmlId !== NULL && $htmlId != "") {
                $htmlArgs = " id=\"$htmlId\"";
            }

            if($class !== NULL && $class != "") {
                $htmlArgs .= " class=\"$class\"";
            }

            return "<h1$htmlArgs>$title</h1>\n";
        }
    //
        // title line
        private function titleline() {
            $this->logger->trace("titleline()");
            $back = "";
            $displayTitle = "XonqNopp";
            if($this->title != "") {
                $displayTitle = $this->title;
            }
            $back .= "<title>$displayTitle</title>\n";
            return $back;
        }
    //
        // set bodyguards
        public function setBodyguards($args) {
            $this->logger->trace("setBodyguards($args)");

            if($this->isHeadless) {
                $this->logger->error("Cannot set bodyguards, already out");
                return;
            }

            $this->bodyguards .= " $args";
        }
    //
        /**
         * JS form
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function jsForm($unload=true) {
            global $theJsHelper;
            $this->setBodyguards($theJsHelper->form($unload));
        }
    //
        // make head
        private function makeHead() {
            $this->logger->trace("makeHead()");
            $back = "";

            if($this->isHeadless) {
                $this->logger->trace("makeHead already headless");
                return;
            }

            $back .= $this->logger->htmlBlock("makeHead start");
            $back .= "<head>\n";
            $this->logger->trace("makeHead head OK");

            $back .= $this->mandatoryMeta();
            $this->logger->trace("makeHead mandatoryMeta OK");

            $back .= $this->getAllMeta();
            $this->logger->trace("makeHead meta lines OK");

            $back .= $this->favicon();
            $this->logger->trace("makeHead favicon OK");

            global $theCssHelper;
            $back .= $theCssHelper->lines();
            $this->logger->trace("makeHead CSS OK");

            global $theJsHelper;
            $back .= $theJsHelper->lines();
            $this->logger->trace("makeHead javascript OK");

            $back .= $this->titleline();
            $this->logger->trace("makeHead titleline OK");

            $back .= $this->logger->htmlBlock("makeHead done");

            $this->logger->trace("makeHead head ready");
            echo $back;
        }
    //
        // Head to body
        private function decapite() {
            $this->logger->trace("decapite()");

            if($this->isHeadless) {
                return;
            }

            $back = "</head>\n";
            $this->logger->trace("decapite head done");

            $back .= "<body$this->bodyguards>\n";// must think about this because has args
            $this->logger->trace("decapite body start");

            // add warning for noscript
            $this->logger->trace("decapite warning noscript");
            $back .= "<noscript><div id=\"noscript\">\n";
            $back .= "You have disabled javascript. This website should work correctly except when filling forms.\n";
            $back .= "</div></noscript>\n";

            echo $back;

            $this->isHeadless = true;
            $this->logger->inBody = true;
            $this->logger->logStack(1);// now that bHeadless==true
            $this->logger->trace("decapite end");
        }
    //
        // init HTML+body
        public function hotBooty() {
            $this->logger->trace("hotBooty()");
            $this->init();
            $this->makeHead();
            $this->decapite();
        }
    //
        // Finish
        public function finishHim() {
            // close DB
            global $theBobbyTable;
            $theBobbyTable->close();

            if(!$this->isOpened) {
                return;
            }

            // Footer
            $body = $this->logger->htmlBlock("footer start");
            $body .= "<div id=\"LeftFoot\">\n";

            $body .= $this->getValid();

            global $theCssHelper;
            $body .= $theCssHelper->getValid();

            global $theBodyBuilder;
            $body .= $theBodyBuilder->anchor(
                "http://firefox.com",
                "F",
                "optimized for Firefox",
                true
            );
            $body .= $theBodyBuilder->anchor(
                "https://github.com/XonqNopp/web-xonqnopp",
                "G",
                "fork me on github",
                true
            );
            $body .= $theBodyBuilder->anchor(
                "https://www.infomaniak.com/",
                "I",
                "powered by Infomaniak",
                true
            );

            $body .= "</div>\n";  // footer
            $body .= $this->logger->htmlBlock("footer done, closing body+html");

            $body .= "</body>\n";
            $body .= "</html>\n";

            echo $body;

            $this->isOpened = false;
        }
    //
        /**
         * Header for new Location but Checks if error
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        public function headerLocation($location="index.php") {
            $this->logger->trace("headerLocation($location)");

            if($location == "") {
                return;
            }

            $count = $this->logger->countErrors();
            if($count == 0) {
                // No error, can redirect
                header("Location: $location");
                exit();  // exits anyway because I do not want to proceed further
            }

            $this->logger->error("Could not relocate to '$location' due to previous errors");
            $this->hotBooty();
            $this->logger->logStack(1);
            $this->finishHim();
            exit();  // exits anyway because I do not want to proceed further
        }
    //
        // Header for refresh
        public function headerRefresh($timeout, $url="") {
            $this->logger->trace("headerRefresh($timeout, url=$url)");

            if($url != "") {
                $timeout = "$timeout; url=$url";
            }

            header("Refresh: $timeout");
        }
}


// singleton
$theHtmlHelper = new HtmlHelper();
?>
