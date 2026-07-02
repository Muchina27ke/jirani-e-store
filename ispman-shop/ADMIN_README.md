# AfriGear.tech Admin Panel

## Login Credentials

**URL:** `http://localhost/ecommerce/jirani-e-store/ispman-shop/admin/login.php`

**Default Admin:**
- Email: `admin@afgrigear.tech`
- Password: `password`

## Features

### Dashboard (`admin/index.php`)
- 4 stat cards: Total Orders, Revenue Today, Total Products, Total Customers
- Recent orders table (last 10)
- Low stock alert (products with stock < 5)

### Products (`admin/products.php`)
- Full CRUD: Add, Edit, Delete products
- Image upload to `assets/images/products/`
- Search by name, brand, SKU
- Filter by category
- Pagination (20 per page)
- Stock level indicators (red < 5, green ≥ 5)
- Featured product toggle

### Orders (`admin/orders.php`)
- List all orders with pagination (25 per page)
- Search by customer name, phone, order ID, M-Pesa receipt
- Filter by status
- Status update dropdown (auto-saves via AJAX)
- Order detail view: customer info, delivery address, payment info, items ordered
- Statuses: pending, paid, processing, shipped, delivered, cancelled

### Customers (`admin/customers.php`)
- Not yet implemented (stub file exists)

### Categories (`admin/categories.php`)
- Not yet implemented (stub file exists)

## File Structure

```
admin/
├── auth_guard.php          # Session check (include at top of every admin page)
├── layout.php              # Shared header + sidebar
├── layout_end.php          # Shared footer + scripts
├── login.php               # Admin login form
├── logout.php              # Session destroy
├── index.php               # Dashboard
├── products.php            # Products management
├── orders.php              # Orders management
├── customers.php           # (stub)
├── categories.php          # (stub)
└── api/
    ├── product_actions.php # Add/Edit/Delete products
    └── order_actions.php   # Update order status
```

## Security

- All admin pages protected by `auth_guard.php`
- Session-based authentication
- Role check: `$_SESSION['admin_role'] === 'admin'`
- Image uploads validated (type, size)
- SQL injection protected (PDO prepared statements)

## Adding More Admins

```sql
INSERT INTO users (name, email, phone, password, role)
VALUES ('New Admin', 'newadmin@afgrigear.tech', '+254700000001', '$2y$12$...', 'admin');
```

Generate password hash in PHP:
```php
echo password_hash('your_password', PASSWORD_DEFAULT);
```

## Customization

**Colors:** Edit `assets/css/admin.css` — uses same navy/orange scheme as frontend

**Sidebar:** Edit `admin/layout.php` to add/remove nav links

**Stats:** Edit `admin/index.php` SQL queries to change dashboard metrics
