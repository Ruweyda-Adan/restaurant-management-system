<?php
session_start();
$conn = new mysqli("localhost", "root", "", "restaurant_db");

// Get order ID from URL parameter
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    // Redirect to index if no order ID
    header('Location: index.php');
    exit;
}

// Fetch order details
$order_query = "SELECT o.*, DATE_FORMAT(o.order_time, '%d %b %Y %h:%i %p') as formatted_time 
                FROM orders o WHERE o.id = $order_id";
$order_result = $conn->query($order_query);

if ($order_result->num_rows == 0) {
    // Order not found
    header('Location: index.php');
    exit;
}

$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = "SELECT oi.*, mi.name, mi.price 
                FROM order_items oi 
                JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Order #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    /* General Styles */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
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
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .page-title {
        font-size: 2rem;
        color: #2c3e50;
        margin: 0;
        font-weight: 600;
    }

    .back-btn {
        background-color: #e74c3c;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s ease;
    }

    .back-btn:hover {
        background-color: #c0392b;
    }

    /* Order Management */
    .order-management {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    @media (max-width: 768px) {
        .order-management {
            grid-template-columns: 1fr;
        }
    }

    /* Card Styles */
    .card {
        background-color: #fff;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .card-title {
        font-size: 1.5rem;
        margin: 0;
        color: #34495e;
        font-weight: 600;
    }

    .order-info {
        margin-bottom: 25px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 500;
        color: #7f8c8d;
    }

    .info-value {
        color: #2c3e50;
        font-weight: 500;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-pending {
        background-color: #fef9e7;
        color: #f1c40f;
    }

    .status-in-progress {
        background-color: #e8f6f3;
        color: #1abc9c;
    }

    .status-completed {
        background-color: #e8f5e9;
        color: #27ae60;
    }

    .status-cancelled {
        background-color: #f9ebea;
        color: #e74c3c;
    }

    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .items-table th, .items-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .items-table th {
        font-weight: 600;
        color: #34495e;
        background-color: #f8f9fa;
    }

    .items-table tr:hover {
        background-color: #f8f9fa;
    }

    /* Buttons */
    .btn {
        display: inline-block;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .btn-primary {
        background-color: #e74c3c;
        color: #fff;
    }

    .btn-primary:hover {
        background-color: #e74c3c;
    }

    .btn-secondary {
        background-color: #95a5a6;
        color: #fff;
    }

    .btn-secondary:hover {
        background-color: #7f8c8d;
    }

    .btn-danger {
        background-color: #e74c3c;
        color: #fff;
    }

    .btn-danger:hover {
        background-color: #c0392b;
    }

    .form-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #34495e;
    }

    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f8f9fa;
        font-size: 0.9rem;
        color: #333;
        transition: border-color 0.3s ease;
    }

    .form-group select:focus {
        border-color: #e74c3c;
        outline: none;
    }

    /* Alert Messages */
    .alert {
        padding: 12px 20px;
        border-radius: 5px;
        margin-bottom: 25px;
        font-size: 0.9rem;
    }

    .alert-success {
        background-color: #e8f6f3;
        border: 1px solid #a3e4d7;
        color: #1abc9c;
    }

    .alert-danger {
        background-color: #f9ebea;
        border: 1px solid #f5b7b1;
        color: #e74c3c;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Manage Order #<?php echo $order_id; ?></h1>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="order-management">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Order Details</h2>
                </div>
                
                <div class="order-info">
                    <div class="info-item">
                        <span class="info-label">Order ID:</span>
                        <span class="info-value">#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Customer:</span>
                        <span class="info-value"><?php echo $order['customer_name']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Type:</span>
                        <span class="info-value">
                            <?php echo $order['order_type']; ?>
                            <?php if($order['table_number'] > 0): ?>
                                (Table #<?php echo $order['table_number']; ?>)
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Time:</span>
                        <span class="info-value"><?php echo $order['formatted_time']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Amount:</span>
                        <span class="info-value">KSh <?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                </div>
                
                <a href="receipt.php?order_id=<?php echo $order_id; ?>" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                    <i class="fas fa-print"></i> View Receipt
                </a>
                
                <a href="delete_order.php?order_id=<?php echo $order_id; ?>" class="btn btn-danger" style="width: 100%;" 
                   onclick="return confirm('Are you sure you want to delete this order?');">
                    <i class="fas fa-trash"></i> Delete Order
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Order Items</h2>
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $item['name']; ?></td>
                                <td>KSh <?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>KSh <?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                            <td style="font-weight: bold;">KSh <?php echo number_format($order['total_price'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="card-header" style="margin-top: 20px;">
                    <h2 class="card-title">Update Status</h2>
                </div>
                
                <form action="update_status.php" method="post">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    
                    <div class="form-group">
                        <label for="status">Order Status:</label>
                        <select name="status" id="status">
                            <option value="Pending" <?php echo ($order['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo ($order['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($order['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo ($order['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>