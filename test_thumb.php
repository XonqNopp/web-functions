<?php
/*** Created:       jeu 2014-12-18 09:52:17 CET
 * TODO:
 *
 */
require("classPage.php");
require("initLocal.php");
$page = new PhPage($initLocal);
$page->LogLevelUp(6);
$body = "";


$gohome = new stdClass();
$body .= $page->SetTitle("test");// before HotBooty
$page->HotBooty();


$page->show($body);
unset($page);
?>
