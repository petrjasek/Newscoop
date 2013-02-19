<?php

$whitelist = array(
    'css',
    'js',
    'jpg',
    'png',
    'gif',
    'bmp',
);

$ext = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_EXTENSION);
if (in_array($ext, $whitelist)) {
    return false;
}

require __DIR__ . '/public/index.php';
