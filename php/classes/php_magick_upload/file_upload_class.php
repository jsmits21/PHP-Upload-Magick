<?php

/************************************************************************************************************************************

PHP/Image Magick Upload - Version 1.0

A powerful image uploading class that utilizes Image Magick to ensure user uploaded images adhere to your web applications standards.

Copywrite (c) 2011 - Jacob Smits - jsmits21@lavabit.com

This software is free and redistributable. DO NOT DELETE THIS CREDIT SECTION OF TEXT.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 'AS IS' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Special thanks to Olaf Lederer and his Easy PHP Upload class library. This was the inspiration and guiding example for this class.

*************************************************************************************************************************************

Features:

	* Landscape or Portrait resizing with optional ratio constraint
	* Minimum and maximum files sizes
	* Minimum and maximum image dimensions
	* Thumbnail creation
	* Powerful file handling including renaming and overwriting
	
*************************************************************************************************************************************

Notes:

	* Please do not copy or use any of this code without crediting me and this library.

*************************************************************************************************************************************/

class file_upload {
	
	/********** Variables ***********/
	
	var $theFile; //The uploaded file.
	var $tempFile; //The uploaded temporary file.
	var $uploadName; //The name/ID of the upload input field
	var $maxSize = 1572864; //Max file size in megabytes
	var $uploadDir; //The directory the file is to be stored.
	var $extensions = array( ".jpg", ".png", ".gif", ".bmp"); //Array of accepted file extensions. Defaults to common image extensions that Image Magick supports. It is recommended that you do not deviate from these and use the conversion function to standardize your websites image files.
	var $multipleUpload = true; //Set if script is to be uploading multiple files. This flag will add a counter to the end of the new file name.
	var $extString; //This is the array of extensions as a string
	var $newName; //The new name the file is to be saved as. If left blank, the script will rename it to a timestamp.
	var $fileNum = 1; //Var starts a file counter to add as an extension so files are not overwritten when uploading multiple files.
	var $maxFileName = 32; //Max length for filename
	var $doNameCheck = true; //True or false. Perform the file name reg expression check.
	var $error = array(); //An array of error messages.
	var $errorText; //The error arrayed out into individual HTML lines of text. Set this text to a session variable to echo it between pages.
	var $conFormat; //The extension to convert the file/image to. Moved from image_manip_class to fix a rename_file() bug.
	var $fileperm = 0644;
	var $dirperm = 0755;
	var $dbTable;	//The table that you want to store the information to
	var $dbField = array('name', 'url', 'date'); //An array of fields that the script saves the information to
	var $webURL; //The formatted web URL you saved the images to

	
	/********** File Upload Functions ***********/
	
	function get_ext($from_file) { //Function returns the extension from the '.' on
		$ext = strtolower(strrchr($from_file,'.'));
		return $ext;
	}
	
	function error_text($br = "<br />"){ //Function creates errorText variable from error array. New line created for each error array element.
		$msg_string = '';
		foreach ($this->error as $value) {
			$msg_string .= $value.$br;
		}
		return $msg_string;
	}
	
	function max_size(){
		$filesize = $_FILES[$this->uploadName]['size'];
		if ($filesize > $this->maxSize){
			$this->error[] = "The file exceeds the maximum file size";
			return false;	
		}else{
			return true;	
		}
	}
	
	function check_convert(){ //If user isn't going to be converting files, set conFormat to current extension
		if(!isset($this->conFormat) || $this->conFormat == ''){
			$this->conFormat = $this->get_ext($this->theFile);
			echo "conFormat set to: ".$this->get_ext($this->theFile);
		}
	}
	
	function rename_file(){ //Function renames the file to the set var newName
		if ($this->renameFile == true){
			$originalName = $this->theFile;
			if ($this->newName == '' || !isset($this->newName)){ //If newName is left blank, create a name via timestamp. Each subsequent file uploaded will there for have a unique time stamp as it's name.
				$time = strtotime('Now');
				$this->newName = $time;
				$this->theFile = $time.$this->get_ext($this->theFile);
				return true;	
			}else{ //What this is going to do is check if the new file name exists. If it does, it's going to append a fileNum to the end and keep checking until the newName + the fileName isn' found, and then rename the file accordingly.
				$theFile = $this->uploadDir.$this->newName.$this->conFormat;
				if (file_exists($theFile)){
					$theFile = $this->uploadDir.$this->newName."_".$this->fileNum.$this->conFormat;
					while (file_exists($theFile)){
						$this->fileNum++;
						$theFile = $this->uploadDir.$this->newName."_".$this->fileNum.$this->conFormat;
					}
					$this->newName = $this->newName."_".$this->fileNum;	
					$this->theFile = $this->newName.$this->get_ext($this->theFile);	
				}else{
					$this->theFile = $this->newName.$this->get_ext($this->theFile);
				}
				$this->error[] = $originalName." was renamed to ".$this->theFile;
				return true;
			}
		}else{
			//No file rename will take place
		}
	}
	
	function show_ext() { //Function just implodes array of allowed extensions into a string
		$this->extString = implode(', ', $this->extensions);
	}
	
	function validate_ext() { //Function validates that the files extension matches the list of allowed extensions
		$extension = $this->get_ext($this->theFile);
		$ext_array = $this->extensions;
		if (in_array($extension, $ext_array)) { //Check if file's ext is in the list of allowed exts
			return true;
		} else {
			$this->error[] = "That file type is not supported. The supported file types are: ".$this->extString;
			return false;
		}
	}
	
	function check_dir($directory) { //Function checks if the uploadDir exists, if not, it creates it now.
		if(!file_exists($directory)) {	
			if(!mkdir($directory, $this->dirperm, true)){
				$this->error[] = "Failed to create ".$directory." directory";
				return false;
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
	
	function check_file_name($fileName) { //Function checks for blank filename first to determine if a file was uploaded. Then checks for valid file name if doNameCheck is true.
		if ($fileName != '' || !isset($fileName)) {
			if (strlen($fileName) > $this->maxFileName) {
				$this->error[] = "The file name is too long. The max length is: ".$this->maxFileName;
				return false;
			}else{
				if ($this->doNameCheck = true) {
					if (preg_match('/^[a-z0-9_]*\.(.){1,5}$/i', $fileName)) {
						return true;
					} else {
						$this->error[] = "The filename does not appear to be valid. Please rename the file and try again.";
						return false;
					}	
				}else{
					return true;
				}
			}
		}else{
			$this->error[] = "Please select a file to upload";
			return false;
		}
	}	
	
	function file_upload_error_message($error_code) { //Switch function to handle built in HTTP POST errors.
		switch ($error_code) { 
			case UPLOAD_ERR_INI_SIZE: 
				return 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; 
			case UPLOAD_ERR_FORM_SIZE: 
				return 'The uploaded file exceeds the max file size'; 
			case UPLOAD_ERR_PARTIAL: 
				return 'The uploaded file was only partially uploaded'; 
			case UPLOAD_ERR_NO_FILE: 
				return 'No file was uploaded'; 
			case UPLOAD_ERR_NO_TMP_DIR: 
				return 'Missing a temporary folder'; 
			case UPLOAD_ERR_CANT_WRITE: 
				return 'Failed to write file to disk'; 
			case UPLOAD_ERR_EXTENSION: 
				return 'File upload stopped by extension'; 
			default: 
				return 'Unknown upload error'; 
		} 	
	}
			
	function upload($rename = true, $validate = true, $db_entry = true){ //Procederal function brings it all together and uploads the file. Tests for false, if false, die and create errorText var.
		$this->show_ext();
		$this->check_convert();
		if (!$this->check_file_name($this->theFile)){
			unlink($this->theFile);	
			die($this->error_text());
		}
		if (!$this->max_size()){
			unlink($this->theFile);	
			die($this->error_text());	
		}
		if (!$this->check_dir($this->uploadDir)) {
			unlink($this->theFile);	
			die($this->error_text());	
		}
		if ($validate == true){
			if (!$this->validate_ext()){
				$this->show_ext();
				unlink($this->theFile);	
				die($this->error_text());	
			}
		}
		if ($rename == true){
			if (!$this->rename_file()){
				unlink($this->theFile);	
				die($this->error_text());	
			}
		}
		if ($_FILES[$this->uploadName]['error'] === UPLOAD_ERR_OK){
			$newfile = $this->uploadDir.$this->theFile;
			$origName = $this->theFile;
			if (!move_uploaded_file($this->tempFile, $newfile)) {
				$this->error[] = "The file could not be moved to the new directory. Check permissions and folder paths.";
				unlink($this->theFile);	
				die($this->error_text());	
			}else{
				$this->error[] = "The file ".$origName." was successfully uploaded.";
				chmod($newfile , $this->fileperm);
			}
		}else{
			$this->error[] = $this->file_upload_error_message($_FILES[$this->uploadName]['error']);
			unlink($this->theFile);	
			die($this->error_text());
		}
		if ($db_entry == true){
			$thePath = $this->webURL.$this->newName.$this->conFormat;
			$timestamp = date('c');
			$timestamp = explode('T', $timestamp);
			$datestamp = $timestamp[0];
			$timestamp = explode('+', $timestamp[1]);
			$timestamp = $timestamp[0];			
			$insertQry = sprintf("INSERT INTO %s (%s, %s, %s) VALUES ('%s','%s','%s')",
				mysql_real_escape_string($this->dbTable), 
				mysql_real_escape_string($this->dbField['name']), 
				mysql_real_escape_string($this->dbField['url']), 
				mysql_real_escape_string($this->dbField['date']), 
				mysql_real_escape_string($this->newName.$this->conFormat),
				mysql_real_escape_string($thePath),
				mysql_real_escape_string($datestamp." ".$timestamp));
			$insertResult = mysql_query($insertQry);
			if (!$insertResult) {
				die('Invalid query: ' . mysql_error());
			}else{
				$this->error[] = "Image information successfully inserted into database";	
			}
		}
	}
	
}

?>