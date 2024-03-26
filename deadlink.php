<?php
require_once("page_helper.php");
$page = new PhPage("..");
//$page->logLevelUp(6);
$page->setTitle("Dead Link");
$page->hotBooty();
$body = "<div id=\"deadlink\">\n";
$body .= "<img src=\"/functions/pics/404.png\" alt=\"dead link\" title=\"dead link\" />\n";
$body .= "</div>\n";
$body .= "<div id=\"deadbody\">Oops, it seems you found a dead link...</div>\n";
$page->show($body);
unset($page);
?>
