<?php
session_start();
require_once('../includes/db_connect.php');

// Initialize cart 
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get table number
$table_number = isset($_SESSION['table_number']) ? intval($_SESSION['table_number']) : 0;

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['subtotal'];
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Order</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="cart-container">
        <header>
            <h1>Your Order</h1>
            <?php if ($table_number > 0): ?>
                <div class="table-info">Table #<?php echo $table_number; ?></div>
            <?php endif; ?>
            <a href="menu.php<?php echo $table_number ? "?table=$table_number" : ""; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Menu
            </a>
        </header>
        
        <?php if (count($_SESSION['cart']) > 0): ?>
            <div class="cart-items">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                            <tr>
                                <td><?php echo $item['name']; ?></td>
                                <td>KSh <?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="quantity-controls">
                                        <button class="qty-btn decrease" data-index="<?php echo $index; ?>">-</button>
                                        <input type="number" min="1" value="<?php echo $item['quantity']; ?>" 
                                            class="quantity-input" data-index="<?php echo $index; ?>">
                                        <button class="qty-btn increase" data-index="<?php echo $index; ?>">+</button>
                                    </div>
                                </td>
                                <td>KSh <?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <button class="remove-btn" data-index="<?php echo $index; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="total-label">Total:</td>
                            <td colspan="2" class="total-amount">KSh <?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="order-form">
                <h2>Complete Your Order</h2>
                <form id="checkout-form">
                    <!-- Dynamic Fields -->
                    <div class="form-group" id="customer-name-group">
                        <label for="customer-name">Your Name:</label>
                        <input type="text" id="customer-name" name="customer_name" required>
                    </div>
                    
                    <div class="form-group" id="table-number-group" style="display: none;">
                        <label for="table-number">Table Number:</label>
                        <input type="number" id="table-number" name="table_number" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>Order Type:</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="order_type" value="Dine-in" checked>
                                Dine-in
                            </label>
                            <label>
                                <input type="radio" name="order_type" value="Takeaway">
                                Takeaway
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group estimated-time">
                        <p>Estimated preparation time: <span id="est-time">15-20</span> minutes</p>
                    </div>
                    
                    <button type="submit" class="checkout-btn">Place Order</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart cart-icon"></i>
                <p>Your cart is empty</p>
                <a href="menu.php<?php echo $table_number ? "?table=$table_number" : ""; ?>" class="continue-shopping">
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="../script.js"></script>
    <script>
        // Toggle table number input based on order type
document.querySelectorAll('input[name="order_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const tableNumberGroup = document.getElementById('table-number-group');
        const customerNameGroup = document.getElementById('customer-name-group');
        if (this.value === 'Dine-in') {
            tableNumberGroup.style.display = 'block';
            customerNameGroup.style.display = 'none'; 
            // Make table number required for dine-in
            document.getElementById('table-number').setAttribute('required', '');
            // For dine-in, used a default name based on table
            document.getElementById('customer-name').value = 'Table Customer';
            document.getElementById('customer-name').removeAttribute('required');
        } else {
            tableNumberGroup.style.display = 'none';
            customerNameGroup.style.display = 'block';
            // Make customer name required for takeaway
            document.getElementById('customer-name').setAttribute('required', '');
            // Remove required attribute for takeaway
            document.getElementById('table-number').removeAttribute('required');
        }
    });
});

// Trigger the change event on page load to set initial state
let initialOrderType = document.querySelector('input[name="order_type"]:checked');
if (initialOrderType) {
    initialOrderType.dispatchEvent(new Event('change'));
}

// Place order
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log("Form submitted"); 
    
    const formData = new FormData(this);
    formData.append('action', 'submit');
    
    // If dine-in is selected, validate table number
    if (document.querySelector('input[name="order_type"]:checked').value === 'Dine-in') {
        if (!document.getElementById('table-number').value || document.getElementById('table-number').value <= 0) {
            alert('Please enter a valid table number for dine-in orders.');
            return;
        }
    }
    
    console.log("Form data:", Object.fromEntries(formData)); 

    fetch('../pages/order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Response received:", response); 
        return response.json();
    })
    .then(data => {
        console.log("Data received:", data); 
        if (data.success) {
            // Redirect to thank-you page
            window.location.href = 'thank_you.php?order_id=' + data.order_id;
        } else {
            alert(data.message || 'Error processing order');
        }
    })
    .catch(error => {
        console.error('Error:', error); 
        alert('Something went wrong. Please try again.');
    });
});
        // Update quantity
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                updateQuantity(this.getAttribute('data-index'), this.value);
            });
        });

        // Increase quantity
        document.querySelectorAll('.increase').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const input = document.querySelector(`.quantity-input[data-index="${index}"]`);
                input.value = parseInt(input.value) + 1;
                updateQuantity(index, input.value);
            });
        });

        // Decrease quantity
        document.querySelectorAll('.decrease').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const input = document.querySelector(`.quantity-input[data-index="${index}"]`);
                if (parseInt(input.value) > 1) {
                    input.value = parseInt(input.value) - 1;
                    updateQuantity(index, input.value);
                }
            });
        });

        // Remove item
        document.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', function() {
                removeItem(this.getAttribute('data-index'));
            });
        });

        function updateQuantity(index, quantity) {
            fetch('../pages/order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&cart_index=${index}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to update prices
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function removeItem(index) {
            fetch('../pages/order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&cart_index=${index}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to update cart
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
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

.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-bottom: 2px solid #eee;
}

header h1 {
    font-size: 2.5rem;
    font-weight: 600;
    color: #222;
}

.table-info {
    font-size: 1.2rem;
    color: #555;
}

.back-button {
    background-color: #ff4757;
    color: #fff;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

.back-button:hover {
    background-color: #ff6b81;
}

/* Cart Items */
.cart-items {
    margin: 20px 0;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 15px;
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

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.quantity-controls .qty-btn {
    background-color: #ff4757;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.quantity-controls .qty-btn:hover {
    background-color: #ff6b81;
}

.quantity-controls input {
    width: 50px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 5px;
}

.remove-btn {
    background-color: #ff4757;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.remove-btn:hover {
    background-color: #ff6b81;
}

/* Order Form */
.order-form {
    margin-top: 40px;
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.order-form h2 {
    font-size: 1.8rem;
    margin-bottom: 20px;
    color: #222;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 1rem;
    margin-bottom: 5px;
    color: #555;
}

.form-group input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.radio-group {
    display: flex;
    gap: 15px;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    color: #555;
}

.estimated-time {
    font-size: 0.9rem;
    color: #555;
    margin-top: 10px;
}

.checkout-btn {
    background-color: #ff4757;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: 100%;
}

.checkout-btn:hover {
    background-color: #ff6b81;
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 50px 0;
    color: #666;
}

.empty-cart .cart-icon {
    font-size: 3rem;
    margin-bottom: 10px;
}

.empty-cart p {
    font-size: 1.2rem;
    margin-bottom: 20px;
}

.continue-shopping {
    background-color: #ff4757;
    color: #fff;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

tr.updated {
    background-color: #e8f5e9;
    transition: background-color 0.5s ease;
}

tr.removed {
    opacity: 0;
    transition: opacity 0.5s ease;
}

.continue-shopping:hover {
    background-color: #ff6b81;
}
<style/>