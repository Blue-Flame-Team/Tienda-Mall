<?php
/**
 * Tienda Mall E-commerce Platform
 * Admin Dashboard Main Page
 */

// Include necessary files
require_once 'includes/admin-auth.php';
require_once '../includes/bootstrap.php';
require_once '../includes/functions.php';

// Initialize variables to prevent errors
$productCount = 0;
$userCount = 0;
$orderCount = 0;
$totalSales = 0;
$recentOrders = [];
$lowStockProducts = [];

try {
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Count total products - check if table exists first
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'products'");
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
            $productCount = $stmt->fetch()['count'];
        }
    } catch (PDOException $e) {
        // Log error but continue
        error_log('Error counting products: ' . $e->getMessage());
    }
    
    // Count total customers
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'];
        }
    } catch (PDOException $e) {
        error_log('Error counting users: ' . $e->getMessage());
    }
    
    // Count total orders
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'orders'");
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM orders");
            $orderCount = $stmt->fetch()['count'];
        }
    } catch (PDOException $e) {
        error_log('Error counting orders: ' . $e->getMessage());
    }
    
    // Count total sales amount
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'orders'");
        if ($stmt->rowCount() > 0) {
            // Verificamos si existe la columna 'status'
            $stmt = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
            if ($stmt->rowCount() > 0) {
                // Obtener los valores permitidos para la columna 'status'
                $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                $type = $columnInfo['Type'];
                
                // Si es un enum, extraer los valores permitidos
                if (strpos($type, 'enum') === 0) {
                    // Vamos a considerar 'delivered' y 'shipped' como pedidos completados para el cálculo de ventas
                    $stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered' OR status = 'shipped'");
                } else {
                    // Para cualquier otro tipo de columna, usar todos los registros no cancelados
                    $stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
                }
            } else {
                // Si no existe la columna 'status', calculamos el total sin filtrar
                $stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders");
            }
            $result = $stmt->fetch();
            $totalSales = $result['total'] ?? 0;
        }
    } catch (PDOException $e) {
        error_log('Error calculating sales: ' . $e->getMessage());
        // En caso de error, establecemos el total de ventas a 0
        $totalSales = 0;
    }
    
    // Get recent orders
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'orders'");
        if ($stmt->rowCount() > 0 && $conn->query("SHOW TABLES LIKE 'users'")->rowCount() > 0) {
            // Check if order_date column exists
            $stmt = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
            $dateColumn = ($stmt->rowCount() > 0) ? 'order_date' : 'created_at';
            
            // Check if users has correct columns
            $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'first_name'");
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->query(
                    "SELECT o.*, u.first_name, u.last_name, u.email 
                     FROM orders o
                     JOIN users u ON o.user_id = u.user_id
                     ORDER BY o.$dateColumn DESC
                     LIMIT 5"
                );
            } else {
                // Users table might have different structure
                $stmt = $conn->query(
                    "SELECT o.*, u.name, u.email 
                     FROM orders o
                     JOIN users u ON o.user_id = u.user_id
                     ORDER BY o.$dateColumn DESC
                     LIMIT 5"
                );
            }
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Error getting recent orders: ' . $e->getMessage());
    }
    
    // Get low stock products
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'products'");
        if ($stmt->rowCount() > 0) {
            // Check if columns exist
            $quantityColumn = "quantity";
            $activeColumn = "is_active";
            
            $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'quantity'");
            if ($stmt->rowCount() == 0) {
                $quantityColumn = "stock";
            }
            
            $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'is_active'");
            if ($stmt->rowCount() == 0) {
                $activeColumn = "active";
                
                // If no active column found, try without that condition
                $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'active'");
                if ($stmt->rowCount() == 0) {
                    $stmt = $conn->query(
                        "SELECT * FROM products 
                         WHERE $quantityColumn <= 5
                         ORDER BY $quantityColumn ASC
                         LIMIT 5"
                    );
                } else {
                    $stmt = $conn->query(
                        "SELECT * FROM products 
                         WHERE $quantityColumn <= 5 AND $activeColumn = '1'
                         ORDER BY $quantityColumn ASC
                         LIMIT 5"
                    );
                }
            } else {
                $stmt = $conn->query(
                    "SELECT * FROM products 
                     WHERE $quantityColumn <= 5 AND $activeColumn = 'Y'
                     ORDER BY $quantityColumn ASC
                     LIMIT 5"
                );
            }
            
            $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Error getting low stock products: ' . $e->getMessage());
    }
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
}

// Include admin header
$pageTitle = "Dashboard";
include 'includes/admin-header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <!-- Products card -->
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $productCount; ?></h3>
                            <p>Total Products</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <a href="products.php" class="small-box-footer">
                            More info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <!-- Customers card -->
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $userCount; ?></h3>
                            <p>Customers</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="customers.php" class="small-box-footer">
                            More info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <!-- Orders card -->
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $orderCount; ?></h3>
                            <p>Total Orders</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <a href="orders.php" class="small-box-footer">
                            More info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <!-- Revenue card -->
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo formatMoney($totalSales); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <a href="sales-report.php" class="small-box-footer">
                            More info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Orders -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Orders</h3>
                            <div class="card-tools">
                                <a href="orders.php" class="btn btn-tool">
                                    <i class="fas fa-bars"></i> View All
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($order['status']) {
                                                    case 'Pending':
                                                        echo 'badge-secondary';
                                                        break;
                                                    case 'Processing':
                                                        echo 'badge-primary';
                                                        break;
                                                    case 'Shipped':
                                                        echo 'badge-info';
                                                        break;
                                                    case 'Completed':
                                                        echo 'badge-success';
                                                        break;
                                                    case 'Cancelled':
                                                        echo 'badge-danger';
                                                        break;
                                                    default:
                                                        echo 'badge-secondary';
                                                }
                                                ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <a href="view-order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Low Stock Products -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Low Stock Products</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="products-list product-list-in-card pl-2 pr-2">
                                <?php if (empty($lowStockProducts)): ?>
                                <li class="item">
                                    <div class="product-info" style="margin-left: 0;">
                                        <span class="text-center">No low stock products</span>
                                    </div>
                                </li>
                                <?php else: ?>
                                <?php foreach ($lowStockProducts as $product): ?>
                                <li class="item">
                                    <div class="product-img">
                                        <img src="<?php echo $product['image_url'] ?? '../assets/images/default-product.jpg'; ?>" alt="Product Image" class="img-size-50">
                                    </div>
                                    <div class="product-info">
                                        <a href="edit-product.php?id=<?php echo $product['product_id']; ?>" class="product-title">
                                            <?php echo htmlspecialchars($product['title']); ?>
                                            <span class="badge badge-warning float-right"><?php echo $product['quantity']; ?> in stock</span>
                                        </a>
                                        <span class="product-description">
                                            <?php echo formatPrice($product['price']); ?>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="products.php?filter=low_stock" class="uppercase">View All Products</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>
