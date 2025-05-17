<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Initialize variables
$order = [];
$order_items = [];
$error = '';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get site settings
    $site_settings = [];
    $stmt = $conn->query("SELECT * FROM site_settings WHERE setting_key IN ('site_name', 'address', 'contact_email', 'contact_phone')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $site_settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Get order
    $stmt = $conn->prepare(
        "SELECT o.*, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.user_id
         WHERE o.order_id = :order_id"
    );
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
    // Parse shipping address from JSON
    $shipping_data = [];
    if (!empty($order['shipping_address'])) {
        $shipping_data = json_decode($order['shipping_address'], true) ?: [];
    }
    
    // Set up default values for missing fields
    $order['subtotal_price'] = isset($order['subtotal']) ? $order['subtotal'] : 0;
    $order['shipping_price'] = isset($order['shipping_cost']) ? $order['shipping_cost'] : 0;
    $order['tax_price'] = isset($order['tax_amount']) ? $order['tax_amount'] : 0;
    $order['currency'] = 'SAR'; // Default currency
    
    // Extract shipping details
    $order['shipping_address_line'] = isset($shipping_data['address']) ? $shipping_data['address'] : '';
    $order['shipping_city'] = isset($shipping_data['city']) ? $shipping_data['city'] : '';
    $order['shipping_state'] = isset($shipping_data['state']) ? $shipping_data['state'] : '';
    $order['shipping_postal_code'] = isset($shipping_data['postal_code']) ? $shipping_data['postal_code'] : '';
    $order['shipping_country'] = isset($shipping_data['country']) ? $shipping_data['country'] : 'Saudi Arabia';
    
    // If we don't have first/last name from users table, check shipping data
    if (empty($order['first_name']) && isset($shipping_data['full_name'])) {
        $name_parts = explode(' ', $shipping_data['full_name'], 2);
        $order['first_name'] = $name_parts[0];
        $order['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
    }
    
    // If email is missing, check shipping data
    if (empty($order['email']) && isset($shipping_data['email'])) {
        $order['email'] = $shipping_data['email'];
    }
    
    // If phone is missing, check shipping data
    if (empty($order['phone']) && isset($shipping_data['phone'])) {
        $order['phone'] = $shipping_data['phone'];
    }
    
    // Get order items
    $stmt = $conn->prepare(
        "SELECT oi.*, p.name as product_name 
         FROM order_items oi 
         LEFT JOIN products p ON oi.product_id = p.product_id
         WHERE oi.order_id = :order_id"
    );
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Date format for invoice - with safe check for created_at
$invoice_date = !empty($order['created_at']) ? date('Y-m-d', strtotime($order['created_at'])) : date('Y-m-d');
$invoice_number = 'INV-' . (!empty($order['order_number']) ? $order['order_number'] : $order_id);

// Store name fallback
$store_name = isset($site_settings['site_name']) ? $site_settings['site_name'] : 'متجر Tienda';
$store_address = isset($site_settings['address']) ? $site_settings['address'] : 'العنوان، المدينة، البلد';
$store_email = isset($site_settings['contact_email']) ? $site_settings['contact_email'] : 'info@tienda.com';
$store_phone = isset($site_settings['contact_phone']) ? $site_settings['contact_phone'] : '123456789';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .invoice-title {
            font-size: 28px;
            color: #333;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .invoice-details {
            margin-bottom: 20px;
        }
        
        .invoice-details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .invoice-details-label {
            font-weight: 500;
            color: #555;
        }
        
        .company-details {
            text-align: left;
        }
        
        .customer-details {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #444;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table-totals {
            margin-top: 20px;
            width: 100%;
            max-width: 400px;
            margin-right: auto;
        }
        
        .table-totals td {
            padding: 8px 0;
        }
        
        .table-totals .label {
            font-weight: 500;
        }
        
        .table-totals .grand-total {
            font-weight: 700;
            font-size: 18px;
            border-top: 2px solid #ddd;
            padding-top: 12px;
        }
        
        .thank-you {
            margin-top: 40px;
            text-align: center;
            color: #777;
        }
        
        .print-footer {
            margin-top: 30px;
            text-align: center;
        }
        
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
            
            .print-footer {
                display: none;
            }
            
            @page {
                margin: 0.5cm;
            }
        }
    </style>
</head>
<body>
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <div class="invoice-title"><?php echo htmlspecialchars($store_name); ?></div>
                    <div><?php echo htmlspecialchars($store_address); ?></div>
                    <div><?php echo htmlspecialchars($store_email); ?></div>
                    <div><?php echo htmlspecialchars($store_phone); ?></div>
                </div>
                <div class="col-md-6 text-md-left">
                    <h1>فـاتـورة</h1>
                    <div class="invoice-details">
                        <div class="invoice-details-row">
                            <div class="invoice-details-label">رقم الفاتورة:</div>
                            <div><?php echo htmlspecialchars($invoice_number); ?></div>
                        </div>
                        <div class="invoice-details-row">
                            <div class="invoice-details-label">تاريخ الفاتورة:</div>
                            <div><?php echo htmlspecialchars($invoice_date); ?></div>
                        </div>
                        <div class="invoice-details-row">
                            <div class="invoice-details-label">حالة الدفع:</div>
                            <div>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                <span class="badge badge-success">مدفوع</span>
                                <?php else: ?>
                                <span class="badge badge-danger">غير مدفوع</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="customer-details">
            <div class="row">
                <div class="col-md-6">
                    <div class="customer-info">
                        <div><strong>الاسم:</strong> <?php echo !empty($order['first_name']) ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : (!empty($shipping_data['full_name']) ? htmlspecialchars($shipping_data['full_name']) : '-'); ?></div>
                        <div><strong>البريد الإلكتروني:</strong> <?php echo !empty($order['email']) ? htmlspecialchars($order['email']) : '-'; ?></div>
                        <div><strong>الهاتف:</strong> <?php echo !empty($order['phone']) ? htmlspecialchars($order['phone']) : 'غير متوفر'; ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-info">
                        <div class="section-title">عنوان الشحن</div>
                        <div class="shipping-info">
                            <div><?php echo !empty($order['shipping_address_line']) ? htmlspecialchars($order['shipping_address_line']) : '-'; ?></div>
                            <div>
                                <?php 
                                $location_parts = [];
                                if (!empty($order['shipping_city'])) $location_parts[] = htmlspecialchars($order['shipping_city']);
                                if (!empty($order['shipping_state'])) $location_parts[] = htmlspecialchars($order['shipping_state']);
                                if (!empty($order['shipping_postal_code'])) $location_parts[] = htmlspecialchars($order['shipping_postal_code']);
                                echo implode(', ', $location_parts);
                                ?>
                            </div>
                            <div><?php echo !empty($order['shipping_country']) ? htmlspecialchars($order['shipping_country']) : 'Saudi Arabia'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="items-table">
            <div class="section-title">العناصر المطلوبة</div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>المنتج</th>
                        <th width="100">السعر</th>
                        <th width="80">الكمية</th>
                        <th width="120">المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo number_format($item['price'], 2); ?> <?php echo $order['currency']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> <?php echo $order['currency']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <table class="table-totals ml-auto">
                <tr>
                    <td class="label">المجموع الفرعي:</td>
                    <td class="text-left"><?php echo number_format($order['subtotal_price'], 2); ?> <?php echo $order['currency']; ?></td>
                </tr>
                <tr>
                    <td class="label">الضريبة:</td>
                    <td class="text-left"><?php echo number_format($order['tax_price'], 2); ?> <?php echo $order['currency']; ?></td>
                </tr>
                <tr>
                    <td class="label">الشحن:</td>
                    <td class="text-left"><?php echo number_format($order['shipping_price'], 2); ?> <?php echo $order['currency']; ?></td>
                </tr>
                <tr class="grand-total">
                    <td class="label">الإجمالي:</td>
                    <td class="text-left"><?php echo number_format($order['total_amount'], 2); ?> <?php echo $order['currency']; ?></td>
                </tr>
            </table>
        </div>
        
        <?php if (!empty($order['admin_note'])): ?>
        <div class="mt-4">
            <div class="section-title">ملاحظات</div>
            <p><?php echo nl2br(htmlspecialchars($order['admin_note'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="thank-you">
            <p>شكراً لطلبك من متجرنا!</p>
        </div>
    </div>
    
    <div class="print-footer">
        <button class="btn btn-primary" onclick="window.print();"><i class="fas fa-print mr-1"></i> طباعة الفاتورة</button>
        <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn btn-secondary ml-2"><i class="fas fa-arrow-right ml-1"></i> العودة للطلب</a>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            // Give the browser a moment to render the page
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
