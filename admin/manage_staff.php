<?php
$conn = new mysqli("localhost", "root", "", "restaurant_db");

// Handle staff addition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if (!empty($name) && !empty($position) && !empty($email) && !empty($phone)) {
        $sql = "INSERT INTO staff (name, position, email, phone) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $position, $email, $phone);
        if ($stmt->execute()) {
            $success_message = "Staff member added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "All fields are required!";
    }
}

// Fetch all staff members
$staff = [];
$result = $conn->query("SELECT * FROM staff ORDER BY date_joined DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff | Restaurant Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #4a6da7;
            --primary-light: #5d80ba;
            --primary-dark: #3a5a94;
            --secondary: #f8f9fa;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            overflow-x: hidden;
        }
        
        .admin-sidebar {
            width: 250px;
            height: 100vh;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .admin-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .admin-logo h2 {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .admin-nav a {
            text-decoration: none;
            color: white;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s ease-in-out;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: #1abc9c;
        }
        
        .admin-nav a i {
            font-size: 18px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
            margin-bottom: 2rem;
        }
        
        header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 500;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--secondary);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--primary);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(74, 109, 167, 0.25);
        }
        
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 4px;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            cursor: pointer;
        }
        
        .btn-primary {
            color: #fff;
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #e3e6f0;
            background-color: var(--secondary);
            color: var(--dark);
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
        }
        
        table tbody td {
            padding: 0.75rem;
            border-top: 1px solid #e3e6f0;
        }
        
        table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        
        .badge-primary {
            color: #fff;
            background-color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .container {
                width: 95%;
                padding: 10px;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="admin-logo">
            <h2>Restaurant Admin</h2>
        </div>
        <nav class="admin-nav">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> Manage Menu</a>
            <a href="manage_staff.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_staff.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Staff</a>
            
            <a href="view_sales_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_sales_report.php' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Sales Report</a>
   
            <a href="set_restaurant_branding.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'set_restaurant_branding.php' ? 'active' : ''; ?>"><i class="fas fa-palette"></i> Restaurant Branding</a>
            <a href="#" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container">
            <header>
                <h1><i class="fas fa-users"></i> Restaurant Staff Management</h1>
            </header>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-user-plus"></i> Add New Staff Member</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" required>
                        </div>

                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" class="form-control" id="position" name="position" placeholder="Enter position" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter phone number" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Staff Member
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Staff Directory</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Date Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($staff)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">No staff members found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($staff as $member): ?>
                                        <tr>
                                            <td><?= $member['id']; ?></td>
                                            <td><?= $member['name']; ?></td>
                                            <td><span class="badge badge-primary"><?= $member['position']; ?></span></td>
                                            <td><a href="mailto:<?= $member['email']; ?>"><?= $member['email']; ?></a></td>
                                            <td><?= $member['phone']; ?></td>
                                            <td><?= $member['date_joined']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });
    </script>
</body>
</html>