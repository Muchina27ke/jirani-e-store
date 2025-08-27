<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/User.php';

if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: ../auth.php?action=login');
  exit;
}

$pageTitle = 'Manage Users';
$currentPage = 'manage_users';

ob_start();
?>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">All Users</h3>
            <div class="card-tools d-flex align-items-center">
  <div class="input-group input-group-sm mr-2" style="width: 150px;">
    <input type="text" id="search-users" class="form-control float-right" placeholder="Search">
    <div class="input-group-append">
      <button type="submit" class="btn btn-default">
        <i class="fas fa-search"></i>
      </button>
    </div>
  </div>
  <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addUserModal"><i class="fas fa-user-plus"></i> Add User</button>
</div>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Verified</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="users-table">
                <?php
                $users = [];
                $stmt = $conn->prepare("SELECT id, name, email, phone, role, verified, created_at FROM users ORDER BY created_at DESC");
                $stmt->execute();
                $result = $stmt->get_result();
                while ($user = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><span
                        class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : ($user['role'] === 'vendor' ? 'info' : 'secondary'); ?>"><?php echo ucfirst($user['role']); ?></span>
                    </td>
                    <td>
                      <span
                        class="badge badge-<?php echo $user['verified'] ? 'success' : 'warning'; ?>"><?php echo $user['verified'] ? 'Yes' : 'No'; ?></span>
                    </td>
                    <td><?php echo date('M j, Y H:i', strtotime($user['created_at'])); ?></td>
                    <td>
                      <button class="btn btn-sm btn-info" onclick="editUser(<?php echo $user['id']; ?>)"><i
                          class="fas fa-edit"></i></button>
                      <!-- Add more actions as needed -->
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add User</h4>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
          <div class="form-group">
            <label for="add-user-name">Name</label>
            <input type="text" class="form-control" id="add-user-name" required>
          </div>
          <div class="form-group">
            <label for="add-user-email">Email</label>
            <input type="email" class="form-control" id="add-user-email" required>
          </div>
          <div class="form-group">
            <label for="add-user-phone">Phone</label>
            <input type="text" class="form-control" id="add-user-phone" required>
          </div>
          <div class="form-group">
            <label for="add-user-role">Role</label>
            <select class="form-control" id="add-user-role">
              <option value="customer">Customer</option>
              <option value="vendor">Vendor</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label for="add-user-password">Password</label>
            <input type="password" class="form-control" id="add-user-password" required>
          </div>
          <div class="form-group">
            <label for="add-user-verified">Verified</label>
            <select class="form-control" id="add-user-verified">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <button type="submit" class="btn btn-success">Add User</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add User</h4>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
          <div class="form-group">
            <label for="add-user-name">Name</label>
            <input type="text" class="form-control" id="add-user-name" required>
          </div>
          <div class="form-group">
            <label for="add-user-email">Email</label>
            <input type="email" class="form-control" id="add-user-email" required>
          </div>
          <div class="form-group">
            <label for="add-user-phone">Phone</label>
            <input type="text" class="form-control" id="add-user-phone" required>
          </div>
          <div class="form-group">
            <label for="add-user-role">Role</label>
            <select class="form-control" id="add-user-role">
              <option value="customer">Customer</option>
              <option value="vendor">Vendor</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label for="add-user-password">Password</label>
            <input type="password" class="form-control" id="add-user-password" required>
          </div>
          <div class="form-group">
            <label for="add-user-verified">Verified</label>
            <select class="form-control" id="add-user-verified">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <button type="submit" class="btn btn-success">Add User</button>
        </form>
      </div>
    </div>
  </div>
</div>

  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Edit User</h4>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editUserForm">
          <input type="hidden" id="edit-user-id">
          <div class="form-group">
            <label for="edit-user-name">Name</label>
            <input type="text" class="form-control" id="edit-user-name" required>
          </div>
          <div class="form-group">
            <label for="edit-user-email">Email</label>
            <input type="email" class="form-control" id="edit-user-email" required>
          </div>
          <div class="form-group">
            <label for="edit-user-phone">Phone</label>
            <input type="text" class="form-control" id="edit-user-phone" required>
          </div>
          <div class="form-group">
            <label for="edit-user-role">Role</label>
            <select class="form-control" id="edit-user-role">
              <option value="customer">Customer</option>
              <option value="vendor">Vendor</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label for="edit-user-verified">Verified</label>
            <select class="form-control" id="edit-user-verified">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
<script>
  // Store user data in a JS object for quick access
  const usersData = {};
  <?php
  $stmt = $conn->prepare("SELECT id, name, email, phone, role, verified, created_at FROM users ORDER BY created_at DESC");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($user = $result->fetch_assoc()) {
    echo "usersData[{$user['id']}] = " . json_encode($user) . ";\n";
  }
  ?>

  function editUser(userId) {
    const user = usersData[userId];
    if (!user) return;
    $('#edit-user-id').val(user.id);
    $('#edit-user-name').val(user.name);
    $('#edit-user-email').val(user.email);
    $('#edit-user-phone').val(user.phone);
    $('#edit-user-role').val(user.role);
    $('#edit-user-verified').val(user.verified ? '1' : '0');
    $('#editUserModal').modal('show');
  }

  $('#editUserForm').on('submit', function (e) {
    e.preventDefault();
    const userId = $('#edit-user-id').val();
    const data = {
      id: userId,
      name: $('#edit-user-name').val(),
      email: $('#edit-user-email').val(),
      phone: $('#edit-user-phone').val(),
      role: $('#edit-user-role').val(),
      verified: $('#edit-user-verified').val()
    };
    $.ajax({
      url: 'api/user_actions.php?action=update',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function (response) {
        if (response.success) {
          // Update usersData and table row
          usersData[userId] = { ...usersData[userId], ...data };
          const row = $("#users-table tr").filter(function () { return $(this).find('td:first').text() == userId; });
          row.find('td').eq(1).text(data.name);
          row.find('td').eq(2).text(data.email);
          row.find('td').eq(3).text(data.phone);
          row.find('td').eq(4).html(`<span class="badge badge-${data.role === 'admin' ? 'primary' : (data.role === 'vendor' ? 'info' : 'secondary')}">${data.role.charAt(0).toUpperCase() + data.role.slice(1)}</span>`);
          row.find('td').eq(5).html(`<span class="badge badge-${data.verified == '1' ? 'success' : 'warning'}">${data.verified == '1' ? 'Yes' : 'No'}</span>`);
          $('#editUserModal').modal('hide');
          Swal.fire('Success', 'User updated successfully', 'success');
        } else {
          Swal.fire('Error', response.message, 'error');
        }
      },
      error: function () {
        Swal.fire('Error', 'Failed to update user', 'error');
      }
    });
  });
  // Add User logic
  // Show/hide admin roles multi-select based on role
$('#add-user-role').on('change', function() {
  if ($(this).val() === 'admin') {
    $('#admin-roles-group').show();
    // Fetch admin roles if not already loaded
    if ($('#add-admin-roles').children().length === 0) {
      $.ajax({
        url: 'api/user_actions.php?action=get_admin_roles',
        method: 'GET',
        success: function(response) {
          if (response.success && response.roles) {
            $('#add-admin-roles').empty();
            response.roles.forEach(function(role) {
              $('#add-admin-roles').append('<option value="'+role.id+'">'+role.name+'</option>');
            });
          }
        }
      });
    }
  } else {
    $('#admin-roles-group').hide();
  }
});

$('#addUserForm').on('submit', function (e) {
    e.preventDefault();
    const data = {
  name: $('#add-user-name').val(),
  email: $('#add-user-email').val(),
  phone: $('#add-user-phone').val(),
  role: $('#add-user-role').val(),
  password: $('#add-user-password').val(),
  verified: $('#add-user-verified').val(),
  admin_roles: $('#add-user-role').val() === 'admin' ? $('#add-admin-roles').val() : []
};
    $.ajax({
      url: 'api/user_actions.php?action=add',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function (response) {
        if (response.success) {
          $('#addUserModal').modal('hide');
          Swal.fire('Success', 'User added successfully', 'success');
          // Clear form fields
          $('#addUserForm')[0].reset();
          $('#admin-roles-group').hide();
          setTimeout(() => location.reload(), 1000);
        } else {
          Swal.fire('Error', response.message, 'error');
        }
      },
      error: function () {
        Swal.fire('Error', 'Failed to add user', 'error');
      }
    });
  });
</script>