<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['namedb'];
    $email = $_POST['email'];
    $age = $_POST['age'];

    // استخدام prepared statement لحماية البيانات من SQL injection
    $sql = "INSERT INTO student (name, email,age) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $email, $age); // ربط القيم

    if ($stmt->execute()) {
        echo "تم إضافة الطالب بنجاح!";
    } else {
        echo "حدث خطأ أثناء إضافة الطالب: " . $stmt->error;
    }

    // غلق الاتصال بعد تنفيذ الاستعلام
    $stmt->close();
    $conn->close();
}
?>