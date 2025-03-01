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

// Fetch featured items
$featured_query = "SELECT * FROM menu_items WHERE featured = 1 ORDER BY RAND() LIMIT 3";
$featured_result = $conn->query($featured_query);

// Filter by category if set (with proper SQL injection protection)
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$where_clause = "";
if ($category_filter) {
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category = ? ORDER BY name");
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $menu_result = $stmt->get_result();
} else {
    $menu_query = "SELECT * FROM menu_items ORDER BY category, name";
    $menu_result = $conn->query($menu_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <div class="header-content">
                <h1>Our Menu</h1>
                <?php if ($table_number > 0): ?>
                    <div class="table-info">
                        <i class="fas fa-utensils"></i> Table #<?php echo $table_number; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="cart-icon">
                <a href="cart.php" aria-label="View your cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                </a>
            </div>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Search for items...">
                <button id="search-btn"><i class="fas fa-search"></i></button>
            </div>
        </header>

        <!-- Featured Items -->
        <?php if ($featured_result->num_rows > 0): ?>
            <div class="featured-items">
                <h2>Featured Items</h2>
                <div class="featured-grid">
                    <?php while ($item = $featured_result->fetch_assoc()): ?>
                        <div class="featured-item">
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
                </div>
            </div>
        <?php endif; ?>

        <!-- Category Filter -->
        <div class="category-filter">
            <a href="menu.php<?php echo $table_number ? "?table=$table_number" : ""; ?>" 
                class="category-btn <?php echo $category_filter == '' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> All
            </a>
            <?php
            // Defines main categories and their icons
            $main_categories = [
                'Food' => 'fa-utensils',
                'Drinks' => 'fa-glass-martini',
                'Dessert' => 'fa-ice-cream',
                'Brunch' => 'fa-bread-slice',
                'Breakfast' => 'fa-egg',
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
        <div class="menu-items">
            <?php if ($menu_result->num_rows > 0): ?>
                <?php 
                $current_category = '';
                while ($item = $menu_result->fetch_assoc()): 
                    // Display category headers when category changes
                    if ($category_filter == '' && $current_category != $item['category']) {
                        $current_category = $item['category'];
                        echo '<h2 class="category-heading">' . htmlspecialchars($current_category) . '</h2>';
                    }
                ?>
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
                    <a href="menu.php<?php echo $table_number ? "?table=$table_number" : ""; ?>" class="back-btn">
                        Back to All Categories
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="menu-footer">
            <p>Can't decide? Ask our staff for today's special recommendations!</p>
        </footer>
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

        // Search functionality
        document.getElementById('search-btn').addEventListener('click', function() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const menuItems = document.querySelectorAll('.menu-item');

            menuItems.forEach(item => {
                const itemName = item.querySelector('h3').textContent.toLowerCase();
                if (itemName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // load images as they come into view
        document.addEventListener('DOMContentLoaded', function() {
            if ('IntersectionObserver' in window) {
                const imgObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.add('loaded');
                            observer.unobserve(img);
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imgObserver.observe(img);
                });
            }
        });
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
}

.menu-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
.main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-bottom: 2px solid #eee;
}

.main-header h1 {
    font-size: 2.5rem;
    font-weight: 600;
    color: #222;
}

/* Featured Items */
.featured-items {
    margin: 20px 0;
}

.featured-items h2 {
    font-size: 1.8rem;
    color: #222;
    margin-bottom: 15px;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.featured-item {
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.featured-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Search Bar */
.search-bar {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.search-bar input {
    width: 300px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 25px 0 0 25px;
    font-size: 1rem;
}

.search-bar button {
    padding: 10px 20px;
    background-color: #ff4757;
    color: #fff;
    border: none;
    border-radius: 0 25px 25px 0;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.3s ease;
}

.search-bar button:hover {
    background-color: #ff6b81;
}
.table-info {
    font-size: 1.2rem;
    color: #555;
}

.cart-icon {
    position: relative;
}

.cart-icon a {
    color: #333;
    text-decoration: none;
    font-size: 1.5rem;
}

.cart-count {
    position: absolute;
    top: -10px;
    right: -10px;
    background-color: #ff4757;
    color: #fff;
    font-size: 0.8rem;
    padding: 2px 6px;
    border-radius: 50%;
}

/* Category Filter */
.category-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 20px 0;
}

.category-btn {
    padding: 10px 20px;
    background-color: #eee;
    color: #333;
    text-decoration: none;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.category-btn.active,
.category-btn:hover {
    background-color: #ff4757;
    color: #fff;
}

/* Menu Items */
.menu-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.menu-item {
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.menu-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.item-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: opacity 0.3s ease;
}

.item-image img.loaded {
    opacity: 1;
}

.item-details {
    padding: 15px;
}

.item-details h3 {
    font-size: 1.4rem;
    margin: 0 0 10px;
    color: #222;
}

.item-details .description {
    font-size: 0.9rem;
    color: #666;
    margin: 0 0 15px;
}

.price-add {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 1.2rem;
    font-weight: 600;
    color: #ff4757;
}

.add-to-cart-btn {
    background-color: #ff4757;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

.add-to-cart-btn:hover {
    background-color: #ff6b81;
}

.add-to-cart-btn.added {
    background-color: #2ed573;
}

/* No Items Message */
.no-items {
    text-align: center;
    padding: 50px 0;
    color: #666;
}

.no-items i {
    font-size: 2rem;
    margin-bottom: 10px;
}

.back-btn {
    display: inline-block;
    margin-top: 10px;
    padding: 10px 20px;
    background-color: #ff4757;
    color: #fff;
    text-decoration: none;
    border-radius: 25px;
    transition: background-color 0.3s ease;
}

.back-btn:hover {
    background-color: #ff6b81;
}

/* Footer */
.menu-footer {
    text-align: center;
    padding: 20px 0;
    margin-top: 40px;
    border-top: 2px solid #eee;
    color: #666;
    font-size: 0.9rem;
}
<style/>