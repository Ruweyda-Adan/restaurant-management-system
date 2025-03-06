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

if (!$featured_result) {
    die("Error fetching featured items: " . $conn->error);
}

// Debug: Check the number of featured items
echo "Number of featured items: " . $featured_result->num_rows;

// Filter by category 
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
        <div class="featured-items">
    <h2>Featured Items</h2>
    <?php if ($featured_result->num_rows > 0): ?>
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
    <?php else: ?>
        <p class="no-featured">No featured items available at the moment. Check back later!</p>
    <?php endif; ?>
</div>
            

        <!-- Category Filter -->
        <div class="category-filter">
            <a href="menu.php<?php echo $table_number ? "?table=$table_number" : ""; ?>" 
                class="category-btn <?php echo $category_filter == '' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> All
            </a>
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
        <div class="menu-items">
            <?php if ($menu_result && $menu_result->num_rows > 0): ?>
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
    padding: 15px 0;
    gap: 15px;
}

.header-content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.main-header h1 {
    font-size: 2.2rem;
    margin: 0 0 10px 0;
    line-height: 1.2;
}

.table-info {
    font-size: 1rem;
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

/* Search Bar Responsiveness */
.search-bar {
    width: 100%;
    display: flex;
    margin: 15px 0;
}

.search-bar input {
    flex-grow: 1;
    padding: 10px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 25px 0 0 25px;
}

.search-bar button {
    padding: 10px 20px;
    background-color: #ff4757;
    color: #fff;
    border: none;
    border-radius: 0 25px 25px 0;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.search-bar button:hover {
    background-color: #ff6b81;
}

/* Featured Items Responsiveness */
.featured-items h2 {
    font-size: 1.6rem;
    margin-bottom: 15px;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.featured-item {
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.featured-item:hover {
    transform: translateY(-5px);
}

.item-image img {
    width: 100%; 
    height: auto; 
    max-height: 150px; 
    object-fit: cover; 
    border-radius: 8px; 
}

.menu-item img {
    transform: none !important; 
}

/* Category Filter Responsiveness */
.category-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 20px 0;
    justify-content: center;
}

.category-btn {
    padding: 10px 15px;
    background-color: #eee;
    color: #333;
    text-decoration: none;
    border-radius: 25px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.category-btn i {
    margin-right: 5px;
}

.category-btn.active,
.category-btn:hover {
    background-color: #ff4757;
    color: #fff;
}

/* Menu Items Responsiveness */
.menu-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
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
    padding: 15px;
}

.item-details h3 {
    font-size: 1.2rem;
    margin: 0 0 10px;
}

.price-add {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 1.1rem;
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
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.3s ease;
}

.add-to-cart-btn:hover {
    background-color: #ff6b81;
}

/* Tablet Responsiveness (768px - 1024px) */
@media screen and (max-width: 1024px) {
    .menu-container {
        width: 98%;
        padding: 10px;
    }

    .main-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-content {
        width: 100%;
    }

    .cart-icon {
        position: absolute;
        top: 15px;
        right: 15px;
    }

    .featured-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }

    .category-filter {
        justify-content: flex-start;
        overflow-x: auto;
        padding-bottom: 10px;
    }

    .category-btn {
        flex-shrink: 0;
    }
}

/* Mobile Responsiveness (up to 767px) */
@media screen and (max-width: 767px) {
    .menu-container {
        width: 100%;
        padding: 5px;
    }

    .main-header h1 {
        font-size: 1.8rem;
    }

    .search-bar input {
        font-size: 0.9rem;
    }

    .featured-grid {
        grid-template-columns: 1fr;
    }

    .category-filter {
        flex-wrap: nowrap;
        overflow-x: scroll;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .category-filter::-webkit-scrollbar {
        display: none;
    }

    .category-btn {
        font-size: 0.9rem;
        padding: 8px 12px;
    }

    .menu-items {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .item-details h3 {
        font-size: 1rem;
    }

    .price {
        font-size: 1rem;
    }

    .add-to-cart-btn {
        font-size: 0.9rem;
        padding: 8px 12px;
    }
}

/* Small Mobile Devices (up to 480px) */
@media screen and (max-width: 480px) {
    .main-header h1 {
        font-size: 1.5rem;
    }

    .search-bar input {
        font-size: 0.8rem;
    }

    .category-btn {
        font-size: 0.8rem;
        padding: 6px 10px;
    }

    .item-details h3 {
        font-size: 0.9rem;
    }

    .price {
        font-size: 0.9rem;
    }

    .add-to-cart-btn {
        font-size: 0.8rem;
        padding: 6px 10px;
    }
}
/* Base Styles (Mobile-First) */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f9f9f9;
    color: #333;
    margin: 0;
    padding: 0;
    line-height: 1.5;
    font-size: 14px;
}

.menu-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 10px;
    box-sizing: border-box;
}

/* Header */
.main-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    gap: 8px;
}

.main-header h1 {
    font-size: 1.4rem;
    margin: 0;
}

.table-info {
    font-size: 0.8rem;
    color: #555;
}

.cart-icon {
    position: relative;
}

.cart-icon a {
    color: #333;
    text-decoration: none;
    font-size: 1.2rem;
}

.cart-count {
    background-color: #ff4757;
    color: #fff;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 50%;
    margin-left: 5px;
}

/* Search Bar */
.search-bar {
    width: 100%;
    display: flex;
    margin: 8px 0;
}

.search-bar input {
    flex-grow: 1;
    padding: 8px;
    font-size: 0.9rem;
    border: 1px solid #ddd;
    border-radius: 25px 0 0 25px;
    box-sizing: border-box;
}

.search-bar button {
    padding: 8px 15px;
    background-color: #ff4757;
    color: #fff;
    border: none;
    border-radius: 0 25px 25px 0;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.search-bar button:hover {
    background-color: #ff6b81;
}

/* Featured Items */
.featured-items h2 {
    font-size: 1.3rem;
    margin-bottom: 8px;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr); 
    gap: 10px;
}

.featured-item {
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.item-image img {
    width: 100%;
    height: 120px; 
    object-fit: cover;
}

.item-details {
    padding: 8px;
}

.item-details h3 {
    font-size: 0.9rem;
    margin: 0 0 4px;
}

.item-details .description {
    font-size: 0.75rem;
    color: #666;
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
    font-size: 0.75rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.add-to-cart-btn:hover {
    background-color: #ff6b81;
}

/* Category Filter */
.category-filter {
    display: flex;
    gap: 6px;
    margin: 10px 0;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 5px;
}

.category-filter::-webkit-scrollbar {
    display: none;
}

.category-btn {
    padding: 6px 10px;
    background-color: #eee;
    color: #333;
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.75rem;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.category-btn.active,
.category-btn:hover {
    background-color: #ff4757;
    color: #fff;
}

/* Menu Items */
.menu-items {
    display: grid;
    grid-template-columns: repeat(2, 1fr); 
    gap: 10px;
}

.menu-item {
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.menu-item .item-image img {
    width: 100%;
    height: 120px; 
    object-fit: cover;
}

/* Footer */
.menu-footer {
    text-align: center;
    padding: 10px 0;
    font-size: 0.75rem;
    border-top: 1px solid #eee;
}

/* Media Queries (Tablet and Desktop) */
@media (min-width: 768px) {
    .main-header h1 {
        font-size: 1.8rem;
    }

    .featured-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }

    .menu-items {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (min-width: 1024px) {
    .menu-container {
        padding: 15px;
    }

    .featured-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }

    .menu-items {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}
<style/>