<?php
session_start();
require_once('../includes/db_connect.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

// Initialize message variables
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add or Edit menu item
    if (isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'edit')) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = floatval($_POST['price']);
        $category = $conn->real_escape_string($_POST['category']);
        
        // Handle image upload
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $target_dir = "../assets/images/";
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $image_name = 'menu_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $image_name;
            
            // Check if file is an actual image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                // Try to upload file
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Image uploaded successfully
                } else {
                    $error_message = "Sorry, there was an error uploading your file.";
                    $image_name = '';
                }
            } else {
                $error_message = "File is not an image.";
                $image_name = '';
            }
        }
        
        if ($_POST['action'] === 'add') {
            // Insert new menu item
            $sql = "INSERT INTO menu_items (name, description, price, category, image) 
                    VALUES ('$name', '$description', $price, '$category', '$image_name')";
            
            if ($conn->query($sql) === TRUE) {
                $success_message = "Menu item added successfully!";
            } else {
                $error_message = "Error: " . $conn->error;
            }
        } else {
            // Update existing menu item
            if ($image_name) {
                $sql = "UPDATE menu_items SET 
                        name = '$name', 
                        description = '$description', 
                        price = $price, 
                        category = '$category', 
                        image = '$image_name' 
                        WHERE id = $id";
            } else {
                $sql = "UPDATE menu_items SET 
                        name = '$name', 
                        description = '$description', 
                        price = $price, 
                        category = '$category' 
                        WHERE id = $id";
            }
            
            if ($conn->query($sql) === TRUE) {
                $success_message = "Menu item updated successfully!";
            } else {
                $error_message = "Error: " . $conn->error;
            }
        }
    }
    
    // Delete menu item
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        
        // First, check if menu item exists and get image filename
        $check_sql = "SELECT image FROM menu_items WHERE id = $id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $item = $check_result->fetch_assoc();
            
            // Delete image file if it exists
            if (!empty($item['image'])) {
                $image_path = "../assets/images/" . $item['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete menu item from database
            $sql = "DELETE FROM menu_items WHERE id = $id";
            if ($conn->query($sql) === TRUE) {
                $success_message = "Menu item deleted successfully!";
            } else {
                $error_message = "Error: " . $conn->error;
            }
        } else {
            $error_message = "Menu item not found.";
        }
    }
}

// Check if editing an item
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM menu_items WHERE id = $edit_id";
    $edit_result = $conn->query($edit_sql);
    
    if ($edit_result->num_rows > 0) {
        $edit_item = $edit_result->fetch_assoc();
    }
}

// Fetch all menu items
$sql = "SELECT * FROM menu_items ORDER BY category, name";
$result = $conn->query($sql);

// Get distinct categories
$categories_sql = "SELECT DISTINCT category FROM menu_items ORDER BY category";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - Restaurant Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>Restaurant Admin</h2>
            </div>
            <nav class="admin-nav">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_menu.php" class="active"><i class="fas fa-utensils"></i> Manage Menu</a>
                <a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a>
                <a href="#" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-content">
            <header class="admin-header">
                <h1>Manage Menu</h1>
                <button id="add-item-btn" class="primary-btn">
                    <i class="fas fa-plus"></i> Add Menu Item
                </button>
            </header>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div id="menu-form-container" class="<?php echo $edit_item ? 'show' : ''; ?>">
                <div class="menu-form">
                    <h2><?php echo $edit_item ? 'Edit Menu Item' : 'Add New Menu Item'; ?></h2>
                    <form method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $edit_item ? 'edit' : 'add'; ?>">
                        <?php if ($edit_item): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Item Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo $edit_item ? $edit_item['name'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="3"><?php echo $edit_item ? $edit_item['description'] : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (KSh):</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_item ? $edit_item['price'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <input type="text" id="category" name="category" list="categories" value="<?php echo $edit_item ? $edit_item['category'] : ''; ?>" required>
                            <datalist id="categories">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category; ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image:</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <?php if ($edit_item && !empty($edit_item['image'])): ?>
                                <div class="current-image">
                                    <p>Current image: <?php echo $edit_item['image']; ?></p>
                                    <?php if (file_exists("../assets/images/{$edit_item['image']}")): ?>
                                        <img src="../assets/images/<?php echo $edit_item['image']; ?>" alt="Current image" height="100">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="primary-btn">
                                <?php echo $edit_item ? 'Update Item' : 'Add Item'; ?>
                            </button>
                            <button type="button" id="cancel-btn" class="secondary-btn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="menu-items-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="menu-image-cell">
                                        <?php if (!empty($row['image']) && file_exists("../assets/images/{$row['image']}")): ?>
                                            <img src="../assets/images/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                                        <?php else: ?>
                                            <div class="no-image">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td class="description-cell"><?php echo $row['description']; ?></td>
                                    <td>KSh <?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $row['id']; ?>" class="action-link edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="action-link delete" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-items">No menu items found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this menu item? This action cannot be undone.</p>
            <form method="post" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete-id" name="id" value="">
                <div class="modal-actions">
                    <button type="submit" class="danger-btn">Delete</button>
                    <button type="button" id="cancel-delete" class="secondary-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide add form
        document.getElementById('add-item-btn').addEventListener('click', function() {
            document.getElementById('menu-form-container').classList.add('show');
        });
        
        document.getElementById('cancel-btn').addEventListener('click', function() {
            document.getElementById('menu-form-container').classList.remove('show');
            window.location.href = 'manage_menu.php'; // Remove edit parameter
        });
        
        // Delete confirmation
        document.querySelectorAll('.delete').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                document.getElementById('delete-id').value = id;
                document.getElementById('delete-modal').classList.add('show');
            });
        });
        
        document.getElementById('cancel-delete').addEventListener('click', function() {
            document.getElementById('delete-modal').classList.remove('show');
        });
        
        // Logout functionality
        document.getElementById('logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                fetch('dashboard.php?logout=1')
                    .then(() => {
                        window.location.href = 'dashboard.php';
                    });
            }
        });
    </script>
</body>
</html>
<style>
    /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f7f6;
}

.admin-container {
    display: flex;
    min-height: 100vh;
}

.admin-sidebar {
    width: 250px;
    background-color: #2c3e50;
    color: #fff;
    padding: 20px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.admin-logo {
    text-align: center;
    margin-bottom: 20px;
}

.admin-logo h2 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.admin-nav {
    display: flex;
    flex-direction: column;
}

.admin-nav a {
    color: #fff;
    text-decoration: none;
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.admin-nav a:hover {
    background-color: #34495e;
}

.admin-nav a.active {
    background-color: #34495e;
}

.admin-content {
    flex-grow: 1;
    padding: 20px;
    background-color: #fff;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.admin-header h1 {
    margin: 0;
    font-size: 28px;
    color: #2c3e50;
}

.primary-btn {
    background: #3498db;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.primary-btn:hover {
    background: #2980b9;
}

.secondary-btn {
    background: #7f8c8d;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.secondary-btn:hover {
    background: #666;
}

.danger-btn {
    background: #e74c3c;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.danger-btn:hover {
    background: #c0392b;
}

.alert {
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert.success {
    background: #d4edda;
    color: #155724;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
}

/* Menu Form */
#menu-form-container {
    display: none;
    margin-bottom: 20px;
}

#menu-form-container.show {
    display: block;
}

.menu-form {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.menu-form h2 {
    margin: 0 0 20px;
    font-size: 22px;
    color: #2c3e50;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
    color: #2c3e50;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #3498db;
    outline: none;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.current-image {
    margin-top: 10px;
}

.current-image img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

/* Menu Items Table */
.menu-items-container {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.admin-table th,
.admin-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.admin-table th {
    background-color: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.menu-image-cell img {
    max-width: 100px;
    height: auto;
    border-radius: 5px;
}

.no-image {
    background: #f4f7f6;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    color: #7f8c8d;
}

.description-cell {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.action-link {
    color: #3498db;
    text-decoration: none;
    font-size: 18px;
    margin-right: 10px;
    transition: color 0.3s;
}

.action-link.edit:hover {
    color: #2980b9;
}

.action-link.delete {
    color: #e74c3c;
}

.action-link.delete:hover {
    color: #c0392b;
}

.no-items {
    text-align: center;
    color: #7f8c8d;
    padding: 20px;
}

/* Delete Confirmation Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    width: 100%;
}

.modal-content h2 {
    margin: 0 0 20px;
    font-size: 22px;
    color: #2c3e50;
}

.modal-content p {
    margin: 0 0 20px;
    font-size: 14px;
    color: #7f8c8d;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
<style/>