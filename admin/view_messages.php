<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Get admin data
$admin = $_SESSION['admin'];

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = ''; // Default WAMP password is empty

// Initialize variables
$messages = [];
$error = '';
$success = '';

// Initialize statistics array with default values
$stats = [
    'total' => 0,
    'new' => 0,
    'read' => 0,
    'responded' => 0
];

// Process actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    try {
        // Connect to database
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action === 'mark_read') {
            $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $success = 'تم تحديث حالة الرسالة إلى مقروءة';
        } elseif ($action === 'mark_responded') {
            $stmt = $conn->prepare("UPDATE contact_messages SET status = 'responded' WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $success = 'تم تحديث حالة الرسالة إلى تم الرد';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $success = 'تم حذف الرسالة بنجاح';
        }
        
        // Redirect to avoid resubmission
        if (!isset($_GET['view'])) {
            header('Location: view_messages.php');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Fetch messages
try {
    // Check if table exists
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result = $conn->query("SHOW TABLES LIKE 'contact_messages'")->fetchAll();
    if (count($result) > 0) {
        // Get message stats
        $stats = [
            'total' => 0,
            'new' => 0,
            'read' => 0,
            'responded' => 0,
        ];
        
        // Total count
        $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages");
        $stats['total'] = $stmt->fetchColumn();
        
        // New count
        $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
        $stats['new'] = $stmt->fetchColumn();
        
        // Read count
        $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'read'");
        $stats['read'] = $stmt->fetchColumn();
        
        // Responded count
        $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'responded'");
        $stats['responded'] = $stmt->fetchColumn();
        
        // Get message details if specific message is requested
        if (isset($_GET['view'])) {
            $message_id = (int)$_GET['view'];
            $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = :id");
            $stmt->bindParam(':id', $message_id);
            $stmt->execute();
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Mark as read if it's new
            if ($message && $message['status'] === 'new') {
                $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = :id");
                $stmt->bindParam(':id', $message_id);
                $stmt->execute();
                $message['status'] = 'read';
            }
        }
        
        // Get all messages
        $stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'messages';

// Include header
include 'includes/header.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة رسائل الاتصال</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .setup-link {
            display: inline-block;
            margin: 10px 0 20px;
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .back-link {
            display: inline-block;
            margin: 10px 10px 20px 0;
            padding: 10px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        th, td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-new {
            background-color: #ffc107;
            padding: 5px 10px;
            border-radius: 4px;
            color: #000;
            font-weight: bold;
        }
        .status-read {
            background-color: #17a2b8;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        .status-responded {
            background-color: #28a745;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-block;
        }
        .read-btn {
            background-color: #17a2b8;
            color: white;
        }
        .respond-btn {
            background-color: #28a745;
            color: white;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .message {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .no-messages {
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            margin-top: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .truncate {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 700px;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: left;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .message-content {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-envelope text-primary mr-2"></i>رسائل الاتصال</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item active">رسائل الاتصال</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>إجمالي الرسائل</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $stats['new']; ?></h3>
                            <p>رسائل جديدة</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?php echo $stats['read']; ?></h3>
                            <p>رسائل مقروءة</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-envelope-open"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $stats['responded']; ?></h3>
                            <p>تم الرد عليها</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-reply"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['view']) && isset($message)): ?>
            <!-- Single Message View -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-envelope-open mr-2"></i>
                        <?php echo htmlspecialchars($message['subject']); ?>
                    </h3>
                    <div>
                        <?php if ($message['status'] === 'new'): ?>
                        <span class="badge badge-warning">جديد</span>
                        <?php elseif ($message['status'] === 'read'): ?>
                        <span class="badge badge-info">مقروء</span>
                        <?php elseif ($message['status'] === 'responded'): ?>
                        <span class="badge badge-success">تم الرد</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><i class="fas fa-user mr-2"></i>المرسل:</strong> <?php echo htmlspecialchars($message['name']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="fas fa-envelope mr-2"></i>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($message['email']); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><i class="fas fa-calendar-alt mr-2"></i>تاريخ الإرسال:</strong> <?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="fas fa-phone mr-2"></i>رقم الهاتف:</strong> <?php echo !empty($message['phone']) ? htmlspecialchars($message['phone']) : 'غير متوفر'; ?>
                        </div>
                    </div>
                    <div class="message-content mt-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">محتوى الرسالة:</h5>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="view_messages.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right ml-1"></i> العودة للقائمة
                    </a>
                    <?php if ($message['status'] !== 'responded'): ?>
                    <a href="?action=mark_responded&id=<?php echo $message['id']; ?>&view=<?php echo $message['id']; ?>" class="btn btn-success">
                        <i class="fas fa-reply mr-1"></i> تحديد كتم الرد
                    </a>
                    <?php endif; ?>
                    <a href="mailto:<?php echo $message['email']; ?>?subject=رد: <?php echo htmlspecialchars($message['subject']); ?>" class="btn btn-primary">
                        <i class="fas fa-envelope mr-1"></i> الرد بالبريد الإلكتروني
                    </a>
                    <a href="?action=delete&id=<?php echo $message['id']; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه الرسالة؟');">
                        <i class="fas fa-trash mr-1"></i> حذف الرسالة
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Messages List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-envelope-open mr-2"></i>قائمة رسائل الاتصال</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th style="width: 40px">#</th>
                                    <th>المرسل</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الموضوع</th>
                                    <th>التاريخ</th>
                                    <th>الحالة</th>
                                    <th style="width: 150px">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد رسائل اتصال حتى الآن</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($messages as $index => $msg): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                    <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                    <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></td>
                                    <td>
                                        <?php if ($msg['status'] === 'new'): ?>
                                        <span class="badge badge-warning">جديد</span>
                                        <?php elseif ($msg['status'] === 'read'): ?>
                                        <span class="badge badge-info">مقروء</span>
                                        <?php elseif ($msg['status'] === 'responded'): ?>
                                        <span class="badge badge-success">تم الرد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?view=<?php echo $msg['id']; ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="عرض الرسالة">
                                                <i class="fas fa-envelope-open"></i>
                                            </a>
                                            <?php if ($msg['status'] === 'new'): ?>
                                            <a href="?action=mark_read&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-primary" data-toggle="tooltip" title="تحديد كمقروء">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($msg['status'] !== 'responded'): ?>
                                            <a href="?action=mark_responded&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-success" data-toggle="tooltip" title="تحديد كتم الرد">
                                                <i class="fas fa-reply"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-danger" data-toggle="tooltip" title="حذف الرسالة" onclick="return confirm('هل أنت متأكد من حذف هذه الرسالة؟');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Include footer
    include 'includes/footer.php';
    ?>
