<?php
session_start();
require_once('../includes/db_connect.php');

// Initialize variables with default values
$error_message = '';
$recent_orders = [];
$menu_items_count = 0;
$total_sales = 0.0;
$popular_items = [];
$staff_count = 0;

// Secure database queries with prepared statements
function safeQuery($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch dashboard data
// Count menu items
$menu_result = safeQuery($conn, "SELECT COUNT(*) as count FROM menu_items");
$menu_items_count = $menu_result ? $menu_result->fetch_assoc()['count'] : 0;

// Get total sales
$sales_result = safeQuery($conn, "SELECT SUM(total_price) as total FROM orders WHERE status != 'Cancelled'");
$total_sales = $sales_result ? ($sales_result->fetch_assoc()['total'] ?? 0) : 0;

// Get recent orders
$orders_result = safeQuery($conn, "SELECT * FROM orders ORDER BY order_time DESC LIMIT 5");
if ($orders_result) {
    while ($row = $orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Get popular menu items
$popular_query = "SELECT m.name, COUNT(oi.menu_item_id) as order_count 
                  FROM order_items oi 
                  JOIN menu_items m ON oi.menu_item_id = m.id 
                  JOIN orders o ON oi.order_id = o.id 
                  WHERE o.status != 'Cancelled' 
                  GROUP BY oi.menu_item_id 
                  ORDER BY order_count DESC 
                  LIMIT 5";
$popular_result = safeQuery($conn, $popular_query);
if ($popular_result) {
    while ($row = $popular_result->fetch_assoc()) {
        $popular_items[] = $row;
    }
}

// Count staff members
$staff_result = safeQuery($conn, "SELECT COUNT(*) as count FROM admin_users");
$staff_count = $staff_result ? $staff_result->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Restaurant Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #f4f7f6;
            --text-color: #333;
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Dashboard Container */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .admin-sidebar-popup {
            width: 250px;
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .admin-sidebar-popup .admin-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
        }

        .admin-nav a {
            color: var(--white);
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: var(--primary-color);
        }

        .admin-nav a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Admin Content */
        .admin-content {
            flex-grow: 1;
            padding: 20px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-header h1 {
            font-size: 28px;
            color: var(--secondary-color);
        }

        .admin-user {
            color: #666;
        }

        /* Dashboard Stats */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }

        .stat-info h3 {
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .stat-info p {
            font-size: 20px;
            font-weight: bold;
            color: var(--secondary-color);
        }

        /* Dashboard Sections */
        .dashboard-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .dashboard-section {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-section h2 {
            margin-bottom: 20px;
            color: var(--secondary-color);
        }

        /* Recent Orders Table */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .admin-table th {
            background-color: #f8f9fa;
            color: var(--secondary-color);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending { background: #f39c12; color: var(--white); }
        .status-processing { background: var(--primary-color); color: var(--white); }
        .status-completed { background: #2ecc71; color: var(--white); }
        .status-cancelled { background: #e74c3c; color: var(--white); }

        /* Popular Items */
        .popular-items {
            margin-top: 15px;
        }

        .popular-item {
            margin-bottom: 15px;
        }

        .popular-item-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .popular-item-bar {
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: var(--primary-color);
            border-radius: 4px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar-toggle-btn {
                display: block;
            }

            .admin-sidebar-popup {
                position: fixed;
                top: 0;
                left: -250px;
                height: 100%;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            .admin-sidebar-popup.active {
                left: 0;
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }

            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar Toggle Button -->
    <button id="sidebar-toggle" class="sidebar-toggle-btn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Dashboard Container -->
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar-popup" id="sidebar">
            <div class="admin-sidebar">
                <div class="admin-logo">
                    <h2>Restaurant Admin</h2>
                </div>
                <nav class="admin-nav">
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> Manage Menu
                    </a>
                    <a href="manage_staff.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_staff.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Manage Staff
                    </a>
                    <a href="view_sales_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_sales_report.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Sales Report
                    </a>
                    <a href="set_restaurant_branding.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'set_restaurant_branding.php' ? 'active' : ''; ?>">
                        <i class="fas fa-palette"></i> Restaurant Branding
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, Admin</span>
                </div>
            </header>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Menu Items</h3>
                        <p><?php echo $menu_items_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Recent Orders</h3>
                        <p><?php echo count($recent_orders); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Sales</h3>
                        <p>KSh <?php echo number_format($total_sales, 2); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Staff Members</h3>
                        <p><?php echo $staff_count; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <h2>Recent Orders</h2>
                    <?php if (count($recent_orders) > 0): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>KSh <?php echo number_format($order['total_price'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_time'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No recent orders found.</p>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-section">
                    <h2>Popular Menu Items</h2>
                    <?php if (count($popular_items) > 0): ?>
                        <div class="popular-items">
                            <?php foreach ($popular_items as $index => $item): ?>
                                <div class="popular-item">
                                    <div class="popular-item-info">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p><?php echo htmlspecialchars($item['order_count']); ?> orders</p>
                                    </div>
                                    <div class="popular-item-bar">
                                        <div class="bar-fill" style="width: <?php 
                                            echo min(100, ($item['order_count'] / $popular_items[0]['order_count']) * 100); 
                                        ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No order data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const toggleBtn = document.getElementById('sidebar-toggle');

        // Toggle sidebar
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        }

        // Event listeners
        toggleBtn.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        // Close sidebar on larger screens
        function handleResize() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            }
        }

        // Add resize listener
        window.addEventListener('resize', handleResize);
    });
    </script>
</body>
</html>