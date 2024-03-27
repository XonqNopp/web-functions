<?php
require_once("helper.php");


class TableHelper extends MyHelper {
    private $tableOpened = false;
    private $rowOpened = false;
    private $cellOpened = false;

    private $minWidth = 64;

    public function setup() {
        // Empty method so we can still call
    }
    //
        public function setMinWidth($minWidth) {
            $this->logger->trace("setMinWidth($minWidth)");

            if($this->tableOpened) {
                $this->logger->fatal("Cannot set table width when a table is already opened");
            }

            $this->minWidth = $minWidth;
        }
    //
        /**
            * Open an element
            *
            * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        private function tabOpen($element, $css=NULL, $hasNewline=true) {
            $class = "csstab{$this->minWidth}_{$element}";

            if($css !== NULL && $css != "") {
                $class .= " $css";
            }

            $string = "<div class=\"$class\">";

            if($hasNewline) {
                $string .= "\n";
            }

            return $string;
        }

        public function open($css=NULL) {
            if($this->tableOpened) {
                $this->logger->fatal("There already is an opened table");
            }

            $this->tableOpened = true;

            return $this->tabOpen("table", $css);
        }

        public function rowOpen($css=NULL) {
            if(!$this->tableOpened) {
                $this->logger->fatal("Cannot open table row outside table");
            }

            if($this->rowOpened) {
                $this->logger->fatal("There already is an opened row");
            }

            $this->rowOpened = true;

            return $this->tabOpen("row", $css);
        }

        /**
         * Open a cell
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function cellOpen($css=NULL, $hasNewline=true) {
            if(!$this->rowOpened) {
                $this->logger->fatal("Cannot open table cell outside of row");
            }

            if($this->cellOpened) {
                $this->logger->fatal("There already is an opened cell");
            }

            $this->cellOpened = true;

            return $this->tabOpen("cell", $css, $hasNewline);
        }
    //
        private function tabClose($element) {
            return "</div><!-- csstab{$this->minWidth}_{$element} -->\n";
        }

        public function cellClose() {
            $this->cellOpened = false;

            return $this->tabClose("cell");
        }

        public function rowClose() {
            if($this->cellOpened) {
                $this->logger->fatal("There is an opened cell, close it before closing row");
            }

            $this->rowOpened = false;

            return $this->tabClose("row");
        }

        public function close() {
            if($this->rowOpened) {
                $this->logger->fatal("There is an opened row, close if before closing table");
            }

            $this->tableOpened = false;

            return $this->tabClose("table");
        }
    //
        public function cell($content=NULL, $css=NULL) {
            if($content === NULL) {
                $content = "";
            }

            return $this->cellOpen($css, false) . $content . $this->cellClose();
        }
}


// singleton
$theTableHelper = new TableHelper();
?>
