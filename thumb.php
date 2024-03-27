<?php
require_once("photos_displaythumb.php");
$max = 100;
if(isset($_GET["max"])) {
    $max = $_GET["max"];
}
GetThumb($_GET["picpath"], $max);
?>
