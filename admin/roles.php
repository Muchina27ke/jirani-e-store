<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'Roles';
$currentPage = 'roles';
ob_start();
?>
<!-- Feedback Alerts -->
<?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <!-- Existing roles content here -->
    <?php
    // Initialize Admin service first
    require_once __DIR__ . '/../includes/Admin.php';
    $adminService = new Admin($conn);
    
    // Check if user has permission to manage roles (simplified check for now)
    // Note: Implement proper permission system if needed
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['error'] = 'You do not have permission to access this page';
        header('Location: index.php');
        exit;
    }

    // Handle role creation/update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $permissions = $_POST['permissions'] ?? [];

        try {
            if ($action === 'create') {
                $adminService->createRole($name, $description, $permissions);
                $_SESSION['success'] = 'Role created successfully';
            } elseif ($action === 'update' && $roleId) {
                $adminService->updateRole($roleId, $name, $description, $permissions);
                $_SESSION['success'] = 'Role updated successfully';
            } elseif ($action === 'delete' && $roleId) {
                $adminService->deleteRole($roleId);
                $_SESSION['success'] = 'Role deleted successfully';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to ' . $action . ' role: ' . $e->getMessage();
        }

        header('Location: /jirani/admin/roles.php');
        exit;
    }

    // Get all roles and permissions
    $roles = $adminService->getAllRoles();
    $permissions = $adminService->getAllPermissions();

    ?>
    <!-- Roles List -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Admin Roles</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createRoleModal">
                            <i class="fas fa-plus"></i> New Role
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Permissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($role['name']); ?></td>
                                        <td><?php echo htmlspecialchars($role['description']); ?></td>
                                        <td>
                                            <?php
                                            $rolePermissions = $adminService->getRolePermissions($role['id']);
                                            $permissionNames = array_map(function($p) {
                                                return $p['name'];
                                            }, $rolePermissions);
                                            echo implode(', ', $permissionNames);
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-toggle="modal" 
                                                    data-target="#editRoleModal<?php echo $role['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($role['name'] !== 'Super Admin'): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-toggle="modal" 
                                                        data-target="#deleteRoleModal<?php echo $role['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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

    <!-- Create Role Modal -->
    <div class="modal fade" id="createRoleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="/jirani/admin/roles.php">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Role</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Role Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Permissions</label>
                            <div class="row">
                                <?php foreach ($permissions as $permission): ?>
                                    <div class="col-md-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="permission<?php echo $permission['id']; ?>" 
                                                   name="permissions[]" 
                                                   value="<?php echo $permission['id']; ?>">
                                            <label class="custom-control-label" for="permission<?php echo $permission['id']; ?>">
                                                <?php echo htmlspecialchars($permission['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modals -->
    <?php foreach ($roles as $role): ?>
        <div class="modal fade" id="editRoleModal<?php echo $role['id']; ?>" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="/jirani/admin/roles.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Role</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Role Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($role['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($role['description']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Permissions</label>
                                <div class="row">
                                    <?php 
                                    $rolePermissions = $adminService->getRolePermissions($role['id']);
                                    $rolePermissionIds = array_map(function($p) {
                                        return $p['id'];
                                    }, $rolePermissions);
                                    ?>
                                    <?php foreach ($permissions as $permission): ?>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="editPermission<?php echo $role['id']; ?>_<?php echo $permission['id']; ?>" 
                                                       name="permissions[]" 
                                                       value="<?php echo $permission['id']; ?>"
                                                       <?php echo in_array($permission['id'], $rolePermissionIds) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="editPermission<?php echo $role['id']; ?>_<?php echo $permission['id']; ?>">
                                                    <?php echo htmlspecialchars($permission['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Role</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Role Modal -->
        <?php if ($role['name'] !== 'Super Admin'): ?>
            <div class="modal fade" id="deleteRoleModal<?php echo $role['id']; ?>" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="/jirani/admin/roles.php">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                            <div class="modal-header">
                                <h5 class="modal-title">Delete Role</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete the role "<?php echo htmlspecialchars($role['name']); ?>"?</p>
                                <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Delete Role</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php
    ?>
  </div>
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?> 