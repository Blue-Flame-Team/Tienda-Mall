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
$password = '';

// Initialize variables
$subscribers = [];
$error = '';
$success = '';
$total_subscribers = 0;
$active_subscribers = 0;
$inactive_subscribers = 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new subscriber
    if (isset($_POST['add_subscriber'])) {
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $name = trim($_POST['name']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!$email) {
            $error = 'يرجى إدخال بريد إلكتروني صالح';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM newsletter_subscribers WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'هذا البريد الإلكتروني مشترك بالفعل';
                } else {
                    // Insert new subscriber
                    $stmt = $conn->prepare(
                        "INSERT INTO newsletter_subscribers 
                         (email, name, is_active, subscribe_date) 
                         VALUES 
                         (:email, :name, :is_active, NOW())");
                    
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':is_active', $is_active);
                    $stmt->execute();
                    
                    $success = 'تم إضافة المشترك بنجاح';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    // Send newsletter
    if (isset($_POST['send_newsletter'])) {
        $subject = trim($_POST['subject']);
        $content = trim($_POST['content']);
        $send_to = $_POST['send_to'];
        
        if (empty($subject)) {
            $error = 'يرجى إدخال عنوان للرسالة';
        } else if (empty($content)) {
            $error = 'يرجى إدخال محتوى الرسالة';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Get subscribers
                $where_clause = $send_to === 'active' ? ' WHERE is_active = 1' : '';
                $stmt = $conn->query("SELECT email, name FROM newsletter_subscribers" . $where_clause);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($recipients) === 0) {
                    $error = 'لا يوجد مشتركين لإرسال الرسالة إليهم';
                } else {
                    // Record the newsletter in the database
                    $stmt = $conn->prepare(
                        "INSERT INTO newsletters 
                         (subject, content, sent_date, sent_by, recipients_count) 
                         VALUES 
                         (:subject, :content, NOW(), :sent_by, :recipients_count)");
                    
                    $sent_by = $admin['username'];
                    $recipients_count = count($recipients);
                    $stmt->bindParam(':subject', $subject);
                    $stmt->bindParam(':content', $content);
                    $stmt->bindParam(':sent_by', $sent_by);
                    $stmt->bindParam(':recipients_count', $recipients_count);
                    $stmt->execute();
                    
                    // In a real scenario, you would use a proper email sending service here
                    // For demo purposes, we're just simulating it
                    
                    $success = 'تم إرسال النشرة البريدية بنجاح إلى ' . $recipients_count . ' مشترك';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// Handle subscriber actions (delete, activate, deactivate)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $subscriber_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action === 'delete') {
            // Delete subscriber
            $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = :id");
            $stmt->bindParam(':id', $subscriber_id);
            $stmt->execute();
            $success = 'تم حذف المشترك بنجاح';
        } else if ($action === 'activate') {
            // Activate subscriber
            $stmt = $conn->prepare("UPDATE newsletter_subscribers SET is_active = 1 WHERE id = :id");
            $stmt->bindParam(':id', $subscriber_id);
            $stmt->execute();
            $success = 'تم تفعيل اشتراك المشترك بنجاح';
        } else if ($action === 'deactivate') {
            // Deactivate subscriber
            $stmt = $conn->prepare("UPDATE newsletter_subscribers SET is_active = 0 WHERE id = :id");
            $stmt->bindParam(':id', $subscriber_id);
            $stmt->execute();
            $success = 'تم إلغاء تفعيل اشتراك المشترك بنجاح';
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get subscribers and stats
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if tables exist
    $tables = $conn->query("SHOW TABLES LIKE 'newsletter_subscribers'")->fetchAll();
    if (count($tables) === 0) {
        // Create newsletter_subscribers table if it doesn't exist
        $conn->exec("CREATE TABLE newsletter_subscribers (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            subscribe_date DATETIME NOT NULL,
            last_updated DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create newsletters table if it doesn't exist
        $conn->exec("CREATE TABLE newsletters (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            subject VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            sent_date DATETIME NOT NULL,
            sent_by VARCHAR(255) NOT NULL,
            recipients_count INT(11) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $success = 'تم إنشاء جداول النشرة البريدية بنجاح';
    } else {
        // Get stats
        $stmt = $conn->query("SELECT COUNT(*) as total FROM newsletter_subscribers");
        $total_subscribers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $conn->query("SELECT COUNT(*) as active FROM newsletter_subscribers WHERE is_active = 1");
        $active_subscribers = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        $stmt = $conn->query("SELECT COUNT(*) as inactive FROM newsletter_subscribers WHERE is_active = 0");
        $inactive_subscribers = $stmt->fetch(PDO::FETCH_ASSOC)['inactive'];
        
        // Get all subscribers
        $stmt = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY subscribe_date DESC");
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'newsletter';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-envelope-open-text text-primary mr-2"></i>إدارة النشرة البريدية</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">النشرة البريدية</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
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
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">إجمالي المشتركين</h6>
                                <h2 class="mb-0"><?php echo $total_subscribers; ?></h2>
                            </div>
                            <div class="icon-circle bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">المشتركين النشطين</h6>
                                <h2 class="mb-0"><?php echo $active_subscribers; ?></h2>
                            </div>
                            <div class="icon-circle bg-success">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">المشتركين غير النشطين</h6>
                                <h2 class="mb-0"><?php echo $inactive_subscribers; ?></h2>
                            </div>
                            <div class="icon-circle bg-danger">
                                <i class="fas fa-user-times"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <!-- Add Subscriber Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>إضافة مشترك جديد</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="name">الاسم</label>
                                <input type="text" class="form-control" id="name" name="name">
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="is_active">نشط</label>
                                </div>
                            </div>
                            <button type="submit" name="add_subscriber" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> إضافة مشترك
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Send Newsletter Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-paper-plane mr-2"></i>إرسال نشرة بريدية</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="subject">عنوان الرسالة <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label for="content">محتوى الرسالة <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="8" required></textarea>
                                <small class="form-text text-muted">يمكنك استخدام HTML لتنسيق الرسالة</small>
                            </div>
                            <div class="form-group">
                                <label>إرسال إلى</label>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="send_all" name="send_to" value="all" class="custom-control-input" checked>
                                    <label class="custom-control-label" for="send_all">جميع المشتركين</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="send_active" name="send_to" value="active" class="custom-control-input">
                                    <label class="custom-control-label" for="send_active">المشتركين النشطين فقط</label>
                                </div>
                            </div>
                            <button type="submit" name="send_newsletter" class="btn btn-success btn-block">
                                <i class="fas fa-paper-plane mr-1"></i> إرسال النشرة البريدية
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Subscribers List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>قائمة المشتركين</h3>
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
                                        <th>البريد الإلكتروني</th>
                                        <th>الاسم</th>
                                        <th>تاريخ الاشتراك</th>
                                        <th>الحالة</th>
                                        <th style="width: 150px">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($subscribers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">لا يوجد مشتركين حتى الآن</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($subscribers as $index => $subscriber): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                        <td><?php echo !empty($subscriber['name']) ? htmlspecialchars($subscriber['name']) : '<span class="text-muted">غير محدد</span>'; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($subscriber['subscribe_date'])); ?></td>
                                        <td>
                                            <?php if ($subscriber['is_active']): ?>
                                                <span class="badge badge-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($subscriber['is_active']): ?>
                                                <a href="?action=deactivate&id=<?php echo $subscriber['id']; ?>" class="btn btn-sm btn-warning" title="إلغاء التفعيل">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="?action=activate&id=<?php echo $subscriber['id']; ?>" class="btn btn-sm btn-success" title="تفعيل">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="?action=delete&id=<?php echo $subscriber['id']; ?>" class="btn btn-sm btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المشترك؟ هذا الإجراء لا يمكن التراجع عنه.')">
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
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
