<?php
require_once("helper.php");


/**
 * Body builder: methods to help build the body.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class BodyHelper extends MyHelper {
    public $strLaTeX = "<span class=\"latex\">L<sup>a</sup>T<sub>e</sub>X</span>";

    public $targetBlank = "target=\"_blank\"";

    private $filename;
    private $noTitle = "DO_NOT-uSE-a-tItLE";
    private $titleAnchorCount = false;

    public function setup($filename) {
        $this->filename = $filename;
    }

        private function upIndex($path) {
            if(!preg_match("/\.\.(\/\.\.)*/", $path)) {
                return $path;
            }

            // check if page==".." or "../"
            if(preg_match("/\.\.$/", $path)) {
                return "$path/index.php";
            }

            if(preg_match("/\.\.\/$/", $path)) {
                return "{$path}index.php";
            }

            // This code should be unreachable
            return $path;
        }
    //
        /**
         * GoHome: go up
         *
         * @SuppressWarnings(PHPMD.ElseExpression)
         */
        public function goUp($page=NULL) {
            $this->logger->trace("goUp($page)");

            if($page === NULL || $page == "") {
                $page = "index.php";
            } else {
                $page = $this->upIndex($page);
            }

            $class = "chome";
            $title = "Up";
            $picUp = "/pictures/GoHome/up.png";

            return $this->imgAnchor($page, $picUp, $title, $class);
        }
    //
        // GoHome: go root
        public function goRoot($rootpage=NULL) {
            $this->logger->trace("goRoot($rootpage)");

            if($rootpage === NULL || $rootpage == "") {
                return "";
            }

            $class = "chome";
            $roottitle = "Home";
            $picHome = "/pictures/GoHome/home.png";

            $rootpage = $this->upIndex($rootpage);
            $this->logger->debug("goHome rootpage=$rootpage");
            if($rootpage == "") {
                return "";
            }

            return $this->imgAnchor($rootpage, $picHome, $roottitle, $class);
        }
    //
        // GoHome: previous + next
        private function goPreviousNext($theId, $title, $pic) {
            if($theId === NULL || $theId <= 0) {
                return "";
            }

            $this->logger->trace("goPreviousNext($theId, $title)");

            // id
            $which = "?id=$theId";
            $this->logger->debug("goHome previous which=$which");
        
            return $this->imgAnchor($this->filename . $which, $pic, $title, "chome");
        }

        public function goPrevious($previousId) {
            return $this->goPreviousNext($previousId, "Previous", "/pictures/GoHome/pa.png");
        }

        public function goNext($nextId) {
            return $this->goPreviousNext($nextId, "Next", "/pictures/GoHome/na.png");
        }
    //
        public function goHome($rootPage=NULL, $upPage=NULL, $previous=NULL, $next=NULL) {
            $back = "<div id=\"home\">\n";

            $back .= $this->goPrevious($previous);

            if($rootPage == ".." && $upPage == NULL && $this->filename == "index.php") {
                // Just provide up to parent dir
                $upPage = $rootPage;
                $rootPage = NULL;
            }

            $back .= $this->goUp($upPage);
            $back .= $this->goRoot($rootPage);

            $back .= $this->goNext($next);

            $back .= "</div>\n";
            return $back;
        }
    //
        // Make marquee (text goes by)
        public function marquee($text, $url="", $urltitle="") {
            $this->logger->trace("maqruee($text)");
            $back = $this->logger->htmlBlock("marquee start");
            $back .= "<div class=\"marquee\">\n";
            $back .= "<span>\n";

            if($url != "") {
                $this->logger->debug("marquee url=$url");

                if($urltitle == "") {
                    $urltitle = $text;
                }

                $this->logger->debug("marquee urltitle=$urltitle");
                $back .= "<a href=\"$url\" title=\"$urltitle\">";
            }

            $back .= $text;

            if($url != "") {
                $back .= "</a>\n";
            }

            $back .= "</span>\n";
            $back .= "</div>\n";
            $back .= $this->logger->htmlBlock("marquee finished");

            $this->logger->trace("marquee end");
            return $back;
        }
    //
        /**
         * anchor (link)
         *
         * Args:
         *     url (string): url to link to
         *     content (string): text to embed in the anchor tag. If NULL, uses URL.
         *     title (string): title arg of tag. If NULL uses content.
         *     class (string): CSS class
         *     targetBlank (bool): true to set tag arg target blank to true
         *     moreArgs (string): additional args for tag
         *
         * Returns:
         *     string: HTML anchor
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         * @SuppressWarnings(PHPMD.NPathComplexity)
         */
        public function anchor(
            $url,
            $content=NULL,
            $title=NULL,
            $class=NULL,
            $targetBlank=false,
            $moreArgs=NULL,
            $lineBreak=true
        ) {
            $string = "<a";

            if($class !== NULL && $class != "") {
                $string .= " class=\"$class\"";
            }

            if($targetBlank) {
                $string .= " {$this->targetBlank}";
            }

            $string .= " href=\"$url\"";

            if($title === NULL) {
                $title = $content;
            }
            if($title !== NULL && $title != $this->noTitle) {
                // Content can be null if we only provide a URL
                $string .= " title=\"$title\"";
            }

            if($moreArgs !== NULL && $moreArgs != "") {
                $string .= " $moreArgs";
            }

            if($content === NULL) {
                $content = $url;
            }
            $string .= ">$content</a>";
            $string .= $lineBreak ? "\n" : "";
            return $string;
        }
    //
        public function titleAnchorCountEnable() {
            $this->titlaAnchorCount = true;
        }
    //
        public function titleAnchorCountDisable() {
            $this->titleAnchorCount = false;
        }
    //
        /**
         * Title anchor.
         *
         * Args:
         *     title (string)
         *     level (int)
         *     idPrefix (string): use this if you have conflicting tag IDs
         *
         * Returns:
         *     string: title with a hover anchor
         */
        function titleAnchor($title, $level=2, $idPrefix="") {
            $ascii = $title;
            $ascii = $idPrefix . preg_replace("/[ &;:'\?!\(\)\/]/", '', $title);

            $string = "<!-- H$level $title -->\n";
            $string .= "<h$level id=\"$ascii\"";
            if($this->titleAnchorCount) {
                $string .= " class=\"hCount\"";
            }
            $string .= ">";
            $string .= "$title&nbsp;\n";
            $string .= $this->anchor("#$ascii", "#", $this->noTitle, "titleAnchor", false);
            $string .= "</h$level>";
            $string .= "\n";
            return $string;
        }
    //
        public function img($src, $text=NULL, $class=NULL, $moreArgs=NULL) {
            $string = "<img";

            if($class !== NULL && $class != "") {
                $string .= " class=\"$class\"";
            }

            $string .= " src=\"$src\"";

            if($text !== NULL && $text != "") {
                $string .= " alt=\"$text\" title=\"$text\"";
            }

            if($moreArgs !== NULL && $moreArgs != "") {
                $string .= " $moreArgs";
            }

            $string .= " />\n";
            return $string;
        }
    //
        /**
         * Image anchor.
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function imgAnchor(
            $url,
            $src,
            $text=NULL,
            $imgClass=NULL,
            $imgMoreArgs=NULL,
            $targetBlank=false,
            $anchorClass=NULL,
            $anchorMoreArgs=NULL
        ) {
            return $this->anchor(
                $url,
                $this->img($src, $text, $imgClass, $imgMoreArgs),
                $text,
                $anchorClass,
                $targetBlank,
                $anchorMoreArgs
            );
        }
    //
        /**
         * List item (li)
         */
        public function lili($content, $class="") {
            $item = "<li";
            if($class != "") {
                $item .= " class=\"$class\"";
            }
            $item .= ">$content</li>\n";
            return $item;
        }
    //
        /**
         * List anchor (link)
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function liAnchor($url, $content, $targetBlank=false, $liClass=NULL, $anchorClass=NULL, $anchorArgs=NULL) {
            return $this->lili(
                $this->anchor(
                    $url,
                    $content,
                    $content,
                    $targetBlank,
                    $anchorClass,
                    $anchorArgs
                ),

                $liClass
            );
        }
    //
        // Phone
        public function tel($number) {
            // CH format +41xxAAAbbCC
            $strNumber = substr($number, 0, 3);
            $strNumber .= "&nbsp;";
            $strNumber .= substr($number, 3, 2);
            $strNumber .= "&nbsp;";
            $strNumber .= substr($number, 5, 3);
            $strNumber .= "&nbsp;";
            $strNumber .= substr($number, 8, 2);
            $strNumber .= "&nbsp;";
            $strNumber .= substr($number, 10, 2);

            return $this->anchor("tel:$number", $strNumber);
        }
}


// singleton
$theBodyBuilder = new BodyHelper();
?>
