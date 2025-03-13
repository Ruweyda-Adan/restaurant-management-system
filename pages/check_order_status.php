<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

session_start();
require_once('../includes/db_connect.php');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id > 0) {
    while (true) {
        // Fetch the latest order status
        $order = $conn->query("SELECT status FROM orders WHERE id = $order_id")->fetch_assoc();

        if ($order) {
            $status = $order['status'];

            // Send the status to the client
            echo "data: " . json_encode(['status' => $status]) . "\n\n";
            ob_flush();
            flush();
        }

        // Break the loop if the connection is closed
        if (connection_aborted()) {
            break;
        }

        // Wait for 5 seconds before checking again
        sleep(5);
    }
}
?>