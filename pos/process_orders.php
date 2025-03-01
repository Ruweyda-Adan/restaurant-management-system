<?php
include('../includes/db_connect.php');
$query = "SELECT * FROM orders WHERE status = 'Pending' ORDER BY order_time DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Process Orders</title>
</head>
<body>
<h2>Incoming Orders</h2>
<table border="1">
    <tr>
        <th>Order ID</th>
        <th>Type</th>
        <th>Total Price</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['order_type']; ?></td>
        <td>Ksh <?php echo number_format($row['total_price'], 2); ?></td>
        <td><?php echo $row['status']; ?></td>
        <td>
            <button onclick="updateOrderStatus(<?php echo $row['id']; ?>)">Mark as Completed</button>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<script>
function updateOrderStatus(orderId) {
    fetch("../pos/update_order.php?id=" + orderId)
        .then(res => res.text())
        .then(() => location.reload());
}
</script>

</body>
</html>
