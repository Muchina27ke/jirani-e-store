<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'Admins';
$currentPage = 'admins';
ob_start();
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Existing admins content here -->
        <?php
        // Check if user has permission to manage users
        if (!$admin->hasPermission($_SESSION['user']['id'], 'manage_users')) {
            $_SESSION['error'] = 'You do not have permission to access this page';
            header('Location: /admin/index.php');
            exit;
        }

        // Initialize Admin service
        $adminService = new Admin($db);

        // Handle admin user actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);

            try {
                if ($action === 'assign_role' && $userId && $roleId) {
                    $adminService->assignRole($userId, $roleId);
                    $_SESSION['success'] = 'Role assigned successfully';
                } elseif ($action === 'remove_role' && $userId && $roleId) {
                    $adminService->removeRole($userId, $roleId);
                    $_SESSION['success'] = 'Role removed successfully';
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Failed to ' . $action . ': ' . $e->getMessage();
            }

            header('Location: /admin/admins.php');
            exit;
        }

        // Get all admin users and roles
        $adminUsers = $adminService->getAdminUsers();
        $roles = $adminService->getAllRoles();

        ?>
        <!-- Admin Users List -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Admin Users</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Roles</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adminUsers as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php
                                                $userRoles = $adminService->getUserRoles($user['id']);
                                                $roleNames = array_map(function ($r) {
                                                    return $r['name'];
                                                }, $userRoles);
                                                echo implode(', ', $roleNames);
                                                ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal"
                                                    data-target="#assignRoleModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-user-tag"></i> Manage Roles
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
        </div>

        <!-- Assign Role Modals -->
        <?php foreach ($adminUsers as $user): ?>
            <div class="modal fade" id="assignRoleModal<?php echo $user['id']; ?>" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Manage Roles for <?php echo htmlspecialchars($user['name']); ?></h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $userRoles = $adminService->getUserRoles($user['id']);
                                        $userRoleIds = array_map(function ($r) {
                                            return $r['id'];
                                        }, $userRoles);
                                        ?>
                                        <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($role['name']); ?></td>
                                                <td>
                                                    <?php if (in_array($role['id'], $userRoleIds)): ?>
                                                        <span class="badge badge-success">Assigned</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Not Assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (in_array($role['id'], $userRoleIds)): ?>
                                                        <form method="POST" action="/admin/admins.php" class="d-inline">
                                                            <input type="hidden" name="action" value="remove_role">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i> Remove
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" action="/admin/admins.php" class="d-inline">
                                                            <input type="hidden" name="action" value="assign_role">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="fas fa-plus"></i> Assign
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>