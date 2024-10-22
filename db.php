<?php

// Get form data
$host = $_POST['host'];
$username = $_POST['username'];
$password = $_POST['password'];
$database = $_POST['database'];

// Connect to the database
$conn = oci_connect($username, $password, "//{$host}/{$database}");

if (!$conn) {
    $e = oci_error();
    die("Could not connect to Oracle: " . $e['message']);
}
