(function () {
  'use strict';

  /*
   * Cart JavaScript / سكربت صفحة السلة:
   * Task 5: Shows cart items and line totals.
   * Task 6: Lets customer modify/delete/clear cart and open checkout form.
   * Task 7: Creates final order, updates stock, and clears cart.
   * Task 13: Validates shipping/payment fields before checkout.
   * Author / المنفذ: EliteGear Team.
   */

  // Shared app object / نستخدم دوال EliteGear المشتركة من main.js.
  function EG() {
    return window.EliteGear;
  }

  // Cart row template / يبني HTML لكل منتج داخل السلة.
  function cartItemTemplate(item) {
    var eg = EG();
    var price = eg.discountedPrice(item);
    var key = item.cart_key || item.id;
    var options = [
      item.selected_size ? 'Size: ' + item.selected_size : '',
      item.selected_color ? 'Color: ' + item.selected_color : ''
    ].filter(Boolean).join(' | ');
    var image = item.image_url
      ? '<img src="' + eg.escapeHTML(item.image_url) + '" alt="' + eg.escapeHTML(item.name) + '">'
      : '<div class="cart-thumb-placeholder">&#127947;&#65039;</div>';

    return '<div class="cart-item" data-cart-item="' + eg.escapeHTML(key) + '">' +
      '<div class="cart-thumb">' + image + '</div>' +
      '<div class="cart-item-info"><h3>' + eg.escapeHTML(item.name) + '</h3><p>' + eg.escapeHTML(item.category || '') + '</p>' + (options ? '<p>' + eg.escapeHTML(options) + '</p>' : '') + '<strong>' + eg.money(price) + ' SAR</strong></div>' +
      '<div class="qty-controls"><button type="button" data-cart-minus="' + eg.escapeHTML(key) + '">-</button><span>' + Number(item.quantity || 0) + '</span><button type="button" data-cart-plus="' + eg.escapeHTML(key) + '">+</button></div>' +
      '<div class="cart-line-total"><p>' + eg.money(price * Number(item.quantity || 0)) + ' SAR</p><button type="button" data-cart-remove="' + eg.escapeHTML(key) + '">&#128465;</button></div>' +
      '</div>';
  }

  // Render cart / يرسم السلة، الحالة الفارغة، الإجمالي، وزر checkout.
  function renderCart() {
    var eg = EG();
    if (!eg) return;
    var page = document.querySelector('[data-cart-page]');
    if (!page) return;

    var cart = eg.getCart();
    var empty = document.querySelector('[data-cart-empty]');
    var filled = document.querySelector('[data-cart-filled]');
    var list = document.querySelector('[data-cart-items]');
    var checkoutLogin = document.querySelector('[data-checkout-login]');
    var checkoutButton = document.querySelector('[data-show-checkout]');
    var checkoutCard = document.querySelector('[data-checkout-card]');
    var user = eg.getUser();

    if (empty) empty.classList.toggle('is-hidden', cart.length > 0);
    if (filled) filled.classList.toggle('is-hidden', cart.length === 0);
    if (list) list.innerHTML = cart.map(cartItemTemplate).join('');

    var count = cart.reduce(function (sum, item) {
      return sum + Number(item.quantity || 0);
    }, 0);
    var total = eg.cartTotal();
    var itemsNode = document.querySelector('[data-summary-items]');
    var subtotalNode = document.querySelector('[data-summary-subtotal]');
    var totalNode = document.querySelector('[data-summary-total]');
    if (itemsNode) itemsNode.textContent = 'Subtotal (' + count + ' items)';
    if (subtotalNode) subtotalNode.textContent = eg.money(total) + ' SAR';
    if (totalNode) totalNode.textContent = eg.money(total) + ' SAR';

    if (checkoutLogin) checkoutLogin.style.display = user ? 'none' : 'block';
    if (checkoutButton) checkoutButton.style.display = user ? 'inline-flex' : 'none';
    if (!user && checkoutCard) checkoutCard.classList.add('is-hidden');
  }

  // Clear validation errors / يمسح أخطاء الفورم عند الكتابة من جديد.
  function clearCheckoutErrors(form) {
    ['address', 'city', 'phone', 'card_name', 'card_number', 'card_expiry', 'card_cvv'].forEach(function (field) {
      var input = form.querySelector('[name="' + field + '"]');
      var error = form.querySelector('[data-error-for="' + field + '"]');
      if (input) input.classList.remove('error');
      if (error) error.textContent = '';
    });
  }

  // Show validation error / يعرض رسالة خطأ ويعمل shake للحقل المطلوب.
  function setCheckoutError(form, field, message) {
    var input = form.querySelector('[name="' + field + '"]');
    var error = form.querySelector('[data-error-for="' + field + '"]');
    var wrap = form.querySelector('[data-field-wrap="' + field + '"]');
    if (input) input.classList.add('error');
    if (error) error.textContent = message;
    if (wrap) {
      wrap.classList.remove('animate-shake');
      void wrap.offsetWidth;
      wrap.classList.add('animate-shake');
    }
  }

  // Payment helpers / دوال مساعدة لتنظيف وتنسيق بيانات البطاقة.
  function cardDigits(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function formatCardNumber(value) {
    return cardDigits(value).slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
  }

  function formatExpiry(value) {
    var digits = cardDigits(value).slice(0, 4);
    return digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
  }

  // Card validation / Luhn check للتأكد من رقم البطاقة في demo checkout.
  function luhnValid(number) {
    var digits = cardDigits(number);
    var sum = 0;
    var doubleDigit = false;
    for (var i = digits.length - 1; i >= 0; i -= 1) {
      var digit = Number(digits.charAt(i));
      if (doubleDigit) {
        digit *= 2;
        if (digit > 9) digit -= 9;
      }
      sum += digit;
      doubleDigit = !doubleDigit;
    }
    return digits.length >= 13 && digits.length <= 19 && sum % 10 === 0;
  }

  function cardBrand(number) {
    var digits = cardDigits(number);
    if (/^4/.test(digits)) return 'Visa';
    if (/^(5[1-5]|2[2-7])/.test(digits)) return 'Mastercard';
    if (/^3[47]/.test(digits)) return 'American Express';
    if (/^6(?:011|5)/.test(digits)) return 'Discover';
    if (/^mada/i.test(digits)) return 'Mada';
    return 'Card';
  }

  function expiryValid(value) {
    var match = String(value || '').match(/^(\d{2})\/(\d{2})$/);
    if (!match) return false;
    var month = Number(match[1]);
    var year = 2000 + Number(match[2]);
    if (month < 1 || month > 12) return false;
    var now = new Date();
    var expiry = new Date(year, month, 0, 23, 59, 59);
    return expiry >= new Date(now.getFullYear(), now.getMonth(), 1);
  }

  // Task 6 + 7 + 13 / Main cart controller: buttons, checkout validation, and order creation.
  function setupCartPage() {
    var eg = EG();
    if (!eg || !document.querySelector('[data-cart-page]')) return;

    document.addEventListener('click', function (event) {
      var minus = event.target.closest('[data-cart-minus]');
      var plus = event.target.closest('[data-cart-plus]');
      var remove = event.target.closest('[data-cart-remove]');
      var clear = event.target.closest('[data-clear-cart]');
      var showCheckout = event.target.closest('[data-show-checkout]');

      if (minus) {
        var minusItem = eg.getCart().find(function (item) { return (item.cart_key || item.id) === minus.dataset.cartMinus; });
        if (minusItem) eg.updateCartQuantity(minusItem.cart_key || minusItem.id, Number(minusItem.quantity || 0) - 1);
        renderCart();
      }

      if (plus) {
        var plusItem = eg.getCart().find(function (item) { return (item.cart_key || item.id) === plus.dataset.cartPlus; });
        if (!plusItem) return;
        if (Number(plusItem.quantity || 0) >= Number(plusItem.stock || 0)) {
          eg.toast('No more stock available', 'error');
          return;
        }
        eg.updateCartQuantity(plusItem.cart_key || plusItem.id, Number(plusItem.quantity || 0) + 1);
        renderCart();
      }

      if (remove) {
        eg.removeFromCart(remove.dataset.cartRemove);
        renderCart();
      }

      if (clear) {
        eg.clearCart();
        renderCart();
      }

      if (showCheckout) {
        var card = document.querySelector('[data-checkout-card]');
        if (!card) return;
        card.classList.toggle('is-hidden');
        showCheckout.innerHTML = card.classList.contains('is-hidden')
          ? '<svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 13H7L6 8Z"/><path d="M9 8a3 3 0 1 1 6 0"/></svg> Proceed to Checkout'
          : '<svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 13H7L6 8Z"/><path d="M9 8a3 3 0 1 1 6 0"/></svg> Hide Checkout';
      }
    });

    var form = document.querySelector('[data-checkout-form]');
    if (form) {
      var cardNumber = form.querySelector('[data-card-number]');
      var cardExpiry = form.querySelector('[data-card-expiry]');
      var cardCvv = form.querySelector('[data-card-cvv]');

      if (cardNumber) {
        cardNumber.addEventListener('input', function () {
          cardNumber.value = formatCardNumber(cardNumber.value);
        });
      }

      if (cardExpiry) {
        cardExpiry.addEventListener('input', function () {
          cardExpiry.value = formatExpiry(cardExpiry.value);
        });
      }

      if (cardCvv) {
        cardCvv.addEventListener('input', function () {
          cardCvv.value = cardDigits(cardCvv.value).slice(0, 4);
        });
      }

      form.addEventListener('input', function () {
        clearCheckoutErrors(form);
      });

      // Place order / عند submit: نتحقق من البيانات ثم ننشئ order وننقص stock.
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var user = eg.getUser();
        if (!user) {
          eg.toast('Please login to checkout.', 'error');
          window.location.href = 'admin.php';
          return;
        }

        var values = Object.fromEntries(new FormData(form).entries());
        clearCheckoutErrors(form);
        var invalid = false;
        if (!values.address || !values.address.trim()) {
          setCheckoutError(form, 'address', 'Shipping address is required.');
          invalid = true;
        }
        if (!values.city || !values.city.trim()) {
          setCheckoutError(form, 'city', 'City is required.');
          invalid = true;
        }
        if (!values.phone || !values.phone.trim()) {
          setCheckoutError(form, 'phone', 'Phone number is required.');
          invalid = true;
        } else if (!/^[0-9+\- ]{7,15}$/.test(values.phone)) {
          setCheckoutError(form, 'phone', 'Enter a valid phone number.');
          invalid = true;
        }
        if (!values.card_name || !values.card_name.trim()) {
          setCheckoutError(form, 'card_name', 'Cardholder name is required.');
          invalid = true;
        }
        if (!values.card_number || !luhnValid(values.card_number)) {
          setCheckoutError(form, 'card_number', 'Enter a valid card number.');
          invalid = true;
        }
        if (!values.card_expiry || !expiryValid(values.card_expiry)) {
          setCheckoutError(form, 'card_expiry', 'Enter a valid future expiry date.');
          invalid = true;
        }
        if (!values.card_cvv || !/^\d{3,4}$/.test(cardDigits(values.card_cvv))) {
          setCheckoutError(form, 'card_cvv', 'Enter a valid CVV.');
          invalid = true;
        }
        if (invalid) return;

        var cart = eg.getCart();
        if (!cart.length) return;
        var orderTotal = eg.cartTotal();
        var items = cart.map(function (item) {
          return {
            id: item.id,
            name: item.name,
            quantity: Number(item.quantity || 0),
            price: eg.discountedPrice(item),
            selected_size: item.selected_size || '',
            selected_color: item.selected_color || ''
          };
        });
        var order = {
          id: 'ord_' + Date.now(),
          created_date: new Date().toISOString(),
          user_email: user.email,
          user_name: user.full_name || user.user_name || user.email,
          total_amount: orderTotal,
          status: 'confirmed',
          payment_status: 'paid',
          payment_method: 'card',
          payment_snapshot: JSON.stringify({
            cardholder: values.card_name.trim(),
            brand: cardBrand(values.card_number),
            last4: cardDigits(values.card_number).slice(-4),
            expiry: values.card_expiry
          }),
          shipping_address: values.address.trim(),
          city: values.city.trim(),
          phone: values.phone.trim(),
          notes: values.notes || '',
          items_snapshot: JSON.stringify(items)
        };

        var orders = eg.getOrders();
        orders.unshift(order);
        eg.saveOrders(orders);

        // ── UPDATE STOCK ────────────────────────────────────────────────────
        var products = eg.getProducts().map(function (product) {
          var item = cart.find(function (cartItem) { return cartItem.id === product.id; });
          if (!item) return product;
          return Object.assign({}, product, { stock: Math.max(0, Number(product.stock || 0) - Number(item.quantity || 0)) });
        });
        eg.saveProducts(products);

        // ── UPDATE CUSTOMER: total_spent, total_orders, address, city, phone ─
        var customers = eg.getCustomers();
        var custIdx = customers.findIndex(function (c) {
          return String(c.email).toLowerCase() === String(user.email).toLowerCase();
        });
        if (custIdx !== -1) {
          var cust = customers[custIdx];
          customers[custIdx] = Object.assign({}, cust, {
            phone:        values.phone.trim(),
            address:      values.address.trim(),
            city:         values.city.trim(),
            total_orders: Number(cust.total_orders || 0) + 1,
            total_spent:  Math.round((Number(cust.total_spent || 0) + orderTotal) * 100) / 100
          });
          eg.saveCustomers(customers);
        }
        // ────────────────────────────────────────────────────────────────────

        eg.clearCart();

        // --- COOKIE: store last order id and customer email for past purchases ---
        var cookieExpiry = new Date();
        cookieExpiry.setFullYear(cookieExpiry.getFullYear() + 1);
        document.cookie = 'eg_last_order=' + encodeURIComponent(order.id) + '; path=/; expires=' + cookieExpiry.toUTCString();
        document.cookie = 'eg_customer_email=' + encodeURIComponent(user.email) + '; path=/; expires=' + cookieExpiry.toUTCString();
        // -------------------------------------------------------------------------

        eg.toast('Order placed successfully!');
        window.location.href = 'order-tracking.php?id=' + encodeURIComponent(order.id);
      });
    }

    renderCart();
  }

  document.addEventListener('DOMContentLoaded', setupCartPage);
  window.addEventListener('elitegear:cart', renderCart);
  window.addEventListener('elitegear:auth', renderCart);
})();
