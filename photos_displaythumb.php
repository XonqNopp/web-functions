<?php
function GetThumb($picpath, $max) {
    //ini_set("display_errors", "1");

    $picpath = "../$picpath";

    //echo (int) file_exists($picpath);

    $size = getimagesize($picpath);
    $width  = $size[0];
    $height = $size[1];
    $newwidth  = $width;
    $newheight = $height;

    $ratio = $max / $height;
    if($width >= $height) {
        $ratio = $max / $width;
    }

    if($ratio < 1) {
        $newwidth  = $ratio * $width;
        $newheight = $ratio * $height;
    }

    $thumb = imagecreatetruecolor($newwidth, $newheight);
    $background = imagecolorallocate($thumb, 0, 0, 0);
    ImageColorTransparent($thumb, $background); // make the new temp image all transparent
    imagealphablending($thumb, false); // turn off the alpha blending to keep the alpha channel

    preg_match("/\.[^.]*$/", $picpath, $picext);
    $ext = substr(strtolower($picext[0]), 1);

    //echo 0;
    header("Content-type: image/$ext");

    //ini_set("memory_limit", "-1");
    //ini_set("gd.jpeg_ignore_warning", "1");

    switch($ext) {
        case "jpg" :
        case "jpeg":
            $img = imagecreatefromjpeg($picpath);
            imagecopyresized($thumb, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            imagejpeg($thumb);
            break;
        case "png":
            $img = imagecreatefrompng($picpath);
            imagecopyresized($thumb, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            imagepng($thumb);
            break;
        case "gif" :
            $img = imagecreatefromgif($picpath);
            imagecopyresized($thumb, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            imagegif($thumb);
            break;
        default :
            break;
    }
    imagedestroy($img);
    imagedestroy($thumb);
}
?>
