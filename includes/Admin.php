<?php

class Admin
{
    private $db;
    private $auth;

    public function __construct($db)
    {
        $this->db = $db;
        $this->auth = new Auth($db);
    }

    /**
     * Check if a user has a specific permission
     */
    public function hasPermission($userId, $permission)
    {
        $sql = "SELECT COUNT(*) as count
                FROM admin_user_roles ur
                JOIN admin_role_permissions rp ON ur.role_id = rp.role_id
                JOIN admin_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ? AND p.name = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $permission]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions($userId)
    {
        $sql = "SELECT DISTINCT p.name, p.description
                FROM admin_user_roles ur
                JOIN admin_role_permissions rp ON ur.role_id = rp.role_id
                JOIN admin_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Assign a role to a user
     */
    public function assignRole($userId, $roleId)
    {
        try {
            $sql = "INSERT INTO admin_user_roles (user_id, role_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $roleId]);
        } catch (PDOException $e) {
            // Handle duplicate entry
            return false;
        }
    }

    /**
     * Remove a role from a user
     */
    public function removeRole($userId, $roleId)
    {
        $sql = "DELETE FROM admin_user_roles WHERE user_id = ? AND role_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $roleId]);
    }

    /**
     * Get all available roles
     */
    public function getAllRoles()
    {
        $sql = "SELECT * FROM admin_roles ORDER BY name";
        $result = $this->db->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Get all available permissions
     */
    public function getAllPermissions()
    {
        $sql = "SELECT * FROM admin_permissions ORDER BY name";
        $result = $this->db->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Get permissions for a specific role
     */
    public function getRolePermissions($roleId)
    {
        $sql = "SELECT p.*
                FROM admin_role_permissions rp
                JOIN admin_permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Create a new role
     */
    public function createRole($name, $description, $permissions = [])
    {
        try {
            $this->db->autocommit(false);

            // Create role
            $sql = "INSERT INTO admin_roles (name, description) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ss', $name, $description);
            $stmt->execute();
            $roleId = $this->db->insert_id;

            // Assign permissions
            if (!empty($permissions)) {
                $sql = "INSERT INTO admin_role_permissions (role_id, permission_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                foreach ($permissions as $permissionId) {
                    $stmt->bind_param('ii', $roleId, $permissionId);
                    $stmt->execute();
                }
            }

            $this->db->commit();
            $this->db->autocommit(true);
            return $roleId;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->db->autocommit(true);
            throw $e;
        }
    }

    /**
     * Update a role
     */
    public function updateRole($roleId, $name, $description, $permissions = [])
    {
        try {
            $this->db->autocommit(false);

            // Update role
            $sql = "UPDATE admin_roles SET name = ?, description = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssi', $name, $description, $roleId);
            $stmt->execute();

            // Update permissions
            $sql = "DELETE FROM admin_role_permissions WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $roleId);
            $stmt->execute();

            if (!empty($permissions)) {
                $sql = "INSERT INTO admin_role_permissions (role_id, permission_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                foreach ($permissions as $permissionId) {
                    $stmt->bind_param('ii', $roleId, $permissionId);
                    $stmt->execute();
                }
            }

            $this->db->commit();
            $this->db->autocommit(true);
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->db->autocommit(true);
            throw $e;
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole($roleId)
    {
        try {
            $this->db->autocommit(false);

            // Delete role permissions
            $sql = "DELETE FROM admin_role_permissions WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $roleId);
            $stmt->execute();

            // Delete user roles
            $sql = "DELETE FROM admin_user_roles WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $roleId);
            $stmt->execute();

            // Delete role
            $sql = "DELETE FROM admin_roles WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $roleId);
            $stmt->execute();

            $this->db->commit();
            $this->db->autocommit(true);
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->db->autocommit(true);
            throw $e;
        }
    }

    /**
     * Get all admin users
     */
    public function getAdminUsers()
    {
        $sql = "SELECT u.*, GROUP_CONCAT(r.name) as roles
                FROM users u
                LEFT JOIN admin_user_roles ur ON u.id = ur.user_id
                LEFT JOIN admin_roles r ON ur.role_id = r.id
                WHERE u.role = 'admin'
                GROUP BY u.id
                ORDER BY u.username";

        $result = $this->db->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Assign role to user
     */
    public function assignUserRole($userId, $roleId, $assignedBy = null)
    {
        $sql = "INSERT IGNORE INTO admin_user_roles (user_id, role_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $roleId);
        return $stmt->execute();
    }

    /**
     * Remove role from user
     */
    public function removeUserRole($userId, $roleId)
    {
        $sql = "DELETE FROM admin_user_roles WHERE user_id = ? AND role_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $roleId);
        return $stmt->execute();
    }

    /**
     * Get user roles
     */
    public function getUserRoles($userId)
    {
        $sql = "SELECT r.* FROM admin_roles r 
                JOIN admin_user_roles ur ON r.id = ur.role_id 
                WHERE ur.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Check if user has permission
     */
    public function userHasPermission($userId, $permission)
    {
        $sql = "SELECT COUNT(*) as count FROM admin_permissions p
                JOIN admin_role_permissions rp ON p.id = rp.permission_id
                JOIN admin_user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ? AND p.name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $userId, $permission);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        }
        return false;
    }
}