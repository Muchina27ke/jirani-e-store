<?php
// Example PHP variables
$adminUsername = "Admin";
$totalUsers = 1200;
$totalProducts = 350;
$totalOrders = 500;

$recentUsers = [
    ['user_id' => 1, 'name' => 'Gideon', 'role' => 'Admin'],
    ['user_id' => 2, 'name' => 'muchina', 'role' => 'User'],
    ['user_id' => 3, 'name' => 'mwatia', 'role' => 'User']
];

$recentOrders = [
    ['order_id' => 101, 'customer_name' => 'Gideon', 'status' => 'Shipped'],
    ['order_id' => 102, 'customer_name' => 'Muchina', 'status' => 'Processing'],
    ['order_id' => 103, 'customer_name' => 'Mwatia', 'status' => 'Delivered']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jirani Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Jirani</div>
        <div class="nav-section">
            <div class="nav-title">DASHBOARD</div>
            <a href="#" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-truck"></i>
                <span>Orders</span>
            </a>
        </div>
        <div class="nav-section">
            <div class="nav-title">SETTINGS</div>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Preferences</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-moon"></i>
                <span>Night Mode</span>
                <label class="toggle-switch">
                    <input type="checkbox" />
                    <span class="toggle-slider"></span>
                </label>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="greeting">Welcome, <?php echo $adminUsername; ?>!</h1>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search users, products, orders..." />
            </div>
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
            </button>
            <img src="https://i.pravatar.cc/100?img=5" alt="Profile" class="profile-pic" />
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard">
            <!-- Filters -->
            <div class="filters-bar">
                <button class="filter-btn">
                    <i class="fas fa-filter"></i>
                    <span>Filter</span>
                </button>
                <div class="filter-btn period-dropdown">
                    <i class="far fa-calendar-alt"></i>
                    <select>
                        <option>Current Month</option>
                        <option>Last Month</option>
                        <option>Last Quarter</option>
                    </select>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <button class="export-btn">
                    <i class="fas fa-file-export"></i>
                    <span>Export Report</span>
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <div class="value"><?php echo $totalUsers; ?></div>
                        <div class="stat-trend up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12% vs last month</span>
                        </div>
                    </div>
                    <div class="stat-icon icon-users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <div class="value"><?php echo $totalProducts; ?></div>
                        <div class="stat-trend up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8% vs last month</span>
                        </div>
                    </div>
                    <div class="stat-icon icon-products">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <div class="value"><?php echo $totalOrders; ?></div>
                        <div class="stat-trend down">
                            <i class="fas fa-arrow-down"></i>
                            <span>-3% vs last month</span>
                        </div>
                    </div>
                    <div class="stat-icon icon-orders">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="dashboard-sections">
                <!-- Recent Users -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Users</h2>
                        <a href="#" class="more-link">
                            View All <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td>
                                    <button class="info-btn">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Orders -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Orders</h2>
                        <a href="#" class="more-link">
                            View All <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo $order['customer_name']; ?></td>
                                <td><?php echo $order['status']; ?></td>
                                <td>
                                    <button class="info-btn">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>