<?php
require_once("helper.php");
require_once("body_helper.php");


class FileHelper extends MyHelper {
    public function setup() {
        // Empty method so we can still call
    }

        /**
         * get extension of filename
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function getExt($filename, $withDot=true) {
            $this->logger->trace("getExt($filename)");

            $filename = trim($filename);
            if($filename == "") {
                return "";
            }

            $back = "";
            preg_match("/\.[^.]*$/", $filename, $back);
            $back = strtolower($back[0]);

            if(!$withDot) {
                $back = substr($back, 1);
            }

            $this->logger->debug("getExt($filename) = $back");
            return $back;
        }
    //
        // filename without extension
        public function woExt($filename) {
            $this->logger->trace("woExt($filename)");

            $filename = trim($filename);
            if($filename == "") {
                return "";
            }

            $back = "";
            preg_match("/(.*)\.[^\.]*$/", $filename, $back);
            $back = $back[1];
            $this->logger->debug("woExt($filename) = $back");
            return $back;
        }
    //
        /**
         * embed file (PDF)
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function embedFile($filename, $altTxt=NULL, $picthumb=false, $picsize=0, $pdfwidth=1000, $pdfheight=1000, $funcpath=NULL) {
            $this->logger->trace("embedFile($filename)");

            $thumbpath = "functions_local/thumb.php";
            if($funcpath !== NULL && $funcpath != "") {
                $thumbpath = "$funcpath/$thumbpath";
            }

            if($altTxt === NULL || $altTxt == "") {
                $this->logger->debug("embedFile altTxt empty, setting filename");
                $altTxt = $filename;
            }

            $ext = $this->getExt($filename);
            $this->logger->debug("embedFile ext=$ext");

            if($ext == ".pdf") {
                $this->logger->trace("embedFile is PDF");
                return "<embed src=\"$filename\" type=\"application/pdf\" width=\"$pdfwidth\" height=\"$pdfheight\" />\n";
            }

            $this->logger->trace("embedFile is *not* PDF");
            $picfile = $filename;

            if($picthumb) {
                $filename = preg_replace("/^(\.\.\/)+/", "", $filename);
                $this->logger->trace("embedFile using thumbnail");
                $picfile = "$thumbpath?picpath=$filename";

                if($picsize > 0) {
                    $picfile .= "&amp;max=$picsize";
                }
            }

            global $theBodyBuilder;
            return $theBodyBuilder->img($picfile, $altTxt);
        }
    //
        /**
         * Load a file.
         *
         * WARNING: do not call this one directly.
         * Use the one from DB helper.
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         */
        public function loadFile($fieldname, $filename, $path, $maxfilesize, $maximgsize, $reduce=true) {
            $this->logger->trace("loadFile($fieldname, $filename, $path)");

            // Use to add in form: enctype="multipart/form-data"
            // Have to use it through try-catch
            $fullname = "$path/$filename";
            $this->logger->debug("loadFile fullname=$fullname");

            $tmp      = $_FILES[$fieldname]["tmp_name"];
            $filesize = $_FILES[$fieldname]["size"];
            $this->logger->debug("loadFile tmp_name=$tmp size=$filesize");

            if(!$reduce && $filesize > $maxfilesize) {
                $this->logger->error("loadFile ERROR file too big");
                return NULL;
            }

            if(!is_uploaded_file($tmp)) {
                $this->logger->error("loadFile ERROR not uploaded");
                return NULL;
            }
            
            if(file_exists($fullname)) {
                $this->logger->error("loadFile ERROR $filename already exists");
                return NULL;
            }
            
            if(!move_uploaded_file($tmp, $fullname)) {
                $this->logger->error("loadFile ERROR cannot move file");
                return NULL;
            }
            
            // All OK, proceed
            if($reduce) {
                if(!$this->createThumb($maximgsize, $fullname, $fullname)) {
                    $this->logger->error("loadFile ERROR cannot create thumbnail");
                    unlink($fullname);
                    return NULL;
                }
            }

            return $fullname;
        }
    //
        /**
         * create a thumbnail file
         *
         * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         * @SuppressWarnings(PHPMD.NPathComplexity)
         */
        public function createThumb($maxsize, $picpath, $thumbpath, $moveOriginal=false) {
            $size = getimagesize($picpath);

            $width  = $size[0];
            $height = $size[1];

            $ratio = $maxsize / $height;
            if($width >= $height) {
                $ratio = $maxsize / $width;
            }

            if($ratio >= 1) {
                return true;
                //return copy($picpath, $thumbpath);
            }

            $imgCopyResizedStatus = true;
            $saveStatus = true;
            $renameStatus = true;

            $newwidth  = $ratio * $width;
            $newheight = $ratio * $height;

            $thumb = imagecreatetruecolor($newwidth, $newheight);

            $ext = $this->getExt($picpath);

            ini_set("memory_limit", "-1");
            ini_set("gd.jpeg_ignore_warning", "1");

            $img = NULL;

            switch($ext) {
                case ".jpg" :
                case ".jpeg":
                    $img = imagecreatefromjpeg($picpath);
                    break;

                case ".png":
                    $img = imagecreatefrompng($picpath);
                    break;

                case ".gif" :
                    $img = imagecreatefromgif($picpath);
                    break;

                default :
                    // Do nothing and let it as it is
                    break;
            }

            if($img === NULL) {
                imagedestroy($thumb);
                return false;
            }

            $imgCopyResizedStatus = imagecopyresized($thumb, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            if($moveOriginal) {
                $renameStatus = rename($picpath, $thumbpath);
                $thumbpath = $picpath;
            }

            switch($ext) {
                case ".jpg" :
                case ".jpeg":
                    $saveStatus = imagejpeg($thumb, $thumbpath, 100);
                    break;

                case ".png":
                    $saveStatus = imagepng($thumb, $thumbpath);
                    break;

                case ".gif" :
                    $saveStatus = imagegif($thumb, $thumbpath);
                    break;

                default :
                    // Do nothing and let it as it is
                    break;
            }

            imagedestroy($img);
            imagedestroy($thumb);

            return $imgCopyResizedStatus && $saveStatus && $renameStatus;
        }
}


// singleton
$theFileHelper = new FileHelper();
?>
