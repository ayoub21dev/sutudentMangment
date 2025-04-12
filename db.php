<?php

$host = 'localhost';
$dbname = 'student_db';
$username = 'root';
$password = '';

// نحاول نربط الاتصال
$conn = new mysqli($host, $username, $password, $dbname);

// التحقق واش الاتصال خدام
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
