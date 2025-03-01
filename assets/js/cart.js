let cart = [];

function addToCart(id, name, price) {
    let item = cart.find(i => i.id === id);
    if (item) {
        item.quantity += 1;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    updateCart();
}

function updateCart() {
    let cartItems = document.getElementById("cart-items");
    cartItems.innerHTML = "";
    let total = 0;

    cart.forEach(item => {
        total += item.price * item.quantity;
        cartItems.innerHTML += `<li>${item.name} (x${item.quantity}) - Ksh ${item.price * item.quantity}</li>`;
    });

    document.getElementById("total-price").innerText = "Total: Ksh " + total.toFixed(2);
}

function checkout(orderType) {
    if (cart.length === 0) {
        alert("Your cart is empty!");
        return;
    }
    
    let order = {
        orderType,
        items: cart
    };

    fetch("../pages/order.php", {
        method: "POST",
        body: JSON.stringify(order),
        headers: { "Content-Type": "application/json" }
    }).then(res => res.json())
      .then(data => {
        alert("Order placed successfully!");
        cart = [];
        updateCart();
      }).catch(err => console.error(err));
}
