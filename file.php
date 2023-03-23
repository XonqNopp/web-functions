<?php
require("helper.php");
require("database.php");
require("text.php");


class FileHelper extends MyHelper {
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

			global $theTextHelper;
			$ext = $theTextHelper->getExt($filename);
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

			return "<img src=\"$picfile\" alt=\"$altTxt\" />\n";
		}
	//
		/**
		 * load a file
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
		 */
		public function loadFile($fieldname, $filename, $path, $maxfilesize, $maximgsize, $querybound, $bReduce=true) {
			$this->logger->trace("loadFile($fieldname, $filename, $path)");

			// Use to add in form: enctype="multipart/form-data"
			// Have to use it through try-catch
			$fullname = "$path/$filename";
			$this->logger->debug("loadFile fullname=$fullname");

			$tmp      = $_FILES[$fieldname]["tmp_name"];
			$filesize = $_FILES[$fieldname]["size"];
			$this->logger->debug("loadFile tmp_name=$tmp");
			$this->logger->debug("loadFile filesize=$filesize");

			if(!is_uploaded_file($tmp)) {
				// Not uploaded
				$this->logger->error("loadFile ERROR not uploaded");
				return;
			}
			
			if(file_exists($fullname)) {
				// Already file
				$this->logger->error("loadFile ERROR $filename already exists");
				return;
			}
			
			if(!move_uploaded_file($tmp, $fullname)) {
				$this->logger->error("loadFile ERROR cannot move file");
				return;
			}
			
			if(!$bReduce && $filesize > $maxfilesize) {
				// Too big
				$this->logger->error("loadFile ERROR file too big");
				return;
			}

			// All OK, proceed
			if($bReduce) {
				if(!$this->createThumb($maximgsize, $fullname, $fullname)) {
					$this->logger->error("loadFile ERROR cannot create thumbnail");
					unlink($fullname);
					return;
				}
			}

			// All OK, treat SQL
			$this->logger->trace("loadFile to SQL");
			if(!$querybound->execute()) {
				unlink($fullname);

				global $theDbHelper;
				$theDbHelper->displayError($querybound);
			}
		}
	//
		/**
		 * create a thumbnail file
		 *
		 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
		 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
		 * @SuppressWarnings(PHPMD.NPathComplexity)
		 */
		public function createThumb($maxsize, $picpath, $thumbpath, $bMoveoriginal=false) {
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

			global $theTextHelper;
			$ext = $theTextHelper->getExt($picpath);

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

			if($bMoveoriginal) {
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
$theFileHelper = FileHelper();
?>
