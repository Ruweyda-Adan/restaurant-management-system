<?php
session_start();
require_once('../includes/db_connect.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

// Process status updates
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $update_sql = "UPDATE orders SET status = '$status' WHERE id = $order_id";
    if ($conn->query($update_sql) === TRUE) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Error updating order status: " . $conn->error;
    }
}

// View a specific order details
$view_order = null;
$order_items = null;
if (isset($_GET['view'])) {
    $order_id = intval($_GET['view']);
    
    // Get order details
    $order_sql = "SELECT o.*, DATE_FORMAT(o.order_time, '%d %b %Y %h:%i %p') as formatted_time 
                 FROM orders o 
                 WHERE o.id = $order_id";
    $order_result = $conn->query($order_sql);
    
    if ($order_result->num_rows > 0) {
        $view_order = $order_result->fetch_assoc();
        
        // Get order items
        $items_sql = "SELECT oi.*, mi.name, mi.price 
                     FROM order_items oi 
                     JOIN menu_items mi ON oi.menu_item_id = mi.id 
                     WHERE oi.order_id = $order_id";
        $items_result = $conn->query($items_sql);
        
        if ($items_result->num_rows > 0) {
            $order_items = [];
            while ($item = $items_result->fetch_assoc()) {
                $order_items[] = $item;
            }
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build WHERE clause
$where_clauses = [];
if ($status_filter) {
    $where_clauses[] = "o.status = '$status_filter'";
}
if ($date_filter) {
    $where_clauses[] = "DATE(o.order_time) = '$date_filter'";
}
if ($search) {
    $where_clauses[] = "(o.id LIKE '%$search%' OR o.customer_name LIKE '%$search%')";
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Get total orders count
$count_sql = "SELECT COUNT(*) as total FROM orders o $where_sql";
$count_result = $conn->query($count_sql);
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders
$orders_sql = "SELECT o.*, DATE_FORMAT(o.order_time, '%d %b %Y %h:%i %p') as formatted_time 
              FROM orders o 
              $where_sql 
              ORDER BY o.order_time DESC 
              LIMIT $offset, $limit";
$orders_result = $conn->query($orders_sql);

// Check for database structure
$check_columns_sql = "SHOW COLUMNS FROM orders LIKE 'total_amount'";
$total_amount_exists = ($conn->query($check_columns_sql)->num_rows > 0);

$check_payment_sql = "SHOW COLUMNS FROM orders LIKE 'payment_status'";
$payment_status_exists = ($conn->query($check_payment_sql)->num_rows > 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Restaurant Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>Restaurant Admin</h2>
            </div>
            <nav class="admin-nav">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_menu.php"><i class="fas fa-utensils"></i> Manage Menu</a>
                <a href="manage_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Manage Orders</a>
                <a href="#" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <header class="admin-header">
                <h1>Manage Orders</h1>
            </header>
            
            <?php if (isset($success_message)): ?>
                <div class="alert success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$total_amount_exists || !$payment_status_exists): ?>
                <div class="alert warning">
                    <strong>Database Update Required:</strong> Your orders table is missing some required columns. 
                    Please run the following SQL to update your database structure:
                    <code>
                        ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0.00;
                        ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) DEFAULT 'pending';
                    </code>
                </div>
            <?php endif; ?>
            
            <?php if ($view_order): ?>
                <div class="order-details">
                    <div class="order-details-header">
                        <h2>Order #<?php echo $view_order['id']; ?> Details</h2>
                        <a href="manage_orders.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                    <div class="order-info">
    <div class="info-card">
        <div class="info-label">Customer:</div>
        <div class="info-value"><?php echo htmlspecialchars($view_order['customer_name'] ?? 'N/A'); ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Email:</div>
        <div class="info-value"><?php echo htmlspecialchars($view_order['customer_email'] ?? 'N/A'); ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Phone:</div>
        <div class="info-value"><?php echo htmlspecialchars($view_order['customer_phone'] ?? 'N/A'); ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Address:</div>
        <div class="info-value"><?php echo htmlspecialchars($view_order['delivery_address'] ?? 'N/A'); ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Order Date:</div>
        <div class="info-value"><?php echo $view_order['formatted_time']; ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Status:</div>
        <div class="info-value">
            <form method="post" action="">
                <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                <select name="status" onchange="this.form.submit()">
                    <option value="pending" <?php echo ($view_order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo ($view_order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                    <option value="preparing" <?php echo ($view_order['status'] == 'preparing') ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?php echo ($view_order['status'] == 'ready') ? 'selected' : ''; ?>>Ready for Pickup/Delivery</option>
                    <option value="delivered" <?php echo ($view_order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="completed" <?php echo ($view_order['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($view_order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <input type="hidden" name="update_status" value="1">
            </form>
        </div>
    </div>
    <div class="info-card">
        <div class="info-label">Payment Method:</div>
        <div class="info-value"><?php echo ucfirst(htmlspecialchars($view_order['payment_method'] ?? 'Not specified')); ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Payment Status:</div>
        <div class="info-value"><?php echo ucfirst(htmlspecialchars($view_order['payment_status'] ?? 'Pending')); ?></div>
    </div>
</div>
                    
                    <div class="order-items">
                        <h3>Order Items</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Special Instructions</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                if ($order_items): 
                                    foreach ($order_items as $item):
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $total += $subtotal;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($item['special_instructions'] ?? 'None'); ?></td>
                                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">Subtotal:</th>
                                    <td>$<?php echo number_format($total, 2); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-right">Delivery Fee:</th>
                                    <td>$<?php echo number_format($view_order['delivery_fee'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-right">Tax:</th>
                                    <td>$<?php echo number_format($view_order['tax'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-right">Total:</th>
                                    <td>$<?php echo number_format($view_order['total_amount'] ?? $total, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if (!empty($view_order['notes'])): ?>
                    <div class="order-notes">
                        <h3>Order Notes</h3>
                        <p><?php echo htmlspecialchars($view_order['notes']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="orders-container">
                    <div class="filters-row">
                        <form method="get" action="" class="filter-form">
                            <div class="filter-group">
                                <label for="status">Status:</label>
                                <select name="status" id="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo ($status_filter == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="preparing" <?php echo ($status_filter == 'preparing') ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="ready" <?php echo ($status_filter == 'ready') ? 'selected' : ''; ?>>Ready</option>
                                    <option value="delivered" <?php echo ($status_filter == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="date">Date:</label>
                                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="search">Search:</label>
                                <input type="text" id="search" name="search" placeholder="Order # or Customer" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="filter-buttons">
                                <button type="submit" class="btn primary">Apply Filters</button>
                                <a href="manage_orders.php" class="btn secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date & Time</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders_result->num_rows > 0): ?>
                                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo $order['formatted_time']; ?></td>
                                            <td>$<?php echo number_format($order['total_amount'] ?? 0.00, 2); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="manage_orders.php?view=<?php echo $order['id']; ?>" class="btn small primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <form method="post" action="" class="inline-form" onsubmit="return confirm('Are you sure you want to update this order status?');">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <select name="status" class="status-select">
                                                            <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                                            <option value="preparing" <?php echo ($order['status'] == 'preparing') ? 'selected' : ''; ?>>Preparing</option>
                                                            <option value="ready" <?php echo ($order['status'] == 'ready') ? 'selected' : ''; ?>>Ready</option>
                                                            <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                            <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                        <button type="submit" name="update_status" value="1" class="btn small secondary">
                                                            <i class="fas fa-sync-alt"></i> Update
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="no-records">No orders found matching your criteria.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="pagination-link active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.getElementById('logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });
        
        // Make status badges more visually distinct
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alert messages after 5 seconds
            const alerts = document.querySelectorAll('.alert:not(.warning)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
<style>
    /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f7f6;
}

.admin-container {
    display: flex;
    min-height: 100vh;
}

.admin-sidebar {
    width: 250px;
    background-color: #2c3e50;
    color: #fff;
    padding: 20px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.admin-logo {
    text-align: center;
    margin-bottom: 20px;
}

.admin-logo h2 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.admin-nav {
    display: flex;
    flex-direction: column;
}

.admin-nav a {
    color: #fff;
    text-decoration: none;
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.admin-nav a:hover {
    background-color: #34495e;
}

.admin-nav a.active {
    background-color: #34495e;
}

.admin-content {
    flex-grow: 1;
    padding: 20px;
    background-color: #fff;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.admin-header h1 {
    margin: 0;
    font-size: 28px;
    color: #2c3e50;
}

.alert {
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert.success {
    background: #d4edda;
    color: #155724;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
}

.alert.warning {
    background: #fff3cd;
    color: #856404;
}

/* Order Details */
.order-details {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.order-details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.order-details-header h2 {
    margin: 0;
    font-size: 24px;
    color: #2c3e50;
}

.back-link {
    color: #3498db;
    text-decoration: none;
    font-size: 14px;
}

.back-link:hover {
    text-decoration: underline;
}

.order-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.info-card {
    background: #fff;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.info-label {
    font-size: 14px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.info-value {
    font-size: 16px;
    color: #2c3e50;
    font-weight: bold;
}

.order-items {
    margin-bottom: 20px;
}

.order-items h3 {
    margin: 0 0 15px;
    font-size: 20px;
    color: #2c3e50;
}

.order-notes {
    background: #fff;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.order-notes h3 {
    margin: 0 0 10px;
    font-size: 18px;
    color: #2c3e50;
}

.order-notes p {
    margin: 0;
    font-size: 14px;
    color: #7f8c8d;
}

/* Orders List */
.orders-container {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.filters-row {
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
    color: #2c3e50;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn.primary {
    background: #3498db;
    color: #fff;
}

.btn.primary:hover {
    background: #2980b9;
}

.btn.secondary {
    background: #7f8c8d;
    color: #fff;
}

.btn.secondary:hover {
    background: #666;
}

.btn.small {
    padding: 5px 10px;
    font-size: 12px;
}

.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.admin-table th,
.admin-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.admin-table th {
    background-color: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: bold;
    text-transform: capitalize;
}

.status-badge.pending {
    background: #f39c12;
    color: #fff;
}

.status-badge.processing {
    background: #3498db;
    color: #fff;
}

.status-badge.preparing {
    background: #9b59b6;
    color: #fff;
}

.status-badge.ready {
    background: #2ecc71;
    color: #fff;
}

.status-badge.delivered {
    background: #27ae60;
    color: #fff;
}

.status-badge.completed {
    background: #16a085;
    color: #fff;
}

.status-badge.cancelled {
    background: #e74c3c;
    color: #fff;
}

.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

.status-select {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 12px;
}

.no-records {
    text-align: center;
    color: #7f8c8d;
    padding: 20px;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.pagination-link {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    text-decoration: none;
    color: #3498db;
    transition: background 0.3s;
}

.pagination-link:hover {
    background: #f4f7f6;
}

.pagination-link.active {
    background: #3498db;
    color: #fff;
    border-color: #3498db;
}
</style>