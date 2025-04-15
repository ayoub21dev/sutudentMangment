<?php

include 'db.php';


$message = '';



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {

    // الحصول على البيانات من الفورم مع استخدام Null Coalescing Operator للأمان
    $name = $_POST['namedb'] ?? '';
    $email = $_POST['email'] ?? '';
    $age = $_POST['age'] ?? null;

    // التحقق من صحة البيانات المدخلة (غير فارغة، بريد إلكتروني صالح، عمر رقمي)
    if (!empty($name) && !empty($email) && $age !== null && filter_var($email, FILTER_VALIDATE_EMAIL) && is_numeric($age)) {

        // -------------------------------------------------------------
        // استخدام Prepared Statements للحماية من SQL Injection
        // -------------------------------------------------------------
        $sql_insert = "INSERT INTO student (name, email, age) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);

        // التحقق من نجاح تحضير الاستعلام
        if ($stmt_insert) {
            // ربط البارامترات بالمتغيرات (s = string, i = integer)
            // "ssi" تعني أن المتغير الأول نصي، الثاني نصي، الثالث رقمي
            $stmt_insert->bind_param("ssi", $name, $email, $age);

            // تنفيذ الاستعلام
            if ($stmt_insert->execute()) {
                $message = "تم إضافة الطالب بنجاح!";
                // --- اختيارية ---
                // إعادة التوجيه لنفس الصفحة لمنع إعادة إرسال الفورم عند تحديث الصفحة
                // header("Location: " . $_SERVER['PHP_SELF']);
                // exit();
                // -------------
            } else {
                // رسالة خطأ في حال فشل التنفيذ
                $message = "حدث خطأ أثناء إضافة الطالب: " . $stmt_insert->error;
            }
            // إغلاق الـ statement الخاص بالإضافة لتحرير الموارد
            $stmt_insert->close();
        } else {
             // رسالة خطأ في حال فشل تحضير الاستعلام
             $message = "خطأ في تحضير استعلام الإضافة: " . $conn->error;
        }

    } else {
        // رسالة خطأ في حال كانت البيانات المدخلة غير صالحة
        $message = "المرجو ملء جميع الحقول بشكل صحيح (الاسم، بريد إلكتروني صالح، عمر رقمي).";
    }
}


// التحقق من أن الطلب من نوع GET ويحتوي على 'action=delete' و 'id'
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {

    // الحصول على ID الطالب المراد حذفه
    $id_to_delete = $_GET['id'];

    // التحقق من أن الـ ID هو رقم صحيح
    if (is_numeric($id_to_delete)) {

        // -------------------------------------------------------------
        // استخدام Prepared Statements للحماية من SQL Injection
        // -------------------------------------------------------------
        // تأكد من أن اسم عمود الـ ID في جدولك هو 'id'
        $sql_delete = "DELETE FROM student WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);

        // التحقق من نجاح تحضير استعلام الحذف
        if ($stmt_delete) {
             // ربط بارامتر الـ ID (i = integer)
             $stmt_delete->bind_param("i", $id_to_delete);

             // تنفيذ استعلام الحذف
             if ($stmt_delete->execute()) {
                 $message = "تم حذف الطالب بنجاح!";
                 // --- اختيارية ---
                 // إعادة التوجيه لنفس الصفحة لتنظيف الرابط من بارامترات GET
                 // header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                 // exit();
                 // -------------
             } else {
                 // رسالة خطأ في حال فشل تنفيذ الحذف
                 $message = "حدث خطأ أثناء حذف الطالب: " . $stmt_delete->error;
             }
             // إغلاق الـ statement الخاص بالحذف
             $stmt_delete->close();
        } else {
            // رسالة خطأ في حال فشل تحضير استعلام الحذف
            $message = "خطأ في تحضير استعلام الحذف: " . $conn->error;
        }
    } else {
        // رسالة خطأ إذا كان الـ ID غير صالح
        $message = "معرف الطالب غير صالح للحذف.";
    }
}


// ---------------------------------------------------------------------
// جلب جميع الطلاب من قاعدة البيانات لعرضهم
// ---------------------------------------------------------------------
$students = []; // تهيئة مصفوفة فارغة لتخزين بيانات الطلاب
// تأكد من أن اسم عمود الـ ID هو 'id'
// ORDER BY id DESC لجعل الطلاب المضافين حديثاً يظهرون في الأعلى
$sql_select = "SELECT id, name, email, age FROM student ORDER BY id DESC";
$result = $conn->query($sql_select); // تنفيذ الاستعلام

// التحقق من وجود نتائج وأن الاستعلام لم ينتج عنه خطأ
if ($result && $result->num_rows > 0) {
    // جلب جميع الصفوف ووضعها في المصفوفة $students بصيغة associative array
    $students = $result->fetch_all(MYSQLI_ASSOC);
} elseif ($conn->error) {
    // إضافة رسالة خطأ إلى $message إذا حدث خطأ أثناء جلب البيانات
    // نستخدم .= لإضافة الرسالة إلى أي رسالة موجودة مسبقاً (مثلاً من عملية إضافة أو حذف)
    $message .= " خطأ في جلب بيانات الطلاب: " . $conn->error;
}

// ---------------------------------------------------------------------
// إغلاق الاتصال بقاعدة البيانات بعد الانتهاء من جميع العمليات
// ---------------------------------------------------------------------
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلاب</title>
    <style>
        /* --- تنسيقات CSS لتحسين المظهر --- */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* خط أفضل */
            margin: 0;
            padding: 0;
            background-color: #eef2f7; /* لون خلفية أفتح */
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 900px; /* عرض أكبر قليلاً */
            margin: 30px auto; /* هامش أكبر */
            background: #ffffff; /* خلفية بيضاء للمحتوى */
            padding: 30px; /* حشوة داخلية أكبر */
            border-radius: 10px; /* حواف دائرية أكثر */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* ظل أوضح */
        }
        h1, h2 {
            text-align: center;
            color: #4a5568; /* لون أغمق للعناوين */
            margin-bottom: 25px; /* هامش أكبر تحت العناوين */
        }
        form, .student-list {
            margin-bottom: 30px; /* هامش أكبر بين الأقسام */
            padding: 25px; /* حشوة داخلية أكبر */
            border: 1px solid #e2e8f0; /* حدود أفتح */
            border-radius: 8px; /* حواف دائرية */
            background-color: #f7fafc; /* خلفية فاتحة جداً للأقسام */
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600; /* خط أثقل لل labels */
            color: #4a5568;
        }
        input[type="text"],
        input[type="email"],
        input[type="number"] {
            width: calc(100% - 24px); /* تعديل العرض ليناسب الحشوة */
            padding: 12px; /* حشوة أكبر داخل الحقول */
            margin-bottom: 20px; /* هامش أكبر تحت الحقول */
            border: 1px solid #cbd5e0; /* حدود أفتح للحقول */
            border-radius: 6px; /* حواف دائرية للحقول */
            font-size: 1em;
            transition: border-color 0.3s ease; /* تأثير انتقالي عند التركيز */
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus {
            border-color: #4299e1; /* تغيير لون الحدود عند التركيز */
            outline: none; /* إزالة المخطط الافتراضي */
        }
        button[type="submit"] {
            background-color: #48bb78; /* لون أخضر جذاب */
            color: white;
            padding: 12px 25px; /* حشوة أكبر للزر */
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.05em; /* حجم خط أكبر قليلاً */
            transition: background-color 0.3s ease; /* تأثير انتقالي عند المرور */
        }
        button[type="submit"]:hover {
            background-color: #38a169; /* لون أغمق عند المرور */
        }
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 6px;
            text-align: center;
            font-weight: 500; /* وزن خط متوسط للرسائل */
            font-size: 1.0em;
        }
        .success {
            background-color: #c6f6d5; /* أخضر فاتح للنجاح */
            color: #2f855a; /* أخضر أغمق للنص */
            border: 1px solid #9ae6b4;
        }
        .error {
            background-color: #fed7d7; /* أحمر فاتح للخطأ */
            color: #c53030; /* أحمر أغمق للنص */
            border: 1px solid #feb2b2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* ظل خفيف للجدول */
        }
        th, td {
            border: 1px solid #e2e8f0; /* حدود أفتح للخلايا */
            padding: 14px; /* حشوة أكبر للخلايا */
            text-align: right;
            vertical-align: middle; /* محاذاة عمودية للوسط */
        }
        th {
            background-color: #f7fafc; /* خلفية فاتحة جداً لرأس الجدول */
            color: #4a5568; /* لون نص رأس الجدول */
            font-weight: 600; /* خط أثقل لرأس الجدول */
        }
        tr:nth-child(even) {
            background-color: #f7fafc; /* خلفية فاتحة للصفوف الزوجية */
        }
        tr:hover {
            background-color: #edf2f7; /* تغيير لون الخلفية عند مرور الماوس */
        }
        .actions a, .actions button {
            margin-left: 8px;
            text-decoration: none;
            padding: 7px 12px; /* حشوة مناسبة للأزرار */
            border-radius: 5px; /* حواف دائرية للأزرار */
            font-size: 0.9em;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease; /* تأثيرات انتقالية */
            display: inline-block; /* لضمان تطبيق الحشوة والهامش بشكل صحيح */
        }
        .actions a:active, .actions button:active {
             transform: scale(0.95); /* تأثير بسيط عند الضغط */
        }
        .edit-btn {
            background-color: #ecc94b; /* لون أصفر للتعديل */
        }
        .edit-btn:hover {
            background-color: #d69e2e; /* أصفر أغمق عند المرور */
        }
        .delete-link {
            background-color: #f56565; /* لون أحمر للحذف */
        }
        .delete-link:hover {
            background-color: #e53e3e; /* أحمر أغمق عند المرور */
        }
        /* تنسيق خاص للهواتف */
        @media (max-width: 600px) {
            .container {
                margin: 15px;
                padding: 20px;
            }
            h1 { font-size: 1.5em; }
            h2 { font-size: 1.3em; }
            input[type="text"],
            input[type="email"],
            input[type="number"],
            button[type="submit"] {
                font-size: 0.95em;
                padding: 10px;
            }
            table, thead, tbody, th, td, tr {
                display: block; /* تغيير عرض الجدول ليناسب الشاشات الصغيرة */
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px; /* إخفاء رأس الجدول التقليدي */
            }
            tr { border: 1px solid #ccc; margin-bottom: 10px; border-radius: 5px; background-color: #fff; }
            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%; /* مساحة لوضع عنوان العمود */
                text-align: left; /* محاذاة لليسار لتناسب العنوان */
            }
            td:before {
                /* استخدام خاصية data-* لوضع عنوان العمود */
                position: absolute;
                top: 12px;
                left: 12px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                content: attr(data-label); /* جلب النص من data-label */
                font-weight: bold;
                text-align: right; /* محاذاة لليمين للعنوان */
            }
             /* إضافة data-label لكل خلية بيانات */
            td[data-label="الاسم"]::before { content: "الاسم:"; }
            td[data-label="البريد الإلكتروني"]::before { content: "البريد:"; }
            td[data-label="العمر"]::before { content: "العمر:"; }
            td[data-label="إجراءات"]::before { content: "إجراءات:"; }

            .actions a, .actions button {
                display: inline-block; /* عرض الأزرار بشكل مناسب */
                margin-bottom: 5px; /* هامش أسفل الأزرار */
            }
             td.actions {
                 padding-left: 12px; /* إعادة ضبط الحشوة لخلية الإجراءات */
                 text-align: right; /* إعادة المحاذاة لليمين */
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>إدارة بيانات الطلاب</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'بنجاح') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); // استخدام htmlspecialchars لعرض الرسائل بأمان ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); // يرسل البيانات لنفس الصفحة بأمان ?>" method="post">
            <h2>إضافة طالب جديد</h2>
            <input type="hidden" name="add_student" value="1">

            <div>
                <label for="namedb">الاسم:</label>
                <input type="text" id="namedb" name="namedb" required>
            </div>

            <div>
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div>
                <label for="age">العمر:</label>
                <input type="number" id="age" name="age" required min="1"> </div>

            <button type="submit">إضافة الطالب</button>
        </form>

        <div class="student-list">
            <h2>قائمة الطلاب المسجلين</h2>
            <?php if (!empty($students)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>العمر</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td data-label="الاسم"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td data-label="البريد الإلكتروني"><?php echo htmlspecialchars($student['email']); ?></td>
                                <td data-label="العمر"><?php echo htmlspecialchars($student['age']); ?></td>
                                <td data-label="إجراءات" class="actions">
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="edit-btn">تعديل</a>

                                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?action=delete&id=<?php echo $student['id']; ?>"
                                       class="delete-link"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟ سيتم حذفه نهائياً.');">حذف</a>

                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #718096; margin-top: 20px;">لا يوجد طلاب مسجلون لعرضهم حالياً.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // مثال: جعل رسائل التنبيه تختفي بعد فترة
        const messageElement = document.querySelector('.message');
        if (messageElement) {
            setTimeout(() => {
                messageElement.style.transition = 'opacity 0.5s ease';
                messageElement.style.opacity = '0';
                setTimeout(() => messageElement.remove(), 500); // إزالة العنصر بعد انتهاء الانتقال
            }, 5000); // إخفاء الرسالة بعد 5 ثواني
        }
    </script>

</body>
</html>

