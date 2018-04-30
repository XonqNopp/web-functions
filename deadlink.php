<?php
/*** Created: Tue 2014-04-29 10:23:46 CEST
 * TODO:
 */
require("classPage.php");
$page = new PhPage("..");
//$page->LogLevelUp(6);
$page->CSS_ppJump();
$page->SetTitle("Dead Link");
$page->HotBooty();
$body = "";
$body .= "<div id=\"deadlink\">\n";
$body .= "<img src=\"/functions/pics/404.png\" alt=\"dead link\" title=\"dead link\" />\n";
$body .= "</div>\n";
$body .= "<div id=\"deadbody\">Oops, it seems you found a dead link...</div>\n";
$page->show($body);
unset($page);
?>
