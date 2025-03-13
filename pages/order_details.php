<?php
session_start();
require_once('../includes/db_connect.php');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch order details
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
$order_items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .order-details-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            font-size: 2.5rem;
            color: #222;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1rem;
            color: #555;
            margin: 10px 0;
        }

        strong {
            color: #ff4757;
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background-color: #ff4757;
            color: #fff;
            text-decoration: none;
            border-radius: 25px;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .back-button:hover {
            background-color: #ff6b81;
            transform: translateY(-3px);
        }

        .back-button:active {
            transform: translateY(0);
        }

        .order-items {
            margin-top: 20px;
            text-align: left;
        }

        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-items th, .order-items td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .order-items th {
            background-color: #ff4757;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <h1>Order Details</h1>
        <p>Order ID: <strong><?php echo $order['id']; ?></strong></p>
        <p>Customer Name: <strong><?php echo $order['customer_name']; ?></strong></p>
        <p>Order Type: <strong><?php echo $order['order_type']; ?></strong></p>
        <p>Table Number: <strong><?php echo $order['table_number']; ?></strong></p>
        <p>Total Price: <strong>KSh <?php echo number_format($order['total_price'], 2); ?></strong></p>
        <p>Status: <strong><?php echo $order['status']; ?></strong></p>

        <div class="order-items">
            <h2>Order Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo $item['menu_item_id']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>KSh <?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="menu.php" class="back-button">Back to Menu</a>
    </div>
</body>
</html>