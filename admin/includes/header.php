<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: ../admin/login.php');
    exit;
}

// Get admin data
$admin = $_SESSION['admin'];

// Set default current page if not set
if (!isset($current_page)) {
    $current_page = '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول | متجر Tienda</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.5.3/css/bootstrap.min.css" integrity="sha384-JvExCACAZcHNJEc7156QaHXTnQL3hQBixvj5RV5buE7vgnNEzzskDtx9NQ4p6BJe" crossorigin="anonymous">

    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    
    <!-- Custom scrollbar styling -->
    <style>
        /* Simple scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(136, 136, 136, 0.5);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
    
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.1.0/css/adminlte.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Modern Admin Style -->
    <link rel="stylesheet" href="../admin/css/admin-style.css" />
    
    <!-- CSS styles moved to external file -->
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link">الرئيسية</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="../index.php" target="_blank" class="nav-link">زيارة الموقع</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav mr-auto-navbav">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="px-2"><?php echo htmlspecialchars($admin['full_name']); ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-left">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-cog mr-2"></i> الملف الشخصي
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> تسجيل الخروج
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="index.php" class="brand-link text-center">
            <span class="brand-text font-weight-bold">متجر Tienda</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>لوحة التحكم</p>
                        </a>
                    </li>
                    
                    <!-- Products -->
                    <li class="nav-item has-treeview <?php echo (in_array($current_page, ['products', 'add_product', 'edit_product', 'categories', 'brands'])) ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo (in_array($current_page, ['products', 'add_product', 'edit_product', 'categories', 'brands'])) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-box"></i>
                            <p>
                                المنتجات
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="products.php" class="nav-link <?php echo ($current_page == 'products') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>جميع المنتجات</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_product.php" class="nav-link <?php echo ($current_page == 'add_product') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>إضافة منتج جديد</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="categories.php" class="nav-link <?php echo ($current_page == 'categories') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>الفئات</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="brands.php" class="nav-link <?php echo ($current_page == 'brands') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>العلامات التجارية</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Orders -->
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link <?php echo ($current_page == 'orders') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>الطلبات</p>
                        </a>
                    </li>
                    
                    <!-- Users -->
                    <li class="nav-item">
                        <a href="users.php" class="nav-link <?php echo ($current_page == 'users') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>المستخدمين</p>
                        </a>
                    </li>
                    
                    <!-- Messages -->
                    <li class="nav-item">
                        <a href="view_messages.php" class="nav-link <?php echo ($current_page == 'messages') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>رسائل الاتصال</p>
                        </a>
                    </li>
                    
                    <!-- Settings -->
                    <li class="nav-item has-treeview <?php echo (in_array($current_page, ['settings', 'admin_users'])) ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo (in_array($current_page, ['settings', 'admin_users'])) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>
                                الإعدادات
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="settings.php" class="nav-link <?php echo ($current_page == 'settings') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>إعدادات الموقع</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="admin_users.php" class="nav-link <?php echo ($current_page == 'admin_users') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>مستخدمي لوحة التحكم</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Logout -->
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>تسجيل الخروج</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
