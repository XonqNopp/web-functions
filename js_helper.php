<?php
require_once("helper.php");


class JsHelper extends MyHelper {
    private $rootPath = "functions";
    private $files = array();
    private $plain = "";

    public function setup($rootPath="") {
        if($rootPath == "") {
            return;
        }

        $this->rootPath = "$rootPath/functions";
    }

        // push script
        public function push($script, $path="") {
            $this->logger->trace("push($script, path='$path')");

            if(array_key_exists($script, $this->files)) {
                $this->logger->error("js error: script $script already in array");
                return false;
            }

            $this->files[$script] = $path;
            return true;
        }
    //
        // javascript lines
        public function lines() {
            $this->logger->trace("lines()");

            $back = "";

            foreach($this->files as $script => $path) {
                $script = "js$script.js";

                if($path != "") {
                    $script = "$path/$script";
                }

                $this->logger->debug("lines script=$script");

                if(!file_exists("$script")) {
                    $this->logger->debug("lines script not found");
                    continue;
                }

                $back .= "<script src=\"$script\"></script>\n";
            }

            $back .= $this->plain;

            return $back;
        }
    //
        // BeforeUnload
        public function beforeUnload() {
            $this->logger->trace("beforeUnload()");
            return "onbeforeunload=\"return ConfirmCancel()\"";
        }
    //
        /**
         * Load JS needed for forms.
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function form($unload=true) {
            $this->logger->trace("form(unload=$unload)");

            $this->push("4forms", $this->rootPath);

            if(!$unload) {
                return "";
            }

            return $this->beforeUnload();
        }
    //
        // add a js script manually
        public function addPlainScript($script) {
            $this->plain .= $script;
        }
}


// singleton
$theJsHelper = new JsHelper();
?>
