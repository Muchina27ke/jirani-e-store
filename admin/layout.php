<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$db = getDbConnection();
$auth = new Auth($db);
$auth->requireRole('admin');

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Jirani Admin</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE Theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Jirani Premium Admin Override -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --j-primary: #2A5C3D;
            --j-primary-dark: #1e4a2f;
            --j-accent: #FFA726;
            --j-bg: #F7F8F5;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--j-bg); }
        h1, h2, h3, h4, h5, h6, .brand-text { font-family: 'Outfit', sans-serif; font-weight: 700; }
        
        /* Sidebar branding */
        .sidebar-dark-primary { background-color: var(--j-primary-dark) !important; }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active,
        .sidebar-light-primary .nav-sidebar>.nav-item>.nav-link.active {
            background-color: var(--j-primary) !important;
            color: #fff !important;
        }
        .brand-link { border-bottom: 1px solid rgba(255,255,255,0.1) !important; background: var(--j-primary) !important; color: white !important; }
        
        /* Buttons & Links */
        .btn-primary { background-color: var(--j-primary); border-color: var(--j-primary); }
        .btn-primary:hover { background-color: var(--j-primary-dark); border-color: var(--j-primary-dark); }
        a { color: var(--j-primary); }
        a:hover { color: var(--j-primary-dark); }
        
        /* Cards */
        .card-primary.card-outline { border-top: 3px solid var(--j-primary); }
        .bg-info { background-color: var(--j-primary) !important; }
        .bg-success { background-color: #22c55e !important; }
        .bg-warning { background-color: var(--j-accent) !important; }
        
        .main-header { border-bottom: 1px solid rgba(0,0,0,0.05); box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .content-wrapper { background-color: var(--j-bg); }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
        .card-header { border-bottom: 1px solid rgba(0,0,0,0.05); background-color: #fff; border-radius: 12px 12px 0 0 !important; }
    </style>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="../title_logo.jpg" alt="Jirani Logo" height="60" width="60">
        </div>

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="../index.php" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Site
                    </a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Notifications Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" id="notificationDropdown">
                        <i class="far fa-bell"></i>
                        <span class="badge badge-warning navbar-badge" id="notificationCount">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">Notifications</span>
                        <div class="dropdown-divider"></div>
                        <div id="notificationList">
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-info-circle mr-2"></i> No new notifications
                            </a>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                    </div>
                </li>

                <!-- User Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-user"></i>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($user['name']); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user-cog mr-2"></i> Profile
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog mr-2"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../auth.php?action=logout" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="index.php" class="brand-link">
                <img src="../title_logo.jpg" alt="Jirani Logo" class="brand-image img-circle elevation-3"
                    style="opacity: .8">
                <span class="brand-text font-weight-light">Jirani Admin</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=007bff&color=fff"
                            class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="profile.php" class="d-block"><?php echo htmlspecialchars($user['name']); ?></a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item">
                            <a href="index.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="vendors.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'vendors' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-store"></i>
                                <p>
                                    Vendors
                                    <span class="badge badge-warning right">2</span>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="products.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'products' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-box"></i>
                                <p>Products</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="orders.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'orders' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>
                                    Orders
                                    <span class="badge badge-info right">2</span>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="payments.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'payments' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>Payments & Escrow</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="verifications.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'verifications' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-check-circle"></i>
                                <p>
                                    Verifications
                                    <span class="badge badge-danger right">2</span>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="add_product.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'add_product' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-plus"></i>
                                <p>Add Product</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="add_vendor.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'add_vendor' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-user-plus"></i>
                                <p>Add Vendor</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="manage_users.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'manage_users' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Manage Users</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="roles.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'roles' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>Roles</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="analytics-sales.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'analytics-sales' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-chart-pie"></i>
                                <p>Sales Analytics</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="settings.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'settings' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logs.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'logs' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-history"></i>
                                <p>System Logs</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="contacts.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'contacts' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-envelope"></i>
                                <p>Contact Messages</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <?php if (isset($breadcrumbs)): ?>
                                    <?php foreach ($breadcrumbs as $breadcrumb): ?>
                                        <?php if (isset($breadcrumb['url'])): ?>
                                            <li class="breadcrumb-item"><a
                                                    href="<?php echo $breadcrumb['url']; ?>"><?php echo $breadcrumb['title']; ?></a>
                                            </li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item active"><?php echo $breadcrumb['title']; ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?php echo $pageTitle ?? 'Dashboard'; ?></li>
                                <?php endif; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i>
                            <?php
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-ban"></i>
                            <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['warning'])): ?>
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-exclamation-triangle"></i>
                            <?php
                            echo $_SESSION['warning'];
                            unset($_SESSION['warning']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['info'])): ?>
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-info"></i>
                            <?php
                            echo $_SESSION['info'];
                            unset($_SESSION['info']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Page Content -->
                    <?php echo $content ?? ''; ?>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="../index.php">Jirani</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 1.0.0
            </div>
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
    </div>

    <!-- Additional JS -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <script>
            <?php echo $pageScripts; ?>
        </script>
    <?php endif; ?>

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .wrapper {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .content-wrapper {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            padding-bottom: 40px;
            /* Space for footer */
            background: #f5f7fa;
        }

        .main-footer {
            flex-shrink: 0;
            width: 100%;
            position: relative;
            z-index: 10;
        }
    </style>
</body>

</html>