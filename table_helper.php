<?php
require_once("helper.php");


/**
 * Table helper base class.
 *
 * This class is abstract and intended to be inherited from.
 * You need then to implement the methods tagOpen and tagClose.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractTableHelper extends MyHelper {
    protected $doCrossCheck = true;
    protected $tableOpened = false;
    protected $rowOpened = false;
    protected $cellOpened = false;

    protected $oddEvenCounter = 0;

    public function setup() {
        // Empty method so we can still call
    }

        public function crossCheckDisable() {
            $this->doCrossCheck = false;
        }
    //
        public function crossCheckEnable() {
            $this->doCrossCheck = true;
        }
    //
        /**
         * Open a tag.
         *
         * Args:
         *     element (string): element to open
         *     args (array): arguments to add to the tag
         *     endsWithNewline (bool): false to not append a newline
         *
         * Returns:
         *     string: opening tag
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.UnusedFormalParameter)
         */
        protected function tagOpen($element, $args=array(), $endsWithNewline=true) {
            return "";  // implementation in child
        }
    //
        /**
         * Close a tag.
         *
         * Args:
         *     element (string): element to close
         *
         * Returns:
         *     string: closing tag
         *
         * @SuppressWarnings(PHPMD.UnusedFormalParameter)
         */
        protected function tagClose($element) {
            return "";  // implementation in child
        }
    //
        /**
         * Open a table.
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function tableOpen($args=array(), $hover=true) {
            if($this->doCrossCheck && $this->tableOpened) {
                $this->logger->fatal("There already is an opened table");
            }

            $this->tableOpened = true;
            $this->oddEvenCounter = 0;

            if($hover) {
                if(!array_key_exists("class", $args)) {
                    $args["class"] = "";
                }

                $args["class"] .= " rover";
            }

            return $this->tagOpen("table", $args);
        }
    //
        public function tableClose() {
            if($this->doCrossCheck && $this->rowOpened) {
                $this->logger->fatal("There is an opened row, close if before closing table");
            }

            $this->tableOpened = false;

            return $this->tagClose("table");
        }
    //
        /**
         * Open a row.
         *
         * Args:
         *     args (array): arguments to add to the tag
         *     isOddEven (bool): false to not mark the row as odd/even
         *
         * Returns:
         *     string: tag to open a row.
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function rowOpen($args=array(), $isOddEven=true) {
            if($this->doCrossCheck && !$this->tableOpened) {
                $this->logger->fatal("Cannot open table row outside table");
            }

            if($this->doCrossCheck && $this->rowOpened) {
                $this->logger->fatal("There already is an opened row");
            }

            $this->rowOpened = true;

            $this->oddEvenCounter += 1;
            if($isOddEven) {
                if(!array_key_exists("class", $args)) {
                    $args["class"] = "";
                }
                $args["class"] .= $this->oddEvenCounter % 2 ? " odd" : " even";
            }

            return $this->tagOpen("row", $args);
        }
    //
        public function rowClose() {
            if($this->doCrossCheck && $this->cellOpened) {
                $this->logger->fatal("There is an opened cell, close it before closing row");
            }

            $this->rowOpened = false;

            return $this->tagClose("row");
        }
    //
        /**
         * Open a cell.
         *
         * Args:
         *     args (array): arguments to add to the tag
         *     endsWithNewline (bool): false to not append a newline
         *
         * Returns:
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function cellOpen($args=array(), $endsWithNewline=true) {
            if($this->doCrossCheck && !$this->rowOpened) {
                $this->logger->fatal("Cannot open table cell outside of row");
            }

            if($this->doCrossCheck && $this->cellOpened) {
                $this->logger->fatal("There already is an opened cell");
            }

            $this->cellOpened = true;

            return $this->tagOpen("cell", $args, $endsWithNewline);
        }
    //
        public function cellClose() {
            $this->cellOpened = false;

            return $this->tagClose("cell");
        }
    //
        public function cell($content="", $args=array()) {
            return $this->cellOpen($args, false) . $content . $this->cellClose();
        }
    //
        public function row($content="", $rowArgs=array(), $cellArgs=array()) {
            if(!is_array($content)) {
                // only a string, only one cell
                return $this->rowOpen($rowArgs) . $this->cell($content, $cellArgs) . $this->rowClose();
            }

            $string = $this->rowOpen($rowArgs);

            foreach($content as $cell) {
                $string .= $this->cell($cell, $cellArgs);
            }

            return $string . $this->rowClose();
        }
    //
        /**
         * Open a header cell.
         *
         * Args:
         *     args (array): arguments to add to the tag
         *     endsWithNewline (bool): false to not append a newline
         *
         * Returns:
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function headerCellOpen($args=array(), $endsWithNewline=true) {
            if($this->doCrossCheck && !$this->rowOpened) {
                $this->logger->fatal("Cannot open table header cell outside of row");
            }

            if($this->doCrossCheck && $this->cellOpened) {
                $this->logger->fatal("There already is an opened cell");
            }

            $this->cellOpened = true;

            return $this->tagOpen("headerCell", $args, $endsWithNewline);
        }
    //
        public function headerCellClose() {
            $this->cellOpened = false;

            return $this->tagClose("headerCell");
        }
    //
        public function headerCell($content="", $args=array()) {
            return $this->headerCellOpen($args, false) . $content . $this->headerCellClose();
        }
}


class HtmlTableHelper extends AbstractTableHelper {
    private $tTagOpened = NULL;

        private function getTag($element) {
            if($element == "row") {
                return "tr";
            }
            if($element == "cell") {
                return "td";
            }
            if($element == "headerCell") {
                return "th";
            }

            return $element;
        }
    //
        /**
         * Open a tag.
         *
         * Args:
         *     element (string): element to open
         *     args (array): arguments to add to the tag
         *     endsWithNewline (bool): false to not append a newline
         *
         * Returns:
         *     string: opening tag
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        protected function tagOpen($element, $args=array(), $endsWithNewline=true) {
            $string = "<{$this->getTag($element)}";

            foreach($args as $key => $value) {
                $string .= " $key=\"$value\"";
            }

            $string .= ">" . ($endsWithNewline ? "\n" : "");
            return $string;
        }
    //
        protected function tagClose($element) {
            return "</{$this->getTag($element)}>\n";
        }
    //
        private function tTagOpen($tTag, $args=array()) {
            if($this->doCrossCheck && !$this->tableOpened) {
                $this->logger->fatal("Cannot open '$tTag' outside a table");
            }

            if($this->doCrossCheck && $this->rowOpened) {
                $this->logger->fatal("Cannot open '$tTag' when there is an opened row");
            }

            if($this->doCrossCheck && $this->cellOpened) {
                $this->logger->fatal("Cannot open '$tTag' when there is an opened cell");
            }

            if($this->doCrossCheck && $this->tTagOpened !== NULL) {
                $this->logger->fatal("Cannot open '$tTag' when there is already a {$this->tTagOpened} in progress");
            }

            $this->tTagOpened = $tTag;
            return $this->tagOpen($tTag, $args);
        }
    //
        private function tTagClose($tTag, $args=array()) {
            if($this->doCrossCheck && $this->rowOpened) {
                $this->logger->fatal("Cannot close '$tTag' when there is an opened row");
            }

            if($this->doCrossCheck && $this->cellOpened) {
                $this->logger->fatal("Cannot close '$tTag' when there is an opened cell");
            }

            if($this->doCrossCheck && $this->tTagOpened != $tTag) {
                $this->logger->fatal("Cannot close '$tTag', currently opened is {$this->tTagOpened}");
            }

            $this->tTagOpened = NULL;
            return $this->tagClose($tTag, $args);
        }
    //
        public function theadOpen($args=array()) {
            return $this->tTagOpen("thead", $args);
        }
    //
        public function theadClose($args=array()) {
            return $this->tTagClose("thead", $args);
        }
    //
        public function tbodyOpen($args=array()) {
            return $this->tTagOpen("tbody", $args);
        }
    //
        public function tbodyClose($args=array()) {
            return $this->tTagClose("tbody", $args);
        }
    //
        public function tfootOpen($args=array()) {
            return $this->tTagOpen("tfoot", $args);
        }
    //
        public function tfootClose($args=array()) {
            return $this->tTagClose("tfoot", $args);
        }
}


class CssTableHelper extends AbstractTableHelper {
    private $minWidth = 64;
    private $allowedWidths = array(64);

        public function setMinWidth($minWidth) {
            if(!in_array($minWidth, $this->allowedWidths)) {
                $this->logger->fatal("CSS table not implemented for $minWidth");
            }

            $this->logger->trace("setMinWidth($minWidth)");

            if($this->doCrossCheck && $this->tableOpened) {
                $this->logger->fatal("Cannot set table width when a table is already opened");
            }

            $this->minWidth = $minWidth;
        }
    //
        /**
         * Open a tag.
         *
         * Args:
         *     element (string): element to open
         *     args (array): arguments to add to the tag
         *     endsWithNewline (bool): false to not append a newline
         *
         * Returns:
         *     string: opening tag
         *
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        protected function tagOpen($element, $args=array(), $endsWithNewline=true) {
            $string = "<div";
            $class = "csstab{$this->minWidth}_{$element}";

            foreach($args as $key => $value) {
                if($key == "class") {
                    $class .= " $value";
                    continue;
                }

                $string .= " $key=\"$value\"";
            }
            $string .= " class=\"$class\"";
            $string .= ">" . ($endsWithNewline ? "\n" : "");
            return $string;
        }
    //
        protected function tagClose($element) {
            return "</div><!-- csstab{$this->minWidth}_{$element} -->\n";
        }
}



// singleton
// "table helper" is a waiter
// To distinguish between HTML and CSS, let's use those in the name (at least part of the acronyms):
$theWaitress = new CssTableHelper();
$theButler = new HtmlTableHelper();  // Everyone needs a butler. (Jasper Fforde)
?>
