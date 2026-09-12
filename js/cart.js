(function () {
    'use strict';

    const storageKey = 'plantora_cart';

    function readCart() {
        try {
            const cart = JSON.parse(localStorage.getItem(storageKey));
            return Array.isArray(cart) ? cart : [];
        } catch (error) {
            return [];
        }
    }

    function saveCart(cart) {
        localStorage.setItem(storageKey, JSON.stringify(cart));
    }

    function updateCartCounters() {
        const totalItems = readCart().reduce(function (total, item) {
            return total + item.quantity;
        }, 0);

        document.querySelectorAll('.cart-count').forEach(function (counter) {
            counter.textContent = totalItems;
        });
    }

    function showToast(message) {
        let toast = document.getElementById('cart-toast');

        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cart-toast';
            toast.className = 'cart-toast';
            toast.setAttribute('role', 'status');
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toast.hideTimer);
        toast.hideTimer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2400);
    }

    function bindAddToCartButtons() {
        document.querySelectorAll('.shop-add-cart[data-product]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                addToCart(JSON.parse(button.dataset.product));
            });
        });
    }

    window.addToCart = function (product) {
        const cart = readCart();
        const existingItem = cart.find(function (item) {
            return item.variation_id === product.variation_id;
        });

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                product_id: product.product_id,
                variation_id: product.variation_id,
                product_name: product.product_name,
                price: product.price,
                quantity: 1
            });
        }

        saveCart(cart);
        updateCartCounters();
        showToast('Item added to cart');
    };

    document.addEventListener('DOMContentLoaded', function () {
        updateCartCounters();
        bindAddToCartButtons();
    });
})();
