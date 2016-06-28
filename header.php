<?php

require_once 'db.php';

set_error_handler('onError');

function onError($level, $message, $file, $line, $context) {
    die($line . ': ' . $message);
}

function __autoload($class) {
    require_once 'class/' . strtolower($class) . '.php';
}
