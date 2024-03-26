<?php
require_once("helper.php");


class BodyHelper extends MyHelper {
	public $targetBlank = "target=\"_blank\"";

	public function setup() {
		// Empty method so we can still call
	}
	//
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

			return $this->anchor(
				$page,
				"<img class=\"$class\" title=\"$title\" alt=\"$title\" src=\"$picUp\" />"
			);
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

			return $this->anchor(
				$rootpage,
				"<img class=\"$class\" title=\"$roottitle\" alt=\"$roottitle\" src=\"$picHome\" />"
			);
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
		
			return $this->anchor(
				$this->filename . $which,
				"<img class=\"chome\" title=\"$title\" alt=\"$title\" src=\"$pic\" />"
			);
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
			$back .= $this->goUp($upPage);
			$back .= $this->goRoot($rootPage);
			$back .= $this->next($next);
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
		 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
		 */
		public function anchor($href, $content, $title=NULL, $targetBlank=false, $class=NULL, $moreArgs=NULL) {
			$string = "<a";

			if($class !== NULL && $class != "") {
				$string .= " class=\"$class\"";
			}

			if($targetBlank) {
				$string .= " {$this->targetBlank}";
			}

			$string .= " href=\"$href\"";

			if($title !== NULL) {
				if($title == true) {
					$title = $content;
				}

				$string .= " title=\"$title\"";
			}

			if($moreArgs !== NULL && $moreArgs != "") {
				$string .= " $moreArgs";
			}

			$string .= ">$content</a>\n";
			return $string;
		}
	//
		// Title anchor
		function titleAnchor($title, $level=3, $idPrefix="") {
			$ascii = $title;
			$ascii = $idPrefix . preg_replace("/[ &;:'\?!\(\)\/]/", '', $title);

			$string = "<!-- H$level $title -->\n";
			$string .= "<h$level id=\"$ascii\">";
			$string .= "$title&nbsp;\n";
			$string .= $this->anchor("#$ascii", "#", NULL, false, "titleAnchor");
			$string .= "</h$level>";
			$string .= "\n";
			return $string;
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
$theBodyHelper = new BodyHelper();
?>
