<?php
session_start();
require_once('../includes/db_connect.php');

// Initialized response array for AJAX requests
$response = ['success' => false, 'message' => ''];

// Handles different actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Adds item to cart
    if ($action == 'add') {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
        $item_price = isset($_POST['item_price']) ? floatval($_POST['item_price']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        // Validates inputs
        if ($item_id > 0 && $item_price > 0 && !empty($item_name)) {
            // Checks if item already exists in cart
            $item_exists = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id'] == $item_id) {
                    // Updates quantity
                    $_SESSION['cart'][$key]['quantity'] += $quantity;
                    $_SESSION['cart'][$key]['subtotal'] = $_SESSION['cart'][$key]['quantity'] * $_SESSION['cart'][$key]['price'];
                    $item_exists = true;
                    break;
                }
            }
            
            // If item doesn't exist, add it
            if (!$item_exists) {
                $_SESSION['cart'][] = [
                    'id' => $item_id,
                    'name' => $item_name,
                    'price' => $item_price,
                    'quantity' => $quantity,
                    'subtotal' => $item_price * $quantity
                ];
            }
            
            $response['success'] = true;
            $response['message'] = 'Item added to cart';
            $response['cart_count'] = count($_SESSION['cart']);
        } else {
            $response['message'] = 'Invalid item data';
        }
    }
    
    // Updates item quantity
    elseif ($action == 'update') {
        $cart_index = isset($_POST['cart_index']) ? intval($_POST['cart_index']) : -1;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if ($cart_index >= 0 && isset($_SESSION['cart'][$cart_index])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$cart_index]['quantity'] = $quantity;
                $_SESSION['cart'][$cart_index]['subtotal'] = $_SESSION['cart'][$cart_index]['price'] * $quantity;
                $response['success'] = true;
                $response['message'] = 'Quantity updated';
            } else {
                // Remove item if quantity is zero or negative
                array_splice($_SESSION['cart'], $cart_index, 1);
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
            }
            $response['cart_count'] = count($_SESSION['cart']);
        } else {
            $response['message'] = 'Invalid cart item';
        }
    }
    
    // Removes item from cart
    elseif ($action == 'remove') {
        $cart_index = isset($_POST['cart_index']) ? intval($_POST['cart_index']) : -1;
        
        if ($cart_index >= 0 && isset($_SESSION['cart'][$cart_index])) {
            array_splice($_SESSION['cart'], $cart_index, 1);
            $response['success'] = true;
            $response['message'] = 'Item removed from cart';
            $response['cart_count'] = count($_SESSION['cart']);
        } else {
            $response['message'] = 'Invalid cart item';
        }
    }
    
    // Submits order
    elseif ($action == 'submit') {
        $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
        $order_type = isset($_POST['order_type']) ? trim($_POST['order_type']) : 'Dine-in';
        $table_number = isset($_SESSION['table_number']) ? intval($_SESSION['table_number']) : 0;
        
        // Validates inputs
        if (empty($customer_name)) {
            $response['message'] = 'Customer name is required';
        } elseif (count($_SESSION['cart']) == 0) {
            $response['message'] = 'Cart is empty';
        } else {
            // Calculates total
            $total_price = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total_price += $item['subtotal'];
            }
            
            // Starts transaction
            $conn->begin_transaction();
            
            try {
                // Inserts order
                $order_sql = "INSERT INTO orders (customer_name, order_type, total_price, status) 
                             VALUES (?, ?, ?, 'Pending')";
                $stmt = $conn->prepare($order_sql);
                $stmt->bind_param("ssd", $customer_name, $order_type, $total_price);
                $stmt->execute();
                
                $order_id = $conn->insert_id;
                
                // Inserts order items
                $items_sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, subtotal) 
                             VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($items_sql);
                
                foreach ($_SESSION['cart'] as $item) {
                    // Validates menu_item_id before inserting
                    $menu_item_id = $item['id'];
                    $check_menu = $conn->query("SELECT id FROM menu_items WHERE id = $menu_item_id");
                    if ($check_menu->num_rows == 0) {
                        throw new Exception("Invalid menu item selected: $menu_item_id");
                    }
                    
                    // Inserts order item
                    $stmt->bind_param("iiid", $order_id, $menu_item_id, $item['quantity'], $item['subtotal']);
                    $stmt->execute();
                }
                
                // Commits transaction
                $conn->commit();
                
                // Stores order ID for receipt
                $_SESSION['last_order_id'] = $order_id;
                
                // Clears cart
                $_SESSION['cart'] = [];
                
                $response['success'] = true;
                $response['message'] = 'Order submitted successfully';
                $response['order_id'] = $order_id;
                $response['redirect'] = 'receipt.php?order_id=' . $order_id;
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $response['message'] = 'Error processing order: ' . $e->getMessage();
                error_log('Order Error: ' . $e->getMessage()); // Log error
            }
        }
    }
    
    // Returns JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not an AJAX request, redirects to menu page
header('Location: menu.php');
exit;
?>