<?php

include ("$DOCUMENT_ROOT/../php/classes/php_magick_upload/file_upload_class.php");
include ("$DOCUMENT_ROOT/../php/classes/php_magick_upload/image_manip_class.php");
$file = new image_upload;

//File Upload Vars

$file->theFile = $_FILES['upload']['name'];
$file->tempFile = $_FILES['upload']['tmp_name'];
$file->uploadName = 'upload';
$file->maxSize = 1572864;
$file->renameFile = true;
$file->extensions = array(".jpg", ".png", ".gif", ".bmp");
$file->newName = "image";
$file->fileNum = 1;
$file->uploadDir = "$DOCUMENT_ROOT/../thehoppr.com/testing/results/";
$file->conFormat = ".jpg"; //Even know this is an image manipulation function, it needs to reside in the file upload vars so the renaming function works.

//Image Manipulation Vars

$file->constrain = true;
$file->thumbDir = "$DOCUMENT_ROOT/../thehoppr.com/testing/results/thumb/";
$file->sharpen = true;
$file->max_X = 800;
$file->max_Y = 600;
$file->min_X = 300;
$file->min_Y = 300;

//Perform functions

$file->upload(true, true);
$file->perform_manipulations(true, true, true);

//Error Output

$errormsg = $file->error_text();
echo $errormsg;

?>