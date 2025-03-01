<?php
include('../includes/db_connect.php');

$orderId = $_GET['id'];
$query = "SELECT * FROM orders WHERE id = $orderId";
$result = $conn->query($query);
$order = $result->fetch_assoc();

echo "<h2>Receipt</h2>";
echo "Order ID: " . $order['id'] . "<br>";
echo "Total Price: Ksh " . number_format($order['total_price'], 2) . "<br>";
?>
