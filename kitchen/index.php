<?php
$conn = new mysqli("localhost", "root", "", "restaurant_db");

$orders = $conn->query("SELECT * FROM orders WHERE status != 'Ready' ORDER BY created_at ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Staff Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-top: 20px;
            font-size: 24px;
        }

        
        table {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color:#ff4757;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        
        button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        button.preparing {
            background-color: #f39c12;
            color: #fff;
        }

        button.preparing:hover {
            background-color: #e67e22;
        }

        button.ready {
            background-color: #2ecc71;
            color: #fff;
        }

        button.ready:hover {
            background-color: #27ae60;
        }

        
        @media (max-width: 768px) {
            table {
                width: 100%;
            }

            th, td {
                padding: 10px;
            }

            button {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
    <script>
        function updateStatus(orderId, newStatus) {
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + orderId + '&status=' + newStatus
            }).then(() => location.reload());
        }
    </script>
</head>
<body>
    <h2>Kitchen Staff Panel</h2>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Type</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $orders->fetch_assoc()): ?>
            <tr>
                <td><?= $row["id"] ?></td>
                <td><?= $row["customer_name"] ?></td>
                <td><?= $row["type"] ?></td>
                <td><?= $row["status"] ?></td>
                <td>
                    <button class="preparing" onclick="updateStatus(<?= $row['id'] ?>, 'Preparing')">Preparing</button>
                    <button class="ready" onclick="updateStatus(<?= $row['id'] ?>, 'Ready')">Ready</button>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>