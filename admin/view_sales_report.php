<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "restaurant_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Default date range (last 7 days)
$start_date = date('Y-m-d', strtotime('-7 days'));
$end_date = date('Y-m-d');

// Update date range if filter is applied
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// First, update the total_amount in orders table based on order_items
$update_totals_sql = "
    UPDATE orders o
    LEFT JOIN (
        SELECT order_id, SUM(subtotal) as total
        FROM order_items
        GROUP BY order_id
    ) oi ON o.id = oi.order_id
    SET o.total_amount = COALESCE(oi.total, 0)
    WHERE o.total_amount = 0 OR o.total_amount IS NULL
";
$conn->query($update_totals_sql);

// Query to get real order data from the orders table
$sql = "SELECT o.id, o.created_at as date, o.customer_name, o.total_amount as amount
        FROM orders o
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$sales = [];
$total_revenue = 0;
$total_orders = 0;

while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
    $total_revenue += floatval($row['amount']);
    $total_orders++;
}

// Get data for the payment method breakdown
$payment_sql = "SELECT payment_status, COUNT(*) as count, SUM(total_amount) as total
                FROM orders 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY payment_status";

$payment_stmt = $conn->prepare($payment_sql);
$payment_stmt->bind_param("ss", $start_date, $end_date);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();

$payment_data = [];
while ($row = $payment_result->fetch_assoc()) {
    $payment_data[$row['payment_status']] = [
        'count' => $row['count'],
        'total' => floatval($row['total'])
    ];
}

// Get data for order type breakdown (Dine-in vs Takeaway)
$type_sql = "SELECT type, COUNT(*) as count, SUM(total_amount) as total
             FROM orders 
             WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY type";

$type_stmt = $conn->prepare($type_sql);
$type_stmt->bind_param("ss", $start_date, $end_date);
$type_stmt->execute();
$type_result = $type_stmt->get_result();

$type_data = [];
while ($row = $type_result->fetch_assoc()) {
    $type_data[$row['type']] = [
        'count' => $row['count'],
        'total' => floatval($row['total'])
    ];
}

// Get top selling menu items
$top_items_sql = "SELECT mi.name, SUM(oi.quantity) as quantity_sold, 
                  SUM(oi.subtotal) as revenue
                  FROM order_items oi
                  JOIN menu_items mi ON oi.menu_item_id = mi.id
                  JOIN orders o ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ?
                  GROUP BY mi.name
                  ORDER BY quantity_sold DESC
                  LIMIT 5";

$top_items_stmt = $conn->prepare($top_items_sql);
$top_items_stmt->bind_param("ss", $start_date, $end_date);
$top_items_stmt->execute();
$top_items_result = $top_items_stmt->get_result();

$top_items = [];
while ($row = $top_items_result->fetch_assoc()) {
    $top_items[] = $row;
}

$stmt->close();
$payment_stmt->close();
$type_stmt->close();
$top_items_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report | Restaurant Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            overflow-x: hidden;
            height: 100%;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
  color: #FFFFFF;
}

        .filters {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .filters input, .filters button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filters button {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            border: none;
        }
        .filters button:hover {
            background-color: #45a049;
        }
        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e9f7ef;
            border-radius: 5px;
        }
        .summary div {
            font-size: 18px;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
        }
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            color: #333;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 16px;
        }
        .print-btn {
            margin-bottom: 20px;
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .print-btn:hover {
            background-color: #0b7dda;
        }
        
        .admin-sidebar {
            width: 250px;
            height: 100vh;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 5px rgba(255, 253, 253, 0);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .admin-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .admin-logo h2 {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
            padding-bottom: 10px;
            border-bottom: none;
        }
        
        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .admin-nav a {
            text-decoration: none;
            color: white;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s ease-in-out;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: #1abc9c;
        }
        
        .admin-nav a i {
            font-size: 18px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
                padding: 15px 5px;
            }
            
            .admin-logo h2 {
                display: none;
            }
            
            .admin-nav a span {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .filters {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .summary {
                flex-direction: column;
            }
            
            .grid-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="admin-sidebar">
    <div class="admin-logo">
        <h2>Restaurant Admin</h2>
    </div>
    <nav class="admin-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
        <a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i> <span>Manage Menu</span>
        </a>
        <a href="manage_staff.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_staff.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> <span>Manage Staff</span>
        </a>
        <a href="view_sales_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_sales_report.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> <span>Sales Report</span>
        </a>
        
        <a href="set_restaurant_branding.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'set_restaurant_branding.php' ? 'active' : ''; ?>">
            <i class="fas fa-palette"></i> <span>Restaurant Branding</span>
        </a>
        <a href="#" id="logout-btn">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</div>

<div class="main-content">
    <div class="container">
        <h1><i class="fas fa-chart-bar"></i> Sales Report</h1>

        <form method="POST" class="filters">
            <label>From:</label>
            <input type="date" name="start_date" value="<?= $start_date ?>" required>
            <label>To:</label>
            <input type="date" name="end_date" value="<?= $end_date ?>" required>
            <button type="submit"><i class="fas fa-filter"></i> Filter</button>
        </form>

        <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>

        <div class="summary">
            <div><strong>Total Revenue:</strong> KSh <?= number_format($total_revenue, 2) ?></div>
            <div><strong>Total Orders:</strong> <?= $total_orders ?></div>
            <div><strong>Average Order Value:</strong> KSh <?= $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00' ?></div>
        </div>

        <div class="grid-container">
            <!-- Payment Method Breakdown -->
            <div class="card">
                <div class="card-title"><i class="fas fa-money-bill-wave"></i> Payment Methods</div>
                <?php if (count($payment_data) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th>Orders</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_data as $method => $data): ?>
                                <tr>
                                    <td><?= ucfirst($method) ?></td>
                                    <td><?= $data['count'] ?></td>
                                    <td>KSh <?= number_format($data['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No payment data available</p>
                <?php endif; ?>
            </div>

            <!-- Order Type Breakdown -->
            <div class="card">
                <div class="card-title"><i class="fas fa-utensils"></i> Order Types</div>
                <?php if (count($type_data) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Orders</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($type_data as $type => $data): ?>
                                <tr>
                                    <td><?= $type ?></td>
                                    <td><?= $data['count'] ?></td>
                                    <td>KSh <?= number_format($data['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No order type data available</p>
                <?php endif; ?>
            </div>

            <!-- Top Selling Items -->
            <div class="card">
                <div class="card-title"><i class="fas fa-trophy"></i> Top Selling Items</div>
                <?php if (count($top_items) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_items as $item): ?>
                                <tr>
                                    <td><?= $item['name'] ?></td>
                                    <td><?= $item['quantity_sold'] ?></td>
                                    <td>KSh <?= number_format($item['revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No top item data available</p>
                <?php endif; ?>
            </div>
        </div>

        <h2><i class="fas fa-list"></i> Order Details</h2>

        <?php if (count($sales) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount (KSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= date('Y-m-d H:i', strtotime($sale['date'])) ?></td>
                            <td><?= $sale['id'] ?></td>
                            <td><?= $sale['customer_name'] ?></td>
                            <td><?= number_format($sale['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data"><i class="fas fa-exclamation-circle"></i> No order data found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Simple logout functionality
    document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if(confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    });
</script>

</body>
</html>