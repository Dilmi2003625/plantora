<?php

$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'plantora_db';
$port = 3307;

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    error_log('Plantora database connection failed: '.mysqli_connect_error());
    exit('The site is temporarily unavailable. Please try again later.');
}
