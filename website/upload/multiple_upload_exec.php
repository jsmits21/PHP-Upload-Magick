<?php

//Include upload classes
include ("$DOCUMENT_ROOT/../php/classes/php_magick_upload/file_upload_class.php");
include ("$DOCUMENT_ROOT/../php/classes/php_magick_upload/image_manip_class.php");
include ("$DOCUMENT_ROOT/../php/database/connection.php"); //This is a simple database connection script. Feel free to use your own database connection script.
$file = new image_upload;

//File Upload Vars

$file->theFile = $_FILES['Filedata']['name'];
$file->tempFile = $_FILES['Filedata']['tmp_name'];
$file->uploadName = 'Filedata';
$file->maxSize = 1572864;
$file->renameFile = true;
$file->extensions = array(".jpg", ".png", ".gif", ".bmp");
$file->newName = "image";
$file->uploadDir = "$DOCUMENT_ROOT/../thehoppr.com/testing/results/";
$file->conFormat = ".jpg";

//Image Manipulation Vars

$file->constrain = true;
$file->thumbDir = "$DOCUMENT_ROOT/../thehoppr.com/testing/results/thumb/";
$file->sharpen = true;
$file->max_X = 800;
$file->max_Y = 600;
$file->min_X = 300;
$file->min_Y = 300;

//Database Entry Vars

$file->dbTable = 'images';
$file->dbField['name'] = 'imgName';
$file->dbField['url'] = 'imgURL';
$file->dbField['date'] = 'imgDate';
$file->webURL = 'www.thehoppr.com/testing/results/';

//Perform functions
$file->upload(true, true, true);
$file->perform_manipulations(true, true, true);

//Error Output
$errormsg = $file->error_text();
header("HTTP/1.1 201 Uploaded File was Successful");
echo $errormsg;
?>