<?php
session_start();
$conn = new mysqli("localhost", "root", "", "restaurant_db");

// Get order ID
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 
            (isset($_SESSION['last_order_id']) ? $_SESSION['last_order_id'] : 0);

if ($order_id <= 0) {
    // Redirect to menu if no order ID
    header('Location: menu.php');
    exit;
}

// Fetch order details
$order_query = "SELECT o.*, DATE_FORMAT(o.order_time, '%d %b %Y %h:%i %p') as formatted_time 
                FROM orders o WHERE o.id = $order_id";
$order_result = $conn->query($order_query);

if ($order_result->num_rows == 0) {
    // Order not found
    header('Location: menu.php');
    exit;
}

$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = "SELECT oi.*, mi.name, mi.price 
                FROM order_items oi 
                JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_query);

// Estimated completion time (15-20 minutes from order time)
$order_time = strtotime($order['order_time']);
$est_min_time = date('h:i A', $order_time + (15 * 60));
$est_max_time = date('h:i A', $order_time + (20 * 60));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Your CSS styles here */
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt">
            <div class="receipt-header">
                <h1>Order Receipt</h1>
                <div class="order-id">Order #<?php echo $order_id; ?></div>
            </div>
            
            <div class="receipt-details">
                <div class="detail-row">
                    <span class="label">Date & Time:</span>
                    <span class="value"><?php echo $order['formatted_time']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Customer:</span>
                    <span class="value"><?php echo $order['customer_name']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Order Type:</span>
                    <span class="value"><?php echo $order['order_type']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-<?php echo strtolower($order['status']); ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Estimated Ready:</span>
                    <span class="value"><?php echo $est_min_time; ?> - <?php echo $est_max_time; ?></span>
                </div>
            </div>
            
            <div class="receipt-items">
                <table>
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
                            <td colspan="3" class="total-label">Total:</td>
                            <td class="total-amount">KSh <?php echo number_format($order['total_price'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="receipt-footer">
                <p>Thank you for your order!</p>
                <p class="small">Please show this receipt when collecting your order.</p>
                
                <div class="receipt-actions">
                    <button class="action-btn print-btn">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <a href="index.php" class="action-btn menu-btn">
                        <i class="fas fa-utensils"></i> Back to Kitchen Staff Panel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.print-btn').addEventListener('click', function() {
            window.print();
        });
        document.querySelector('.menu-btn').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = this.getAttribute('href');
        });
    </script>
</body>
</html>

     
<style>

    /* General Styles */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f9f9f9;
    color: #333;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.receipt-container {
    width: 100%;
    max-width: 600px;
    margin: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

/* Receipt Header */
.receipt-header {
    text-align: center;
    margin-bottom: 20px;
}

.receipt-header h1 {
    font-size: 2rem;
    font-weight: 600;
    color: #222;
    margin: 0;
}

.order-id {
    font-size: 1.2rem;
    color: #555;
    margin-top: 5px;
}

/* Receipt Details */
.receipt-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.detail-row:last-child {
    border-bottom: none;
}

.label {
    font-weight: 500;
    color: #555;
}

.value {
    color: #222;
}

.status-pending {
    color: #ff4757;
}

.status-completed {
    color: #2ed573;
}

.status-in-progress {
    color: #ffa502;
}

/* Receipt Items */
.receipt-items {
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background-color: #ff4757;
    color: #fff;
    font-weight: 600;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

.total-label {
    font-weight: 600;
    color: #555;
}

.total-amount {
    font-weight: 600;
    color: #222;
}

/* Receipt Footer */
.receipt-footer {
    text-align: center;
    margin-top: 20px;
}

.receipt-footer p {
    margin: 5px 0;
    color: #555;
}

.receipt-footer .small {
    font-size: 0.9rem;
    color: #777;
}

.receipt-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.action-btn {
    background-color: #ff4757;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 0.9rem;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.3s ease;
}

.action-btn:hover {
    background-color: #ff6b81;
}

/* Print Styles */
@media print {
    body {
        background-color: #fff;
    }

    .receipt-container {
        box-shadow: none;
        border: 1px solid #ddd;
    }

    .action-btn {
        display: none;
    }
}
<style/>
