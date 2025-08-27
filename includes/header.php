<?php
// Get cart count if user is logged in
$cartCount = 0;
$unreadCount = 0;
$notifications = [];

if (isset($_SESSION['user_id'])) {
    require_once 'Cart.php';
    $cart = new Cart($conn, $_SESSION['user_id']);
    $cartCount = $cart->getCartCount();

    // Initialize notifications if Notification class exists
    if (class_exists('Notification')) {
        try {
            $notification = new Notification();
            $notifications = $notification->getNotifications($_SESSION['user_id'], 5); // Get last 5 notifications
            $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
        } catch (Exception $e) {
            // If notification system fails, use empty defaults
            $notifications = [];
            $unreadCount = 0;
        }
    }
}

// Helper function for time ago
if (!function_exists('timeAgo')) {
    function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);
        $time = ($time < 1) ? 1 : $time;
        $tokens = array(
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );
        foreach ($tokens as $unit => $text) {
            if ($time < $unit)
                continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Jirani' : 'Jirani - Local Marketplace'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">Jirani</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/jirani/fruits.php">Fruits</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/jirani/vegetables.php">Vegetables</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/jirani/handcrafts.php">Handcrafts</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="badge bg-primary cart-count"><?php echo $cartCount; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="wishlist.php">
                                <i class="fas fa-heart"></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">Orders</a></li>
                                <?php if (isset($_SESSION['is_vendor']) && $_SESSION['is_vendor']): ?>
                                    <li><a class="dropdown-item" href="seller/dashboard.php">Seller Dashboard</a></li>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/jirani/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/jirani/Register/index.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Notification Bell -->
    <div class="dropdown">
        <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $unreadCount; ?>
                </span>
            <?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
            <div class="notification-header">
                <h6 class="mb-0">Notifications</h6>
                <?php if ($unreadCount > 0): ?>
                    <button class="btn btn-link btn-sm mark-all-read">Mark all as read</button>
                <?php endif; ?>
            </div>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['read_at'] ? '' : 'unread'; ?>"
                        data-id="<?php echo $notification['id']; ?>">
                        <div class="notification-icon">
                            <?php
                            $icon = 'fa-info-circle';
                            switch ($notification['type']) {
                                case 'payment':
                                    $icon = 'fa-money-bill';
                                    break;
                                case 'order':
                                    $icon = 'fa-shopping-cart';
                                    break;
                                case 'verification':
                                    $icon = 'fa-check-circle';
                                    break;
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small class="text-muted"><?php echo timeAgo($notification['created_at']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($notifications)): ?>
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No notifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .notification-dropdown {
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .notification-list {
            padding: 0;
        }

        .notification-item {
            display: flex;
            padding: 10px 15px;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #e8f4ff;
        }

        .notification-icon {
            margin-right: 10px;
            color: #6c757d;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content p {
            margin-bottom: 2px;
        }

        .notification-content small {
            font-size: 0.8em;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mark notification as read
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function () {
                    const id = this.dataset.id;
                    fetch('/api/notifications/mark-read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.classList.remove('unread');
                                updateUnreadCount();
                            }
                        });
                });
            });

            // Mark all as read
            const markAllReadBtn = document.querySelector('.mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    fetch('/api/notifications/mark-all-read.php', {
                        method: 'POST'
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.querySelectorAll('.notification-item').forEach(item => {
                                    item.classList.remove('unread');
                                });
                                updateUnreadCount();
                            }
                        });
                });
            }

            // Update unread count
            function updateUnreadCount() {
                const badge = document.querySelector('.badge');
                if (badge) {
                    const count = document.querySelectorAll('.notification-item.unread').length;
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            // Poll for new notifications
            setInterval(() => {
                fetch('/api/notifications/count.php')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.badge');
                        if (data.count > 0) {
                            if (badge) {
                                badge.textContent = data.count;
                                badge.style.display = 'block';
                            } else {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                                newBadge.textContent = data.count;
                                document.querySelector('.btn-link').appendChild(newBadge);
                            }
                        }
                    });
            }, 30000); // Check every 30 seconds
        });
    </script>
</body>

</html>