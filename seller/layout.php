<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth($conn);
$auth->requireRole('vendor');

$user = $auth->getUser();
$vendor = $auth->getVendor();

if (!$vendor) {
    $vendor = [
        'business_name' => 'Vendor',
        'status' => 'pending',
        'user_id' => null
    ];
}
if (!$user) {
    $user = [
        'name' => 'Vendor User'
    ];
}

$pageTitle = $pageTitle ?? 'Vendor Dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo $pageTitle; ?> - Jirani Vendor Portal</title>

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

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
                        <i class="fas fa-external-link-alt"></i> View Store
                    </a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Verification Status -->
                <?php if ($vendor['status'] !== 'approved'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="verification.php">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <span class="badge badge-warning">Verification Required</span>
                        </a>
                    </li>
                <?php endif; ?>

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
                        <a href="notifications.php" class="dropdown-item dropdown-footer">See All Notifications</a>
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
                <span class="brand-text font-weight-light">Jirani Vendor</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($vendor['business_name']); ?>&background=28a745&color=fff"
                            class="img-circle elevation-2" alt="Business Image">
                    </div>
                    <div class="info">
                        <a href="profile.php"
                            class="d-block"><?php echo htmlspecialchars($vendor['business_name']); ?></a>
                        <small class="text-muted">
                            <i
                                class="fas fa-circle text-<?php echo $vendor['status'] === 'approved' ? 'success' : 'warning'; ?> mr-1"></i>
                            <?php echo ucfirst($vendor['status']); ?>
                        </small>
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
                            <a href="products.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'products' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-box"></i>
                                <p>
                                    Products
                                    <?php if ($vendor['status'] !== 'approved'): ?>
                                        <i class="fas fa-lock text-warning right"></i>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="orders.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'orders' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>
                                    Orders
                                    <?php
                                    $pendingOrders = 0;
                                    if (!empty($vendor['user_id'])) {
                                        $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE vendor_id = {$vendor['user_id']} AND status = 'pending'");
                                        if ($result) {
                                            $row = $result->fetch_assoc();
                                            $pendingOrders = $row['count'] ?? 0;
                                        }
                                    }
                                    if ($pendingOrders > 0): ?>
                                        <span class="badge badge-info right"><?php echo $pendingOrders; ?></span>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="payments.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'payments' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>Payments</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="verification.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'verification' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-check-circle"></i>
                                <p>
                                    Verification
                                    <?php if ($vendor['status'] === 'pending'): ?>
                                        <span class="badge badge-warning right">Pending</span>
                                    <?php elseif ($vendor['status'] === 'rejected'): ?>
                                        <span class="badge badge-danger right">Rejected</span>
                                    <?php elseif ($vendor['status'] === 'approved'): ?>
                                        <span class="badge badge-success right">Verified</span>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="delivery_zones.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'delivery_zones' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-map-marked-alt"></i>
                                <p>Delivery Zones</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>
                                    Analytics
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="analytics-sales.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Sales Report</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="analytics-products.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Product Performance</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a href="settings.php"
                                class="nav-link <?php echo ($currentPage ?? '') === 'settings' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Settings</p>
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
                            <h1 class="m-0"><?php echo $pageTitle; ?></h1>
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
                                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                                <?php endif; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Verification Alert -->
                    <?php if ($vendor['status'] !== 'approved'): ?>
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Verification Required!</h5>
                            <?php if ($vendor['status'] === 'pending'): ?>
                                Your account verification is pending. You cannot list products or receive payments until
                                verified.
                                <a href="verification.php" class="alert-link">Check verification status</a>
                            <?php elseif ($vendor['status'] === 'rejected'): ?>
                                Your account verification was rejected. Please review the feedback and resubmit your documents.
                                <a href="verification.php" class="alert-link">View details and resubmit</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

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
        <?php include 'footer_simple.php'; ?>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
    </div>

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
    <!-- Leaflet Draw JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/admin.js"></script>

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
</body>

</html>