<?php
require_once("helper.php");

require_once("body_helper.php");


class CssHelper extends MyHelper {
    public $isValid = true;
    private $filename;

    // special arrays: key is script filename, value is path (if any)
    private $files = array(
        "basejump"      => "functions", //     common CSS for all my websites
        "bungeejumping" => "functions", // responsive CSS for all my websites

        "wingsuit"      => "",          //     common CSS at root of single website
        "bridge"        => "",          // responsive CSS at root of single website

        "parachute"     => "",          //     common CSS only for files in same dir
        "rope"          => ""           // responsive CSS only for files in same dir
    );

    public function setup($rootPath, $filename) {
        $this->addPrefix("basejump", $rootPath);
        $this->addPrefix("bungeejumping", $rootPath);

        $this->filename = $filename;
    }

        // invalidate
        public function invalidate() {
            $this->logger->trace("invalidate");
            $this->isValid = false;
        }
    //
        // Get valid for footer
        public function getValid() {
            $this->logger->trace("getValid: isValid={$this->isValid}");

            if(!$this->isValid) {
                return "";
            }

            global $theBodyBuilder;
            return $theBodyBuilder->anchor(
                "http://jigsaw.w3.org/css-validator/check?url=referer&amp;profile=css3",
                "C",
                "valid CSS",
                true
            );
        }
    //
        // push stylesheet
        public function push($stylesheet, $path="") {
            $this->logger->trace("push($stylesheet)");

            if(array_key_exists($stylesheet, $this->files)) {
                $this->logger->error("CSS error: stylesheet $stylesheet already in array");
                return false;
            }

            $this->files[$stylesheet] = $path;
            return true;
        }
    //
        // Add prefix to path of stylesheet
        public function addPrefix($stylesheet, $prefix) {
            $this->logger->trace("addPrefix($stylesheet, $prefix)");

            if(!array_key_exists($stylesheet, $this->files)) {
                $this->logger->error("stylesheet not found in array");
                return;
            }

            if($prefix == "") {
                // Nothing to do
                return;
            }

            $newPath = $prefix;
            $oldPath = $this->files[$stylesheet];
            if($oldPath != "") {
                $newPath = "$newPath/$oldPath";
            }
            $this->files[$stylesheet] = $newPath;
            $this->logger->debug("addPrefix: changed from $oldPath to $newPath");
        }
    //
        // Change path to x dir further
        public function dirUp($stylesheet, $dirUp=1) {
            $this->logger->trace("dirUp($stylesheet, $dirUp)");

            for($i = 1; $i <= $dirUp; $i++) {
                $this->addPrefix($stylesheet, "..");
            }
        }
    //
        // Change path to x dir further for wingsuit
        public function dirUpWing($dirUp=1) {
            $this->logger->trace("dirUpWing($dirUp)");

            $this->dirUp("wingsuit", $dirUp);
            $this->dirUp("bridge", $dirUp);
        }
    //
        public function lines() {
            $this->logger->trace("lines()");

            // Add own CSS to stack
            $shortName = preg_replace("/\.php$/", "", $this->filename);
            $this->push($shortName);

            $back = "";

            foreach($this->files as $stylesheet => $path) {
                $stylesheet = "$stylesheet.css";

                if($path != "") {
                    $stylesheet = "$path/$stylesheet";
                }

                $this->logger->debug("lines stylesheet=$stylesheet");

                if(!file_exists("$stylesheet")) {
                    $this->logger->debug("lines stylesheet not found");
                    continue;
                }

                $back .= "<link rel=\"stylesheet\" href=\"$stylesheet\" />\n";
            }

            return $back;
        }
}


// singleton
$theCssHelper = new CssHelper();
?>
