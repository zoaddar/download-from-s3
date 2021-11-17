<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/download.php';


$save_to   = __DIR__ . '/temp';
$region    = 'ap-northeast-1';
$version   = 'latest';
$s3_key    = '';
$s3_secret = '';
$s3_bucket = '';

$download = new Download($region, $version, $s3_key, $s3_secret);
$download->s3_bucket = $s3_bucket;
$download->save_to = $save_to;
$download->file_version = true;
$download->s3_folder = '';    // Optional
$download->start_date = '';   // Optional format: Y-m-d
$download->end_date = '';     // Optional format: Y-m-d
$download->fromS3();