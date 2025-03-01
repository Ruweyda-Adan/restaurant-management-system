<?php
session_start();
require_once('../includes/db_connect.php');


// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Check if login form submitted
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Simple authentication 
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            
            // Redirect to dashboard after successful login
            header('Location: dashboard.php');
            exit; 
        } else {
            $login_error = "Invalid username or password";
        }
    }
    
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login</title>
            <link rel="stylesheet" href="../style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        </head>
        <body>
            <div class="login-container">
                <div class="login-box">
                    <div class="login-header">
                        <h1>Admin Login</h1>
                        <p>Welcome back! Please log in to access the dashboard.</p>
                    </div>
                    <?php if (isset($login_error)): ?>
                        <div class="login-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $login_error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="" class="login-form">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username</label>
                            <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" class="login-btn">Log In</button>
                    </form>
                    <div class="login-footer">
                        <p>Forgot your password? <a href="#">Reset it here</a></p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}


// Initialize variables with default values
$orders_today = ['count' => 0, 'total' => 0];
$pending_orders = ['count' => 0];
$recent_orders_result = [];
$popular_items_result = [];

// Get stats for dashboard
try {
    // Total orders today
    $today = date('Y-m-d');
    $orders_today_query = "SELECT COUNT(*) as count, SUM(total_price) as total 
                           FROM orders 
                           WHERE DATE(order_time) = '$today'";
    $orders_today_result = $conn->query($orders_today_query);
    if ($orders_today_result) {
        $orders_today = $orders_today_result->fetch_assoc();
    }

    // Pending orders
    $pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'";
    $pending_orders_result = $conn->query($pending_orders_query);
    if ($pending_orders_result) {
        $pending_orders = $pending_orders_result->fetch_assoc();
    }

    // Recent orders
    $recent_orders_query = "SELECT o.*, DATE_FORMAT(o.order_time, '%h:%i %p') as formatted_time 
                           FROM orders o 
                           ORDER BY o.order_time DESC 
                           LIMIT 10";
    $recent_orders_result = $conn->query($recent_orders_query);

    // Popular items
    $popular_items_query = "SELECT mi.name, SUM(oi.quantity) as total_ordered 
                           FROM order_items oi 
                           JOIN menu_items mi ON oi.menu_item_id = mi.id 
                           GROUP BY oi.menu_item_id 
                           ORDER BY total_ordered DESC 
                           LIMIT 5";
    $popular_items_result = $conn->query($popular_items_query);
} catch (Exception $e) {
    // Handle database errors
    echo "Database error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Admin Dashboard</title>
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
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_menu.php"><i class="fas fa-utensils"></i> Manage Menu</a>
                <a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a>
                <a href="#" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                </div>
            </header>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Today's Orders</h3>
                        <p class="stat-value"><?php echo $orders_today['count']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Today's Revenue</h3>
                        <p class="stat-value">KSh <?php echo number_format($orders_today['total'] ?? 0, 2); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending Orders</h3>
                        <p class="stat-value"><?php echo $pending_orders['count']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <h2>Recent Orders</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Time</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_orders_result): ?>
                                <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo $order['customer_name']; ?></td>
                                        <td><?php echo $order['order_type']; ?></td>
                                        <td><?php echo $order['formatted_time']; ?></td>
                                        <td>KSh <?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="manage_orders.php?view=<?php echo $order['id']; ?>" class="action-link">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No recent orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <a href="manage_orders.php" class="view-all-link">View All Orders</a>
                </div>
                
                <div class="dashboard-section">
                    <h2>Popular Items</h2>
                    <div class="popular-items">
                        <?php if ($popular_items_result): ?>
                            <?php while ($item = $popular_items_result->fetch_assoc()): ?>
                                <div class="popular-item">
                                    <span class="item-name"><?php echo $item['name']; ?></span>
                                    <span class="item-count"><?php echo $item['total_ordered']; ?> ordered</span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No popular items found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Logout functionality
        document.getElementById('logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                fetch('?logout=1')
                    .then(() => {
                        window.location.reload();
                    });
            }
        });
        
        // Real-time updates for pending orders (poll every 30 seconds)
        setInterval(function() {
            fetch('?get_pending=1')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = data.count;
                });
        }, 30000);
    </script>
</body>
</html>

<?php
// Handle AJAX logout request
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    echo json_encode(['success' => true]);
    exit;
}

// Handle AJAX pending orders count request
if (isset($_GET['get_pending'])) {
    $pending_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'";
    $pending_result = $conn->query($pending_query);
    $pending = $pending_result->fetch_assoc();
    echo json_encode(['count' => $pending['count']]);
    exit;
}
?>
<style>
    /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f7f6;
    margin: 0;
    padding: 0;
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

.admin-user {
    font-size: 16px;
    color: #34495e;
}

.dashboard-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.stat-card {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    width: 30%;
    text-align: center;
}

.stat-icon {
    font-size: 30px;
    color: #3498db;
    margin-bottom: 10px;
}

.stat-info h3 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #34495e;
}

.dashboard-sections {
    display: flex;
    flex-direction: column;
}

.dashboard-section {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.dashboard-section h2 {
    margin: 0 0 20px;
    font-size: 22px;
    color: #2c3e50;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.admin-table th, .admin-table td {
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
    font-size: 14px;
    font-weight: bold;
}

.status-pending {
    background-color: #f39c12;
    color: #fff;
}

.status-completed {
    background-color: #2ecc71;
    color: #fff;
}

.status-cancelled {
    background-color: #e74c3c;
    color: #fff;
}

.action-link {
    color: #3498db;
    text-decoration: none;
    font-size: 18px;
}

.view-all-link {
    display: inline-block;
    margin-top: 10px;
    color: #3498db;
    text-decoration: none;
    font-size: 16px;
}

.popular-items {
    display: flex;
    flex-direction: column;
}

.popular-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.popular-item:last-child {
    border-bottom: none;
}

.item-name {
    font-weight: bold;
    color: #2c3e50;
}

.item-count {
    color: #7f8c8d;
}

.error-message {
    color: #e74c3c;
    margin-bottom: 15px;
    text-align: center;
}

.admin-login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #f4f7f6;
}

.admin-login-form {
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    text-align: center;
}

.admin-login-form h1 {
    margin-bottom: 20px;
    color: #2c3e50;
}

.form-group {
    margin-bottom: 15px;
    text-align: left;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #2c3e50;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

/* Login Page Styles */
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: linear-gradient(135deg, #3498db, #8e44ad);
    padding: 20px;
}

.login-box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
    padding: 30px;
    text-align: center;
}

.login-header {
    margin-bottom: 20px;
}

.login-header h1 {
    font-size: 24px;
    color: #2c3e50;
    margin-bottom: 10px;
}

.login-header p {
    font-size: 14px;
    color: #7f8c8d;
}

.login-error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.login-error i {
    font-size: 16px;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    text-align: left;
}

.form-group label {
    font-size: 14px;
    color: #2c3e50;
    margin-bottom: 5px;
    display: block;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
}

.login-btn {
    background: #3498db;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.login-btn:hover {
    background: #2980b9;
}

.login-footer {
    margin-top: 20px;
    font-size: 14px;
    color: #7f8c8d;
}

.login-footer a {
    color: #3498db;
    text-decoration: none;
    transition: color 0.3s;
}

.login-footer a:hover {
    color: #2980b9;
}
<style/>