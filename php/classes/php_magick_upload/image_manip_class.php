<?php

/************************************************************************************************************************************

PHP/Imagick Upload - Version 1.1

A powerful image uploading class that utilizes Image Magick to ensure user uploaded images adhere to your web applications standards.

Copywrite (c) 2011 - Jacob Smits - jsmits21@lavabit.com

This software is free and redistributable. DO NOT DELETE THIS CREDIT SECTION OF TEXT.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 'AS IS' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Special thanks to Olaf Lederer and his Easy PHP Upload class library. This was the inspiration and guiding example for this class.

*************************************************************************************************************************************

Features:

This is the Image Magick manipulation. Use this class if you want to take advantage of these features:

	* Landscape or Portrait resizing with optional ratio constraint
	* Minimum and maximum image dimensions
	* Thumbnail creation
	* And more!
	
*************************************************************************************************************************************

Requirements:

	* Image Magick installation || http://www.imagemagick.org/script/index.php
	* For more info on how you can use Imagick, visit this documentation || http://www.php.net/manual/en/intro.imagick.php

************************************************************************************************************************************/

class image_upload extends file_upload {
	
	var $newFile;
	var $newFileDir;
	var $newFileName;
	var $newFileExt;
	var $thumbDir;
	var $constrain = false; //True or false. Constraint proportions to landscape or portrait dimensions. Prevents image squishing.
	var $image; //The image file
	var $image_X; //Uploaded images x dimension
	var $image_Y; //Uploaded images y dimension
	var $max_X = 800; //Max x pixel size.
	var $max_Y = 800; //Max y pixel size.
	var $min_X = 100; //Min x pixel size. **Note that some image stretching, due to enforcing strict pixel sizes, will occur if image is below minimum sizes. Set to zero to
	var $min_Y = 100; //Min y pixel size.
	var $enlarge = false; //If image meets or exceeds minimum sizes, this flag forces the image to be enlarged to the max size.
	var $sharpen = true; //Set to run sharpen function. Used when photos are enlarged to clean them up a bit.
	var $sharpenAmt = .5; //Floating point number. Determines sharpen amount. Techically any value can be used, but simple decimals work best (A .1 to 1 scale). 
	var $thumb_X = 100; //Max thumb x pixel size.
	var $thumb_Y = 100; //Max thumb y pixel size.
	var $setImage = false; //Experimental: Use if min size is smaller than desired size. Sets original image in a larger file based on your max sizes.
		
	function newfile() { //Function sets newFile name and newFileExt extension. Seperating these two makes it easier to work with the names in manipulation functions. Function must be run in perform_manipulations() function.
		$filename = $this->theFile;
		$newString = explode(".", $filename);
		$this->newFile = $this->uploadDir.$newString[0];
		$this->newFileExt = ".".$newString[1];
	}
	
	function display_array($br = "<br />", $array){ //Function creates html formated string variable from error array. New line created for each error array element.
		$msg_string = '';
		foreach ($array as $value) {
			$msg_string .= $value.$br;
		}
		return $msg_string;
	}
	
	function convert() { //Function converts newfile() to the specified format
		$origFile = $this->newFile.$this->newFileExt;
		$newFile = $this->newFile.$this->conFormat;
		if($this->newFileExt != $this->conFormat){ //Convert format does not equal the original format, perform conversion
			exec("convert $origFile $newFile");
			unlink($origFile);
			$this->error[] = $this->theFile." was converted to ".$this->conFormat." format.";
		}
	}
	
	function get_info(){ //Function uses Image Magick to pull image dimensions into vars via the returned command line output from "identify" option. Function must be run in perform_manipulations();
		$checkFile = $this->newFile.$this->newFileExt;
		exec("identify -verbose $checkFile", $outputAry);
		$getDimens = explode(":", $outputAry[2]);
		$dimensions = ltrim($getDimens[1]);
		$explodeDimens = explode("x", $dimensions);
		$this->image_X = $explodeDimens[0];
		$this->image_Y = $explodeDimens[1];
	}
	
	function orientation(){ //Function determines if the image is landscape or portrait.
		if($this->image_X == '' || $this->image_Y == ''){
			die($this->error[] = "The dimensions of the image are not known. No image manipulation will be performed");
		}else{
			if($this->image_X > $this->image_Y){
				return "landscape";
			}else if ($this->image_X < $this->image_Y){
				return "portrait";
			}else{
				return "square";	
			}
		}
	}
	
	function sharpen(){ //This function sharpens the image. It is normally used in the resize function for images that needed to be enlarged.
		if ($this->sharpen == true){
			$theFile = $this->newFile.$this->newFileExt;
			$sharpenedFile = $this->newFile."_s".$this->newFileExt; //Redundancy. Use this in place of the second $theFile in the exec command to create a new file when it's sharpened.
			$sharpenAmt = $this->sharpenAmt;
			exec("convert $theFile -sharpen 0x{$sharpenAmt} $theFile");
		}else{
			$this->error[] = "Sharpen flag not set. Image ".$this->imgNum." will not be sharpened.";	
		}
	}
	
	function resize(){ //Robust function for image resizing. Setting min values will enforce stricter image size control and will result in stretching if constrain is set to false. All images enlarged will result in some fuzines, set sharpen flag and value to alleviate this.
		$orientation = $this->orientation();
		$theFile = $this->newFile.$this->newFileExt;
		$max_X = $this->max_X;
		$max_Y = $this->max_Y;
		$min_X = $this->min_X;
		$min_Y = $this->min_Y;
		if($this->image_X >= $this->min_X  && $this->image_Y >= $this->min_Y){ //Image is large enough, resize it to the max values if enlarge is set. Else leave image alone.
			if($this->enlarge == true){
				if($this->constrain == true){
					exec("convert $theFile -resize {$max_X}x{$max_Y} $theFile", $outputAry);
				}else{
					exec("convert $theFile -resize {$max_X}x{$max_Y}! $theFile", $outputAry);
				}
			}else if ($this->image_X > $this->max_X  || $this->image_Y >= $this->max_Y){ //Image is too large, resize to max values.
				if($this->constrain == true){
					exec("convert $theFile -resize {$max_X}x{$max_Y} $theFile", $outputAry);
				}else{
					exec("convert $theFile -resize {$max_X}x{$max_Y}! $theFile", $outputAry);
				}
			}
		}else if($this->image_X < $this->min_X  || $this->image_Y < $this->min_Y){ //Image is too small, resize it to the min values
			if($this->constrain == false){
				if ($orientation == "portrait"){
					exec("convert $theFile -resize {$min_X}x{$max_Y}! $theFile", $outputAry);
				}else if ($orientation == "landscape"){
					exec("convert $theFile -resize {$max_X}x{$min_Y}! $theFile", $outputAry);
				}else{
					exec("convert $theFile -resize {$min_X}x{$min_Y}! $theFile", $outputAry);
				}
			}else{
				exec("convert $theFile -resize {$min_X}x{$min_Y} $theFile", $outputAry);
			}
			$this->sharpen();
		}
	}
	
	function create_thumb(){
		$theFile = $this->newFile.$this->newFileExt;
		$theThumb = $this->thumbDir.$this->newName.$this->conFormat;
		$max_X = $this->thumb_X;
		$max_Y = $this->thumb_Y;
		$this->check_dir($this->thumbDir);
		exec("convert $theFile -resize {$max_X}x{$max_Y} $theThumb", $outputAry);
		$this->error[] = "A thumbnail of the image was created.";	
	}
	
	function perform_manipulations($resize = true, $createThumb = true, $convert = true){
		$this->newfile();
		$this->get_info();
		if($resize == true){
			$this->resize();
		}
		if($createThumb == true){
			$this->create_thumb();	
		}
		if ($convert == true){
			$this->convert();
		}
	}			
}

?>