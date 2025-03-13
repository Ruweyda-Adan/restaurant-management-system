<?php
session_start();
require_once('../includes/db_connect.php');

// Initialize cart 
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get table number from URL 
$table_number = isset($_GET['table']) ? intval($_GET['table']) : 0;
$_SESSION['table_number'] = $table_number;

// Fetch menu categories
$categories_query = "SELECT DISTINCT category FROM menu_items ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Filter by category 
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$where_clause = "";
if ($category_filter) {
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category = ? ORDER BY name");
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $menu_result = $stmt->get_result();
} else {
    // No category selected 
    $menu_result = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Delicious menu offerings at our restaurant">
    <title>Restaurant Menu | Enjoy Our Delicious Selections</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="menu-container">
        <header class="main-header">
            <!-- Welcome Message at the Top -->
            <div class="welcome-message">
                <i class="fas fa-utensils welcome-icon"></i>
                <h2>Welcome to Our Menu</h2>
                <p>Please select a category to view our delicious offerings.</p>
            </div>
            <?php if ($table_number > 0): ?>
                <div class="table-info">
                    <i class="fas fa-utensils"></i> Table #<?php echo $table_number; ?>
                </div>
            <?php endif; ?>
            
            <div class="cart-icon">
                <a href="cart.php" aria-label="View your cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                </a>
            </div>
        </header>

        <!-- Category Filter -->
        <div class="category-filter">
            <?php
            // Defines main categories and their icons
            $main_categories = [
                'Drinks' => 'fa-glass-martini',
                'Dessert' => 'fa-ice-cream',
                'Brunch' => 'fa-bread-slice',
                'Breakfast' => 'fa-egg',
                'Lunch' => 'fa-hamburger'  
            ];

            // Displays only main categories
            foreach ($main_categories as $category => $icon): ?>
                <a href="menu.php?<?php echo $table_number ? "table=$table_number&" : ""; ?>category=<?php echo urlencode($category); ?>" 
                    class="category-btn <?php echo $category_filter == $category ? 'active' : ''; ?>">
                    <i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($category); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Menu Items -->
        <?php if (!empty($category_filter)): ?>
            <div class="menu-items">
                <?php if ($menu_result && $menu_result->num_rows > 0): ?>
                    <?php while ($item = $menu_result->fetch_assoc()): ?>
                        <div class="menu-item" data-id="<?php echo $item['id']; ?>">
                            <div class="item-image">
                                <?php if (!empty($item['image']) && file_exists("../assets/images/{$item['image']}")): ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="../assets/images/placeholder.jpg" alt="No image available" loading="lazy">
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="price-add">
                                    <span class="price">KSh <?php echo number_format($item['price'], 2); ?></span>
                                    <button class="add-to-cart-btn" data-id="<?php echo $item['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                        data-price="<?php echo $item['price']; ?>">
                                        <i class="fas fa-plus-circle"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-items">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>No menu items available in this category.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Blank landing page -->
            <div class="blank-landing">
                
            
            </div>
        <?php endif; ?>
        
        
    </div>

    <script src="../script.js"></script>
    <script>
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const price = this.getAttribute('data-price');
                
                addToCart(id, name, price);
                
                // Update cart count
                const cartCount = document.querySelector('.cart-count');
                cartCount.textContent = parseInt(cartCount.textContent) + 1;

                // Shows feedback with animation
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Added!';
                this.classList.add('added');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('added');
                }, 1000);
            });
        });

        function addToCart(id, name, price) {
            // Send AJAX request to add item to session cart
            fetch('../pages/order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&item_id=${encodeURIComponent(id)}&item_name=${encodeURIComponent(name)}&item_price=${encodeURIComponent(price)}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Item added to cart');
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
            });
        }
    </script>
</body>
</html>

<style>
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f9f9f9;
    color: #333;
    margin: 0;
    padding: 0;
    line-height: 1.6;
}

.menu-container {
    width: 95%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 10px;
}

/* Header Responsiveness */
.main-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    gap: 15px;
    margin-bottom: 10px;
}

.header-content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.table-info {
    font-size: 0.9rem;
    color: #555;
}

.cart-icon {
    position: relative;
    margin-left: auto;
}

.cart-icon a {
    color: #333;
    text-decoration: none;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
}

.cart-count {
    background-color: #ff4757;
    color: #fff;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 50%;
    margin-left: 5px;
}

/* Welcome Message Styling  */
.welcome-message {
    text-align: center;
    padding: 10px 0;
    margin-bottom: 10px;
    width: 100%;
}

.welcome-icon {
    font-size: 1.5rem;
    color: #ff4757;
    margin-bottom: 5px;
}

.welcome-message h2 {
    font-size: 1.2rem;
    margin: 0 0 5px 0;
    color: #333;
}

.welcome-message p {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}

/* Category Filter  */
.category-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 10px 0;
    justify-content: center;
}

.category-btn {
    padding: 8px 12px;
    background-color: #eee;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.category-btn i {
    font-size: 1rem;
}

.category-btn.active,
.category-btn:hover {
    background-color: #ff4757;
    color: #fff;
    transform: translateY(-3px);
}

/* Blank Landing Page */
.blank-landing {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding: 20px;
}

.landing-hint {
    text-align: center;
    color: #999;
    max-width: 600px;
}

.landing-hint i {
    font-size: 2rem;
    margin-bottom: 15px;
    animation: float 2s ease-in-out infinite;
}

.landing-hint p {
    font-size: 1.1rem;
    font-weight: 300;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Menu Items Responsiveness */
.menu-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.menu-item {
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.menu-item:hover {
    transform: translateY(-5px);
}

.item-details {
    padding: 10px;
}

.item-details h3 {
    font-size: 0.9rem;
    margin: 0 0 8px;
}

.price-add {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 0.9rem;
    font-weight: 600;
    color: #ff4757;
}

.add-to-cart-btn {
    background-color: #ff4757;
    color: #fff;
    border: none;
    padding: 6px 10px;
    border-radius: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.3s ease;
}

.add-to-cart-btn:hover {
    background-color: #ff6b81;
}

.add-to-cart-btn.added {
    background-color: #28a745;
}

.no-items {
    text-align: center;
    padding: 30px 15px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    grid-column: 1 / -1;
}

.no-items i {
    font-size: 1.5rem;
    color: #ff4757;
    margin-bottom: 10px;
}

.no-items p {
    font-size: 1rem;
    color: #666;
    margin-bottom: 10px;
}

.item-image img {
    width: 100%; 
    height: 120px; 
    object-fit: cover; 
}

/* Footer */
.menu-footer {
    text-align: center;
    padding: 15px 0;
    margin-top: 20px;
    border-top: 1px solid #eee;
    color: #666;
}

/* Mobile Responsiveness (up to 767px) */
@media screen and (max-width: 767px) {
    .menu-items {
        grid-template-columns: repeat(2, 1fr); 
    }

    .item-details h3 {
        font-size: 0.8rem;
    }

    .price {
        font-size: 0.8rem;
    }

    .add-to-cart-btn {
        font-size: 0.7rem;
        padding: 5px 8px;
    }
}
</style>