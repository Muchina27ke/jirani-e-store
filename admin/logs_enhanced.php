<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$pageTitle = 'Enhanced System Logs';
$currentPage = 'logs';
ob_start();

$db = getDbConnection();

// Get parameters
$tab = $_GET['tab'] ?? 'logs';
$page = max(1, intval($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$limit = 25;
$offset = ($page - 1) * $limit;

// Define log tables
$logTables = [
    'logs' => 'logs',
    'order' => 'order_logs', 
    'product' => 'product_logs',
    'system' => 'system_logs',
    'inventory' => 'inventory_logs',
    'verification' => 'vendor_verification_logs'
];

$currentTable = $logTables[$tab] ?? 'logs';

// Build query with filters
$whereConditions = [];
$params = [];
$paramTypes = '';

if (!empty($search)) {
    $whereConditions[] = "(action LIKE ? OR details LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $paramTypes .= 'ss';
}

if (!empty($dateFrom)) {
    $whereConditions[] = "created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
    $paramTypes .= 's';
}

if (!empty($dateTo)) {
    $whereConditions[] = "created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
    $paramTypes .= 's';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM $currentTable $whereClause";
$countStmt = $db->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get paginated results
$query = "SELECT * FROM $currentTable $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$paramTypes .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper function to get user name
function getUserName($db, $userId) {
    if (empty($userId)) return 'System';
    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['name'] ?? "User #$userId";
}

// Helper function to format details
function formatDetails($details, $maxLength = 100) {
    if (empty($details)) return 'N/A';
    $details = htmlspecialchars($details);
    if (strlen($details) > $maxLength) {
        return '<span title="' . $details . '">' . substr($details, 0, $maxLength) . '...</span>';
    }
    return $details;
}
?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-alt mr-2"></i>
                            Enhanced System Logs
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'logs' ? 'active' : ''; ?>" 
                                   href="?tab=logs">System Logs</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'order' ? 'active' : ''; ?>" 
                                   href="?tab=order">Order Logs</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'product' ? 'active' : ''; ?>" 
                                   href="?tab=product">Product Logs</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'system' ? 'active' : ''; ?>" 
                                   href="?tab=system">App Events</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'inventory' ? 'active' : ''; ?>" 
                                   href="?tab=inventory">Inventory</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'verification' ? 'active' : ''; ?>" 
                                   href="?tab=verification">Verification</a>
                            </li>
                        </ul>

                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search logs...">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control" name="date_from" 
                                           value="<?php echo htmlspecialchars($dateFrom); ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control" name="date_to" 
                                           value="<?php echo htmlspecialchars($dateTo); ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Results Summary -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>Total:</strong> <?php echo number_format($totalRecords); ?> records
                                <?php if (!empty($search) || !empty($dateFrom) || !empty($dateTo)): ?>
                                    <span class="badge badge-info ml-2">Filtered</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <small class="text-muted">
                                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                                </small>
                            </div>
                        </div>

                        <!-- Log Table -->
                        <?php if (!empty($logs)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>User</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars($log['action'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDetails($log['details'] ?? ''); ?></td>
                                        <td><?php echo getUserName($db, $log['user_id'] ?? $log['admin_id'] ?? null); ?></td>
                                        <td>
                                            <small><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No logs found matching your criteria.
                        </div>
                        <?php endif; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?tab=<?php echo $tab; ?>&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                        Previous
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                for ($i = $start; $i <= $end; $i++): 
                                ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?tab=<?php echo $tab; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?tab=<?php echo $tab; ?>&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                        Next
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
