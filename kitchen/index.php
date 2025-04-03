<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['kitchen_staff_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "restaurant_db");

// Fetch all orders with latest first
$query = "SELECT o.*, COUNT(oi.id) as item_count, 
          DATE_FORMAT(o.order_time, '%d %b %Y %h:%i %p') as formatted_time 
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id 
          GROUP BY o.id 
          ORDER BY o.order_time DESC";
$result = $conn->query($query);

// Get counts for order stats
$pending_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'")->fetch_assoc()['count'];
$completed_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'")->fetch_assoc()['count'];
$total_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$today_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_time) = CURDATE()")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_price) as total FROM orders")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Staff Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">Restaurant Management</div>
            <div class="user-nav">
                <div class="user-info">
                    <!-- User info here -->
                </div>
                <a href="logout.php" class="btn btn-danger">Logout</a>
                <a href="change_password.php" class="btn btn-primary">Change Password</a>
            </div>
        </header>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3><?php echo $total_count; ?></h3>
                <p>Total Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?php echo $completed_count; ?></h3>
                <p>Completed Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <h3><?php echo $today_count; ?></h3>
                <p>Today's Orders</p>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>KSh <?php echo number_format($total_revenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
        
        <div class="orders-section">
            <div class="section-header">
                <h2 class="section-title">Recent Orders</h2>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Special Instructions</th> <!-- New Column -->
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="order-id">#<?php echo $row['id']; ?></td>
                                    <td><?php echo $row['customer_name']; ?></td>
                                    <td>
                                        <?php echo $row['order_type']; ?>
                                        <?php if($row['table_number'] > 0): ?>
                                            <small>(Table #<?php echo $row['table_number']; ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['item_count']; ?> items</td>
                                    <td>KSh <?php echo number_format($row['total_price'], 2); ?></td>
                                    <td><?php echo $row['formatted_time']; ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['special_instructions'])): ?>
                                            <div class="special-instructions">
                                                <i class="fas fa-info-circle"></i>
                                                <span><?php echo $row['special_instructions']; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-instructions">No special instructions</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="receipt.php?order_id=<?php echo $row['id']; ?>" class="action-btn view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="manage_orders.php?order_id=<?php echo $row['id']; ?>" class="action-btn update">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                        <a href="delete_order.php?order_id=<?php echo $row['id']; ?>" class="action-btn delete" 
                                           onclick="return confirm('Are you sure you want to delete this order?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No orders found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto refresh the page every 60 seconds to show new orders
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>

<style>
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #ff4757;
        }
        
        .user-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            font-size: 0.9rem;
        }
        
        .logout-btn {
            background-color: #ff4757;
            color: #fff;
            padding: 8px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        
        .logout-btn:hover {
            background-color: #ff6b81;
        }
        
        /* Dashboard Stats */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 2rem;
            color: #ff4757;
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            margin: 0;
            color: #222;
        }
        
        .stat-card p {
            color: #666;
            margin: 5px 0 0;
        }
        
        /* Orders Table */
        .orders-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin: 0;
            color: #222;
        }
        
        .refresh-btn {
            background-color: #f2f2f2;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .refresh-btn:hover {
            background-color: #e6e6e6;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th, .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        .orders-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .order-id {
            font-weight: 600;
            color: #ff4757;
        }
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .status-cancelled {
            background-color: #fbe9e7;
            color: #d32f2f;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .action-btn.view {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        
        .action-btn.view:hover {
            background-color: #bbdefb;
        }
        
        .action-btn.update {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .action-btn.update:hover {
            background-color: #c8e6c9;
        }
        
        .action-btn.delete {
            background-color: #fbe9e7;
            color: #d32f2f;
        }
        
        .action-btn.delete:hover {
            background-color: #ffccbc;
        }
        
        .empty-orders {
            text-align: center;
            padding: 50px 0;
            color: #999;
        }
        
        .empty-orders i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .orders-table th, .orders-table td {
                padding: 10px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            .btn {
    padding: 8px 15px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

.btn-primary {
    background-color: #2196f3;
    color: #fff;
}

.btn-primary:hover {
    background-color: #1e88e5;
}

.btn-danger {
    background-color: #ff4757;
    color: #fff;
}

.btn-danger:hover {
    background-color: #ff6b81;
}
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-nav {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>