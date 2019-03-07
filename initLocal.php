<?php  // FIXME kept only for editor formatting, remove this in production
// Created: 2019-03-04T16:35:00Z

$initLocal = new stdClass();


$initLocal->ddb = new stdClass();
$initLocal->ddb->server   = "";
$initLocal->ddb->username = "";
$initLocal->ddb->password = "";
$initLocal->ddb->DBname   = "";
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

$initLocal->misc = new stdClass();
?>  // FIXME kept only for editor formatting, remove this in production

