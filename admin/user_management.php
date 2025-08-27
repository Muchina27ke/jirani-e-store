<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Admin.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$admin = new Admin($db);
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_user':
                    $username = trim($_POST['username']);
                    $email = trim($_POST['email']);
                    $password = $_POST['password'];
                    $role = $_POST['role'];
                    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
                    
                    // Create user first
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param('ssss', $username, $email, $hashedPassword, $role);
                    
                    if ($stmt->execute()) {
                        $userId = $db->insert_id;
                        
                        // Assign roles if user is admin
                        if ($role === 'admin' && !empty($roles)) {
                            foreach ($roles as $roleId) {
                                $admin->assignUserRole($userId, $roleId);
                            }
                        }
                        
                        $message = "User created successfully!";
                    } else {
                        $error = "Failed to create user.";
                    }
                    break;
                    
                case 'assign_role':
                    $userId = $_POST['user_id'];
                    $roleId = $_POST['role_id'];
                    
                    if ($admin->assignUserRole($userId, $roleId)) {
                        $message = "Role assigned successfully!";
                    } else {
                        $error = "Failed to assign role.";
                    }
                    break;
                    
                case 'remove_role':
                    $userId = $_POST['user_id'];
                    $roleId = $_POST['role_id'];
                    
                    if ($admin->removeUserRole($userId, $roleId)) {
                        $message = "Role removed successfully!";
                    } else {
                        $error = "Failed to remove role.";
                    }
                    break;
                    
                case 'create_role':
                    $name = trim($_POST['role_name']);
                    $description = trim($_POST['role_description']);
                    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
                    
                    if ($admin->createRole($name, $description, $permissions)) {
                        $message = "Role created successfully!";
                    } else {
                        $error = "Failed to create role.";
                    }
                    break;
                    
                case 'update_role':
                    $roleId = $_POST['role_id'];
                    $name = trim($_POST['role_name']);
                    $description = trim($_POST['role_description']);
                    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
                    
                    if ($admin->updateRole($roleId, $name, $description, $permissions)) {
                        $message = "Role updated successfully!";
                    } else {
                        $error = "Failed to update role.";
                    }
                    break;
                    
                case 'delete_role':
                    $roleId = $_POST['role_id'];
                    
                    if ($admin->deleteRole($roleId)) {
                        $message = "Role deleted successfully!";
                    } else {
                        $error = "Failed to delete role.";
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get data for display
$adminUsers = $admin->getAdminUsers();
$allRoles = $admin->getAllRoles();
$allPermissions = $admin->getAllPermissions();

// Get all users for role assignment
$sql = "SELECT id, username, email, role FROM users ORDER BY username";
$result = $db->query($sql);
$allUsers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User & Role Management - Jirani Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: #495057;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .table th {
            background: #f8f9fa;
        }
        .badge-role {
            font-size: 0.75em;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h5 class="text-white px-3 mb-3">Jirani Admin</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="user_management.php">
                                <i class="fas fa-users"></i> User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="roles.php">
                                <i class="fas fa-shield-alt"></i> Roles & Permissions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="fas fa-file-alt"></i> System Logs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User & Role Management</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="managementTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                            <i class="fas fa-users"></i> Users
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab">
                            <i class="fas fa-shield-alt"></i> Roles
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign" type="button" role="tab">
                            <i class="fas fa-user-plus"></i> Assign Roles
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="managementTabsContent">
                    <!-- Users Tab -->
                    <div class="tab-pane fade show active" id="users" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Admin Users</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Username</th>
                                                        <th>Email</th>
                                                        <th>Roles</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($adminUsers as $user): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                            <td>
                                                                <?php if ($user['roles']): ?>
                                                                    <?php foreach (explode(',', $user['roles']) as $role): ?>
                                                                        <span class="badge bg-primary badge-role me-1"><?php echo htmlspecialchars($role); ?></span>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No roles assigned</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary" onclick="manageUserRoles(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                                    <i class="fas fa-edit"></i> Manage Roles
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
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Create New User</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="create_user">
                                            <div class="mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" name="username" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Password</label>
                                                <input type="password" class="form-control" name="password" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">User Type</label>
                                                <select class="form-select" name="role" id="userRole" onchange="toggleRoleSelection()">
                                                    <option value="customer">Customer</option>
                                                    <option value="vendor">Vendor</option>
                                                    <option value="admin">Admin</option>
                                                </select>
                                            </div>
                                            <div class="mb-3" id="adminRoles" style="display: none;">
                                                <label class="form-label">Admin Roles</label>
                                                <?php foreach ($allRoles as $role): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" id="role<?php echo $role['id']; ?>">
                                                        <label class="form-check-label" for="role<?php echo $role['id']; ?>">
                                                            <?php echo htmlspecialchars($role['name']); ?>
                                                            <small class="text-muted d-block"><?php echo htmlspecialchars($role['description']); ?></small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Create User
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Roles Tab -->
                    <div class="tab-pane fade" id="roles" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">System Roles</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Role Name</th>
                                                        <th>Description</th>
                                                        <th>Permissions</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($allRoles as $role): ?>
                                                        <?php $rolePermissions = $admin->getRolePermissions($role['id']); ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($role['name']); ?></strong></td>
                                                            <td><?php echo htmlspecialchars($role['description']); ?></td>
                                                            <td>
                                                                <?php foreach ($rolePermissions as $perm): ?>
                                                                    <span class="badge bg-secondary badge-role me-1"><?php echo htmlspecialchars($perm['name']); ?></span>
                                                                <?php endforeach; ?>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary" onclick="editRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>', '<?php echo htmlspecialchars($role['description']); ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php if ($role['name'] !== 'Super Admin'): ?>
                                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')">
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
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Create New Role</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="create_role">
                                            <div class="mb-3">
                                                <label class="form-label">Role Name</label>
                                                <input type="text" class="form-control" name="role_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="role_description" rows="3"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Permissions</label>
                                                <div style="max-height: 200px; overflow-y: auto;">
                                                    <?php foreach ($allPermissions as $permission): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" id="perm<?php echo $permission['id']; ?>">
                                                            <label class="form-check-label" for="perm<?php echo $permission['id']; ?>">
                                                                <?php echo htmlspecialchars($permission['name']); ?>
                                                                <small class="text-muted d-block"><?php echo htmlspecialchars($permission['description']); ?></small>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Create Role
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assign Roles Tab -->
                    <div class="tab-pane fade" id="assign" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Assign Role to User</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="assign_role">
                                            <div class="mb-3">
                                                <label class="form-label">Select User</label>
                                                <select class="form-select" name="user_id" required>
                                                    <option value="">Choose user...</option>
                                                    <?php foreach ($allUsers as $user): ?>
                                                        <option value="<?php echo $user['id']; ?>">
                                                            <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Select Role</label>
                                                <select class="form-select" name="role_id" required>
                                                    <option value="">Choose role...</option>
                                                    <?php foreach ($allRoles as $role): ?>
                                                        <option value="<?php echo $role['id']; ?>">
                                                            <?php echo htmlspecialchars($role['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus"></i> Assign Role
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Remove Role from User</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="remove_role">
                                            <div class="mb-3">
                                                <label class="form-label">Select User</label>
                                                <select class="form-select" name="user_id" required>
                                                    <option value="">Choose user...</option>
                                                    <?php foreach ($allUsers as $user): ?>
                                                        <option value="<?php echo $user['id']; ?>">
                                                            <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Select Role</label>
                                                <select class="form-select" name="role_id" required>
                                                    <option value="">Choose role...</option>
                                                    <?php foreach ($allRoles as $role): ?>
                                                        <option value="<?php echo $role['id']; ?>">
                                                            <?php echo htmlspecialchars($role['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-minus"></i> Remove Role
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modals -->
    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="role_id" id="editRoleId">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" class="form-control" name="role_name" id="editRoleName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="role_description" id="editRoleDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div style="max-height: 200px; overflow-y: auto;" id="editRolePermissions">
                                <?php foreach ($allPermissions as $permission): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" id="editPerm<?php echo $permission['id']; ?>">
                                        <label class="form-check-label" for="editPerm<?php echo $permission['id']; ?>">
                                            <?php echo htmlspecialchars($permission['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Role Modal -->
    <div class="modal fade" id="deleteRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete_role">
                    <input type="hidden" name="role_id" id="deleteRoleId">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the role "<span id="deleteRoleName"></span>"?</p>
                        <p class="text-danger"><strong>Warning:</strong> This will remove this role from all users who have it assigned.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleRoleSelection() {
            const userRole = document.getElementById('userRole').value;
            const adminRoles = document.getElementById('adminRoles');
            adminRoles.style.display = userRole === 'admin' ? 'block' : 'none';
        }

        function editRole(roleId, roleName, roleDescription) {
            document.getElementById('editRoleId').value = roleId;
            document.getElementById('editRoleName').value = roleName;
            document.getElementById('editRoleDescription').value = roleDescription;
            
            // Load current permissions for this role
            fetch(`get_role_permissions.php?role_id=${roleId}`)
                .then(response => response.json())
                .then(permissions => {
                    // Uncheck all permissions first
                    document.querySelectorAll('#editRolePermissions input[type="checkbox"]').forEach(cb => {
                        cb.checked = false;
                    });
                    
                    // Check permissions for this role
                    permissions.forEach(perm => {
                        const checkbox = document.getElementById(`editPerm${perm.id}`);
                        if (checkbox) checkbox.checked = true;
                    });
                })
                .catch(error => console.error('Error loading permissions:', error));
            
            new bootstrap.Modal(document.getElementById('editRoleModal')).show();
        }

        function deleteRole(roleId, roleName) {
            document.getElementById('deleteRoleId').value = roleId;
            document.getElementById('deleteRoleName').textContent = roleName;
            new bootstrap.Modal(document.getElementById('deleteRoleModal')).show();
        }

        function manageUserRoles(userId, username) {
            // This could open a modal to manage user roles
            // For now, redirect to assign roles tab
            document.getElementById('assign-tab').click();
        }
    </script>
</body>
</html>
