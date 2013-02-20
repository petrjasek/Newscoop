<?php

$static = array(
    'css',
    'js',
    'jpg',
    'png',
    'gif',
    'bmp',
);

$ext = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_EXTENSION);
if (in_array($ext, $static) && file_exists($_SERVER['SCRIPT_FILENAME'])) {
    return false;
}

if (strlen($_SERVER['SCRIPT_NAME']) >= 6 && substr($_SERVER['SCRIPT_NAME'], 0, 6) === '/admin') {
    // rewrite
    $_SERVER['SCRIPT_NAME'] = '/admin.php';
}

require __DIR__ . '/public/index.php';
