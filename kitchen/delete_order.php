<?php
session_start();
$conn = new mysqli("localhost", "root", "", "restaurant_db");
// Get order ID from URL parameter
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    // Invalid order ID
    $_SESSION['message'] = "Invalid order ID";
    $_SESSION['message_type'] = "danger";
    
    // Redirect to index
    header('Location: index.php');
    exit;
}
// Start transaction to ensure data integrity
$conn->begin_transaction();
try {
    // First delete order items (child records)
    $delete_items_query = "DELETE FROM order_items WHERE order_id = $order_id";
    $conn->query($delete_items_query);
    
    // Then delete the order
    $delete_order_query = "DELETE FROM orders WHERE id = $order_id";
    $conn->query($delete_order_query);
    
    // Commit the transaction
    $conn->commit();
    
    // Set success message
    $_SESSION['message'] = "Order #$order_id has been deleted successfully";
    $_SESSION['message_type'] = "success";
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    
    // Set error message
    $_SESSION['message'] = "Error deleting order: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Close the connection
$conn->close();

// Redirect back to orders page
header('Location: index.php');
exit;
?>