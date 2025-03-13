<?php
session_start();
require_once('../includes/db_connect.php');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch order details
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Styles */
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

        .thank-you-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
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

        /* Animation for the order status */
        .status-animation {
            display: inline-block;
            margin-top: 20px;
            font-size: 1.2rem;
            color: #ff4757;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <h1>Thank You for Your Order!</h1>
        <p>Your order (#<?php echo $order_id; ?>) is being prepared.</p>
        <p>Estimated preparation time: <strong>15-20 minutes</strong>.</p>
        <p>You will be notified when your order is ready.</p>
        <div class="status-animation">Preparing your order...</div>
        <a href="order_details.php?order_id=<?php echo $order_id; ?>" class="back-button">View Your Order</a>
    </div>

    <script>
        // Polling to check if order is ready
        setInterval(() => {
            fetch('check_order_status.php?order_id=<?php echo $order_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'Ready') {
                        alert('Your order is ready!');
                        window.location.href = 'order_details.php?order_id=<?php echo $order_id; ?>';
                    }
                });
        }, 5000); // Check every 5 seconds

        setTimeout(() => {
        window.location.href = 'order_details.php?order_id=<?php echo $order_id; ?>';
    }, 5000); // 5 seconds
    </script>
</body>
</html>