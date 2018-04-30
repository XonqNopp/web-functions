<?php
/*** Created: Sun 2015-03-15 11:13:58 CET
 * TODO:
 *
 */
$initLocal = new stdClass();


$ddb = new stdClass();
$ddb->server   = "";
$ddb->username = "";
$ddb->password = "";
$ddb->DBname   = "";
$initLocal->ddb = $ddb;


$initLocal->sex = new stdClass();
$initLocal->sex->sugar       = "";
$initLocal->sex->session     = "";
$initLocal->sex->GuestValue  = "";
$initLocal->sex->LoggedValue = "";
$initLocal->sex->AdminValue  = "";
$initLocal->sex->SuperValue  = "";


$initLocal->long = new stdClass();
$initLocal->long->english = "english";
$initLocal->long->french  = "francais";

$initLocal->AvailLangs = array($initLocal->long->english);//, $initLocal->long->french);
?>
