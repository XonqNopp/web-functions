<?php
/*** Created: Thu 2014-12-18 12:55:56 CET
 * TODO:
 *
 */
require("../functions/photos_displaythumb.php");
$picpath = $_GET["picpath"];
$max = 100;
if(isset($_GET["max"])) {
	$max = $_GET["max"];
}
GetThumb($picpath, $max);
?>
