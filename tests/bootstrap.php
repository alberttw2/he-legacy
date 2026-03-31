<?php

// Simulate web environment for tests
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';

if (!isset($_SESSION)) {
    session_start();
}

require_once dirname(__DIR__) . '/config.php';
