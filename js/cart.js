(function () {
    'use strict';

    const storageKey = 'plantora_cart';

    // Check if user just logged out, clear cart immediately
    if (window.location.search.indexOf('logged_out=1') !== -1) {
        try {
            localStorage.removeItem(storageKey);
        } catch (e) {}
    }

    function readCart() {
        try {
            const cart = JSON.parse(localStorage.getItem(storageKey));
            return Array.isArray(cart) ? cart : [];
        } catch (error) {
            return [];
        }
    }

    function saveCart(cart, syncDb = true) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(cart));
        } catch (e) {}

        if (syncDb) {
            syncCartWithServer(cart);
        }
    }

    function updateCartCounters() {
        const totalItems = readCart().reduce(function (total, item) {
            return total + (parseInt(item.quantity, 10) || 1);
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

    function syncCartWithServer(cart) {
        // Send cart state to server for logged-in user
        fetch('cart-sync.php?action=sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ items: cart })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.logged_in && Array.isArray(data.cart)) {
                // Keep local updated if server adjusted quantities
            }
        })
        .catch(function() {
            // Offline or guest, local storage remains authoritative
        });
    }

    function initializeCartSession() {
        // Query server to check if user is logged in and sync database cart
        fetch('cart-sync.php?action=get', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.logged_in) {
                const localCart = readCart();
                if (Array.isArray(data.cart)) {
                    // Load user's database cart into client
                    localStorage.setItem(storageKey, JSON.stringify(data.cart));
                    updateCartCounters();
                    if (typeof window.renderCartPage === 'function') {
                        window.renderCartPage();
                    }
                }
            }
        })
        .catch(function() {});
    }

    function bindAddToCartButtons() {
        document.querySelectorAll('.shop-add-cart[data-product]').forEach(function (button) {
            if (button.dataset.bound) return;
            button.dataset.bound = 'true';
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                
                if (button.disabled) return;
                
                try {
                    addToCart(JSON.parse(button.dataset.product));
                    button.disabled = true;
                    setTimeout(function() { button.disabled = false; }, 1000);
                } catch (err) {
                    console.error('Invalid product dataset:', err);
                }
            });
        });
    }

    window.readCart = readCart;
    window.saveCart = saveCart;
    window.updateCartCounters = updateCartCounters;
    window.showToast = showToast;

    window.addToCart = function (product) {
        if (!product || !product.variation_id) {
            return;
        }

        const cart = readCart();
        const targetVariationId = Number(product.variation_id);
        const existingItem = cart.find(function (item) {
            return Number(item.variation_id) === targetVariationId;
        });

        const addQty = Math.max(1, parseInt(product.quantity, 10) || 1);
        const itemSize = product.size || product.selected_size || '';
        const itemColor = product.color || '';
        const itemCategory = product.category || '';

        if (existingItem) {
            existingItem.quantity += addQty;
            if (itemSize && !existingItem.size) {
                existingItem.size = itemSize;
            }
            if (itemColor && !existingItem.color) {
                existingItem.color = itemColor;
            }
            if (product.image && !existingItem.image) {
                existingItem.image = product.image;
            }
        } else {
            cart.push({
                product_id: Number(product.product_id),
                variation_id: targetVariationId,
                product_name: product.product_name || '',
                category: itemCategory,
                color: itemColor,
                size: itemSize,
                price: Number(product.price) || 0,
                quantity: addQty,
                image: product.image || ''
            });
        }

        saveCart(cart, true);
        updateCartCounters();
        const colorLabel = itemColor ? ' - ' + itemColor : '';
        const sizeLabel = itemSize ? ' - ' + itemSize : '';
        showToast((product.product_name || 'Item') + colorLabel + sizeLabel + ' added to cart');
    };

    document.addEventListener('DOMContentLoaded', function () {
        updateCartCounters();
        bindAddToCartButtons();
        initializeCartSession();
    });
})();
