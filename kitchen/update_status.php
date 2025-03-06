<?php
session_start();
$conn = new mysqli("localhost", "root", "", "restaurant_db");

// Check if form was submitted with order ID and status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validate order exists
    $check_query = "SELECT id FROM orders WHERE id = $order_id";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        // Update order status
        $update_query = "UPDATE orders SET status = '$status' WHERE id = $order_id";
        
        if ($conn->query($update_query)) {
            // Set success message
            $_SESSION['message'] = "Order #$order_id status updated to '$status'";
            $_SESSION['message_type'] = "success";
        } else {
            // Set error message
            $_SESSION['message'] = "Error updating order status: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
    } else {
        // Order not found
        $_SESSION['message'] = "Order #$order_id not found";
        $_SESSION['message_type'] = "danger";
    }
} else {
    // Invalid request
    $_SESSION['message'] = "Invalid request";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to manage order page
header("Location: manage_orders.php?order_id=$order_id");
exit;
?>