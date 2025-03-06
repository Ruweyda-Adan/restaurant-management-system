<?php
session_start();
require_once('../includes/db_connect.php');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$order = $conn->query("SELECT status FROM orders WHERE id = $order_id")->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['status' => $order['status']]);
exit;
?>