<?php
session_start();
require_once('../includes/db_connect.php');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Validate order ID
if ($order_id <= 0) {
    // Redirect to menu if order ID is invalid
    header('Location: menu.php');
    exit;
}

// Fetch order details
$order = $conn->query("SELECT *, instructions FROM orders WHERE id = $order_id")->fetch_assoc();

// Check if the order exists
if (!$order) {
    // Redirect to menu if the order is not found
    header('Location: menu.php');
    exit;
}

// Handle item removal
if (isset($_GET['item_id'])) {
    $item_id = intval($_GET['item_id']);
    if ($item_id > 0) {
        // Remove the item from the order
        $conn->query("DELETE FROM order_items WHERE id = $item_id");

        // Recalculate total price
        $total_price = $conn->query("SELECT SUM(subtotal) AS total FROM order_items WHERE order_id = $order_id")->fetch_assoc()['total'];
        $conn->query("UPDATE orders SET total_price = $total_price WHERE id = $order_id");

        // Redirect to refresh the page
        header("Location: order_details.php?order_id=$order_id");
        exit;
    }
}

// Fetch order items
$order_items = $conn->query("SELECT oi.*, mi.name, mi.price 
                             FROM order_items oi 
                             JOIN menu_items mi ON oi.menu_item_id = mi.id 
                             WHERE oi.order_id = $order_id")->fetch_all(MYSQLI_ASSOC);

// Fetch all menu items for adding new items
$menu_items = $conn->query("SELECT * FROM menu_items")->fetch_all(MYSQLI_ASSOC);

// Handle form submission for updating order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_order'])) {
        // Update order items
        foreach ($_POST['quantity'] as $item_id => $quantity) {
            if ($quantity > 0) {
                $conn->query("UPDATE order_items SET quantity = $quantity WHERE id = $item_id");
            } else {
                $conn->query("DELETE FROM order_items WHERE id = $item_id");
            }
        }

        // Add new items
        if (!empty($_POST['new_item'])) {
            $new_item_id = intval($_POST['new_item']);
            $new_quantity = intval($_POST['new_quantity']);
            if ($new_item_id > 0 && $new_quantity > 0) {
                $conn->query("INSERT INTO order_items (order_id, menu_item_id, quantity, subtotal) 
                               VALUES ($order_id, $new_item_id, $new_quantity, 
                               (SELECT price FROM menu_items WHERE id = $new_item_id) * $new_quantity)");
            }
        }

        // Update instructions
        $instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : '';
        $conn->query("UPDATE orders SET instructions = '$instructions' WHERE id = $order_id");

        // Recalculate total price
        $total_price = $conn->query("SELECT SUM(subtotal) AS total FROM order_items WHERE order_id = $order_id")->fetch_assoc()['total'];
        $conn->query("UPDATE orders SET total_price = $total_price WHERE id = $order_id");

        // Redirect to refresh the page
        header("Location: order_details.php?order_id=$order_id");
        exit;
    }
}

// Fetch the updated order details
$order = $conn->query("SELECT *, instructions FROM orders WHERE id = $order_id")->fetch_assoc();
$order_items = $conn->query("SELECT oi.*, mi.name, mi.price 
                             FROM order_items oi 
                             JOIN menu_items mi ON oi.menu_item_id = mi.id 
                             WHERE oi.order_id = $order_id")->fetch_all(MYSQLI_ASSOC);
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
        /* Add your existing styles here */
    </style>
</head>
<body>
    <div class="order-details-container">
        <h1>Order Details</h1>
        <p>Order ID: <strong><?php echo $order['id']; ?></strong></p>
        <p>Customer Name: <strong><?php echo $order['customer_name']; ?></strong></p>
        <p>Order Type: <strong><?php echo $order['order_type']; ?></strong></p>
        <p>Table Number: <strong><?php echo $order['table_number']; ?></strong></p>
        <p>Total Price: <strong id="total-price">KSh <?php echo number_format($order['total_price'], 2); ?></strong></p>
        <p>Status: <strong><?php echo $order['status']; ?></strong></p>

        <div class="order-items">
            <h2>Order Items</h2>
            <form method="POST" action="">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo $item['name']; ?></td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" min="1">
                                </td>
                                <td>KSh <?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <button type="button" class="btn btn-secondary" 
                                            onclick="if(confirm('Are you sure you want to remove this item?')) { 
                                                this.form.action = 'order_details.php?order_id=<?php echo $order_id; ?>&item_id=<?php echo $item['id']; ?>'; 
                                                this.form.submit(); 
                                            }">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="form-group">
                    <label for="new_item">Add New Item:</label>
                    <select name="new_item" id="new_item">
                        <option value="">Select an item</option>
                        <?php foreach ($menu_items as $menu_item): ?>
                            <option value="<?php echo $menu_item['id']; ?>"><?php echo $menu_item['name']; ?> (KSh <?php echo number_format($menu_item['price'], 2); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="new_quantity" min="1" value="1" style="width: 80px;">
                </div>

                <div class="form-group">
                    <label for="instructions">Special Instructions:</label>
                    <textarea name="instructions" id="instructions" rows="3"><?php echo $order['instructions'] ?? ''; ?></textarea>
                </div>

                <div class="form-buttons">
                    <button type="submit" name="update_order" class="btn btn-primary">Save Changes</button>
                    <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to update the total price dynamically
        function updateTotalPrice() {
            fetch('order_details.php?order_id=<?php echo $order_id; ?>')
                .then(response => response.text())
                .then(html => {
                    // Parse the HTML response to extract the total price
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const totalPriceElement = doc.getElementById('total-price');
                    if (totalPriceElement) {
                        document.getElementById('total-price').textContent = totalPriceElement.textContent;
                    }
                })
                .catch(error => {
                    console.error('Error fetching total price:', error);
                });
        }

        // Update the total price when the form is submitted
        document.querySelector('form').addEventListener('submit', function() {
            setTimeout(updateTotalPrice, 500); // Wait for the server to process the changes
        });

        // Initial call to update the total price
        updateTotalPrice();
    </script>
</body>
</html>
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
            max-width: 800px;
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

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input[type="number"] {
            width: 80px;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-primary {
            background-color: #ff4757;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #ff6b81;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: #fff;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
    </style>