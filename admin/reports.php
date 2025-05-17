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
$error = '';
$success = '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-d', strtotime('-30 days'));
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d');

// Set current page for navigation
$current_page = 'reports';

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Dashboard Statistics
    $total_sales = 0;
    $total_revenue = 0;
    $total_orders = 0;
    $total_customers = 0;
    $avg_order_value = 0;
    
    // Get total sales for the selected period
    $stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue 
                         FROM orders 
                         WHERE created_at BETWEEN :date_start AND :date_end");
    $stmt->bindParam(':date_start', $date_start);
    $stmt->bindParam(':date_end', $date_end);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_orders = $result['total_orders'] ?? 0;
    $total_revenue = $result['total_revenue'] ?? 0;
    
    // Get total customers (users who placed orders) for the selected period
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as total_customers 
                         FROM orders 
                         WHERE user_id IS NOT NULL AND created_at BETWEEN :date_start AND :date_end");
    $stmt->bindParam(':date_start', $date_start);
    $stmt->bindParam(':date_end', $date_end);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_customers = $result['total_customers'] ?? 0;
    
    // Get total guest orders for the selected period
    $stmt = $conn->prepare("SELECT COUNT(*) as total_guest_orders 
                         FROM orders 
                         WHERE user_id IS NULL AND created_at BETWEEN :date_start AND :date_end");
    $stmt->bindParam(':date_start', $date_start);
    $stmt->bindParam(':date_end', $date_end);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_guest_orders = $result['total_guest_orders'] ?? 0;
    
    // Calculate average order value
    $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
    
    // Prepare chart data based on report type
    $chart_data = [];
    $chart_labels = [];
    $chart_values = [];
    
    if ($report_type === 'sales') {
        // Sales report
        if ($period === 'daily') {
            // Daily sales for the selected date range
            $stmt = $conn->prepare(
                "SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as total 
                 FROM orders 
                 WHERE created_at BETWEEN :date_start AND :date_end 
                 GROUP BY DATE(created_at) 
                 ORDER BY date"
            );
            $stmt->bindParam(':date_start', $date_start);
            $stmt->bindParam(':date_end', $date_end);
            $stmt->execute();
            $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($chart_data as $data) {
                $chart_labels[] = date('Y-m-d', strtotime($data['date']));
                $chart_values[] = $data['total'];
            }
        } else if ($period === 'monthly') {
            // Monthly sales for the past year
            $stmt = $conn->prepare(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total 
                 FROM orders 
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                 ORDER BY month"
            );
            $stmt->execute();
            $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($chart_data as $data) {
                $chart_labels[] = date('M Y', strtotime($data['month'] . '-01'));
                $chart_values[] = $data['total'];
            }
        }
    } else if ($report_type === 'products') {
        // Top selling products
        $stmt = $conn->prepare(
            "SELECT p.name, SUM(oi.quantity) as total_quantity, SUM(oi.price * oi.quantity) as total_revenue 
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.product_id 
             JOIN orders o ON oi.order_id = o.order_id 
             WHERE o.created_at BETWEEN :date_start AND :date_end 
             GROUP BY oi.product_id 
             ORDER BY total_quantity DESC 
             LIMIT 10"
        );
        $stmt->bindParam(':date_start', $date_start);
        $stmt->bindParam(':date_end', $date_end);
        $stmt->execute();
        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($chart_data as $data) {
            $chart_labels[] = $data['name'];
            $chart_values[] = $data['total_quantity'];
        }
    } else if ($report_type === 'categories') {
        // Sales by category
        $stmt = $conn->prepare(
            "SELECT c.name, COUNT(o.order_id) as order_count, SUM(oi.price * oi.quantity) as total_revenue 
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.product_id 
             JOIN categories c ON p.category_id = c.category_id 
             JOIN orders o ON oi.order_id = o.order_id 
             WHERE o.created_at BETWEEN :date_start AND :date_end 
             GROUP BY c.category_id 
             ORDER BY total_revenue DESC"
        );
        $stmt->bindParam(':date_start', $date_start);
        $stmt->bindParam(':date_end', $date_end);
        $stmt->execute();
        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($chart_data as $data) {
            $chart_labels[] = $data['name'];
            $chart_values[] = $data['total_revenue'];
        }
    }
    
    // Convert chart data to JSON for JavaScript
    $chart_labels_json = json_encode($chart_labels);
    $chart_values_json = json_encode($chart_values);
    
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-chart-bar text-info mr-2"></i>التقارير والإحصائيات</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">التقارير</li>
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
        
        <!-- Report Filter -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>تصفية التقارير</h3>
            </div>
            <div class="card-body">
                <form method="get" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>نوع التقرير</label>
                            <select name="type" class="form-control" onchange="this.form.submit()">
                                <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>تقرير المبيعات</option>
                                <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>المنتجات الأكثر مبيعاً</option>
                                <option value="categories" <?php echo $report_type === 'categories' ? 'selected' : ''; ?>>المبيعات حسب الفئة</option>
                            </select>
                        </div>
                    </div>
                    <?php if ($report_type === 'sales'): ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>الفترة الزمنية</label>
                            <select name="period" class="form-control" onchange="this.form.submit()">
                                <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>يومي</option>
                                <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>شهري</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>تاريخ البداية</label>
                            <input type="date" name="date_start" class="form-control" value="<?php echo $date_start; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>تاريخ النهاية</label>
                            <input type="date" name="date_end" class="form-control" value="<?php echo $date_end; ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt mr-1"></i> تحديث التقرير
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                                <h2 class="mb-0"><?php echo number_format($total_orders); ?></h2>
                            </div>
                            <div class="icon-circle bg-primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">إجمالي الإيرادات</h6>
                                <h2 class="mb-0"><?php echo number_format($total_revenue, 2); ?> $</h2>
                            </div>
                            <div class="icon-circle bg-success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">متوسط قيمة الطلب</h6>
                                <h2 class="mb-0"><?php echo number_format($avg_order_value, 2); ?> $</h2>
                            </div>
                            <div class="icon-circle bg-info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">عدد العملاء</h6>
                                <h2 class="mb-0"><?php echo number_format($total_customers); ?></h2>
                                <small class="text-muted">+ <?php echo number_format($total_guest_orders); ?> زائر</small>
                            </div>
                            <div class="icon-circle bg-warning">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <?php if ($report_type === 'sales'): ?>
                        <i class="fas fa-chart-line mr-2"></i>تقرير المبيعات (<?php echo $period === 'daily' ? 'يومي' : 'شهري'; ?>)
                    <?php elseif ($report_type === 'products'): ?>
                        <i class="fas fa-chart-bar mr-2"></i>المنتجات الأكثر مبيعاً
                    <?php elseif ($report_type === 'categories'): ?>
                        <i class="fas fa-chart-pie mr-2"></i>المبيعات حسب الفئة
                    <?php endif; ?>
                </h3>
            </div>
            <div class="card-body">
                <div style="height: 400px;">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>
        </div>
        
        <?php if (!empty($chart_data) && ($report_type === 'products' || $report_type === 'categories')): ?>
        <!-- Detailed Data Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table mr-2"></i>
                    <?php echo $report_type === 'products' ? 'تفاصيل المنتجات الأكثر مبيعاً' : 'تفاصيل المبيعات حسب الفئة'; ?>
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                <th><?php echo $report_type === 'products' ? 'المنتج' : 'الفئة'; ?></th>
                                <th><?php echo $report_type === 'products' ? 'الكمية المباعة' : 'عدد الطلبات'; ?></th>
                                <th>إجمالي الإيرادات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chart_data as $index => $data): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($data['name']); ?></td>
                                <td>
                                    <?php if ($report_type === 'products'): ?>
                                        <?php echo number_format($data['total_quantity']); ?>
                                    <?php else: ?>
                                        <?php echo number_format($data['order_count']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($data['total_revenue'], 2); ?> $</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('reportChart').getContext('2d');
        var chartType = '<?php echo $report_type === 'categories' ? 'pie' : 'bar'; ?>';
        var chartTitle = '<?php echo $report_type === 'sales' ? 'تقرير المبيعات' : ($report_type === 'products' ? 'المنتجات الأكثر مبيعاً' : 'المبيعات حسب الفئة'); ?>';
        var chartLabels = <?php echo $chart_labels_json ?? '[]'; ?>;
        var chartValues = <?php echo $chart_values_json ?? '[]'; ?>;
        
        var backgroundColor = [
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(199, 199, 199, 0.6)',
            'rgba(83, 102, 255, 0.6)',
            'rgba(40, 159, 64, 0.6)',
            'rgba(210, 99, 132, 0.6)'
        ];
        
        var borderColor = [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(199, 199, 199, 1)',
            'rgba(83, 102, 255, 1)',
            'rgba(40, 159, 64, 1)',
            'rgba(210, 99, 132, 1)'
        ];
        
        var config = {
            type: chartType,
            data: {
                labels: chartLabels,
                datasets: [{
                    label: chartTitle,
                    data: chartValues,
                    backgroundColor: chartType === 'pie' ? backgroundColor : 'rgba(54, 162, 235, 0.6)',
                    borderColor: chartType === 'pie' ? borderColor : 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: chartType === 'pie',
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== undefined) {
                                    if (chartType === 'pie') {
                                        label += context.parsed.toFixed(2) + ' $';
                                    } else {
                                        label += context.parsed.y.toFixed(2) + ' $';
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: chartType !== 'pie' ? {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' $';
                            }
                        }
                    }
                } : {}
            }
        };
        
        new Chart(ctx, config);
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
