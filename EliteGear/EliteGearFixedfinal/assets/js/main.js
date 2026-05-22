(function () {
  'use strict';

  /*
   * Main JavaScript / الملف الرئيسي للتفاعلات:
   * Task 3: Product filters, sorting, and product cards data.
   * Task 4 + 5: Product details quantity/size validation and add to cart.
   * Task 12: Cookies/session/localStorage login and past purchases.
   * Task 13: Register/contact form validation.
   * Task 14: Product Help popup open/close behavior.
   * Author / المنفذ: EliteGear Team.
   */

  // Storage keys / مفاتيح localStorage: نستخدمها لحفظ cart/user/data داخل المتصفح.
  var STORAGE = {
    products: 'elitegear_products',
    orders: 'elitegear_orders',
    cart: 'elitegear_cart',
    user: 'elitegear_user',
    customers: 'elitegear_customers',
    admins: 'elitegear_admins',
    messages: 'elitegear_contact_messages'
  };

  // Read embedded JSON / قراءة البيانات المطبوعة داخل <script type="application/json"> من PHP.
  function readInitial(id, fallback) {
    var node = document.getElementById(id);
    if (!node) return fallback;
    try {
      return JSON.parse(node.textContent || 'null') || fallback;
    } catch (error) {
      return fallback;
    }
  }

  // Read localStorage safely / قراءة localStorage مع fallback إذا البيانات تالفة.
  function readStore(key, fallback) {
    try {
      var raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (error) {
      return fallback;
    }
  }

  // Write localStorage + sync server / حفظ محلي ثم مزامنة مع api.php.
  function writeStore(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
    syncServerStore(key, value);
  }

  function seedStore(key, data) {
    if (Array.isArray(data) && data.length) {
      localStorage.setItem(key, JSON.stringify(data));
    }
  }

  // Sync resources / مزامنة products/orders/customers/messages مع MySQL/JSON عن طريق API.
  function syncServerStore(key, value) {
    var resourceByKey = {};
    resourceByKey[STORAGE.products] = 'products';
    resourceByKey[STORAGE.orders] = 'orders';
    resourceByKey[STORAGE.customers] = 'customers';
    resourceByKey[STORAGE.admins] = 'admins';
    resourceByKey[STORAGE.messages] = 'messages';

    var resource = resourceByKey[key];
    if (!resource || !Array.isArray(value) || !window.fetch) return;

    fetch('api.php?action=save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ resource: resource, records: value })
    }).catch(function () {
      toast('Saved in this browser, but server sync failed.', 'error');
    });
  }

  // Task 12 / Sync login session: يرسل user للـ PHP session حتى cookies تشتغل.
  function syncSession(user) {
    if (!window.fetch) return;
    fetch('api.php?action=session', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user: user })
    }).catch(function () {});
  }

  function clearServerSession() {
    if (!window.fetch) return;
    fetch('api.php?action=logout', { method: 'POST' }).catch(function () {});
  }

  // Money helper / تنسيق السعر برقمين عشريين.
  function money(value) {
    return Number(value || 0).toFixed(2);
  }

  // Escape helper / حماية HTML عند بناء عناصر ديناميكية بالـ JS.
  function escapeHTML(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function discountedPrice(product) {
    var price = Number(product && product.price ? product.price : 0);
    var discount = Number(product && product.discount_percent ? product.discount_percent : 0);
    return discount > 0 ? price * (1 - discount / 100) : price;
  }

  // Initial data / البيانات القادمة من PHP لكل صفحة.
  var initial = {
    products: readInitial('initial-products', []),
    orders: readInitial('initial-orders', []),
    customers: readInitial('initial-customers', []),
    admins: readInitial('initial-admins', []),
    messages: readInitial('initial-contact-messages', [])
  };

  seedStore(STORAGE.products, initial.products);
  seedStore(STORAGE.orders, initial.orders);
  seedStore(STORAGE.customers, initial.customers);
  seedStore(STORAGE.admins, initial.admins);
  seedStore(STORAGE.messages, initial.messages);

  if (window.ELITE_SESSION_USER && window.ELITE_SESSION_USER.email) {
    localStorage.setItem(STORAGE.user, JSON.stringify(window.ELITE_SESSION_USER));
  }

  // ── Restore cart from PHP session if localStorage is empty ─────────────────
  // Task 5 / Restore cart from PHP session إذا localStorage فاضي.
  (function restoreCartFromSession() {
    var existing = readStore(STORAGE.cart, []);
    if (!existing.length) {
      fetch('api.php?action=cart-load')
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok && Array.isArray(data.cart) && data.cart.length) {
            writeStore(STORAGE.cart, data.cart);
            window.dispatchEvent(new CustomEvent('elitegear:cart'));
          }
        })
        .catch(function () {});
    }
  })();
  // ──────────────────────────────────────────────────────────────────────────

  syncSeededProductImages();

  function getProducts() {
    return readStore(STORAGE.products, initial.products);
  }

  function saveProducts(products) {
    writeStore(STORAGE.products, products);
  }

  function syncSeededProductImages() {
    if (!Array.isArray(initial.products) || !initial.products.length) return;
    var imageById = {};
    initial.products.forEach(function (product) {
      if (product && product.id && product.image_url) {
        imageById[product.id] = product.image_url;
      }
    });
    if (!Object.keys(imageById).length) return;

    var changedProducts = false;
    var products = readStore(STORAGE.products, initial.products).map(function (product) {
      if (product && product.id && shouldUseSeedImage(product.image_url) && imageById[product.id]) {
        changedProducts = true;
        return Object.assign({}, product, { image_url: imageById[product.id] });
      }
      return product;
    });
    if (changedProducts) {
      writeStore(STORAGE.products, products);
    }

    var changedCart = false;
    var cart = readStore(STORAGE.cart, []).map(function (item) {
      if (item && item.id && shouldUseSeedImage(item.image_url) && imageById[item.id]) {
        changedCart = true;
        return Object.assign({}, item, { image_url: imageById[item.id] });
      }
      return item;
    });
    if (changedCart) {
      writeStore(STORAGE.cart, cart);
    }
  }

  function shouldUseSeedImage(url) {
    return !url || String(url).indexOf('assets/images/products/') === 0;
  }

  function getOrders() {
    return readStore(STORAGE.orders, initial.orders);
  }

  function saveOrders(orders) {
    writeStore(STORAGE.orders, orders);
  }

  function getCustomers() {
    return readStore(STORAGE.customers, initial.customers);
  }

  function saveCustomers(customers) {
    writeStore(STORAGE.customers, customers);
  }

  function getAdmins() {
    return readStore(STORAGE.admins, initial.admins);
  }

  function getMessages() {
    return readStore(STORAGE.messages, initial.messages);
  }

  function saveMessages(messages) {
    writeStore(STORAGE.messages, messages);
  }

  function getUser() {
    return readStore(STORAGE.user, null);
  }

  function setUser(user) {
    writeStore(STORAGE.user, user);
    syncSession(user);
    window.dispatchEvent(new CustomEvent('elitegear:auth'));
  }

  function logout() {
    localStorage.removeItem(STORAGE.user);
    clearServerSession();
    window.dispatchEvent(new CustomEvent('elitegear:auth'));
    if (window.ELITE_PAGE === 'admin' || window.ELITE_PAGE === 'my-orders') {
      window.location.href = 'index.php';
    }
  }

  // ── PHP SESSION CART SYNC (Task 5: PHP sessions for cart) ──────────────────
  // Task 5 / Sync cart to PHP session so the project uses sessions, not only localStorage.
  function syncCartToSession(cart) {
    fetch('api.php?action=cart-save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cart: cart })
    }).catch(function () { /* silent – localStorage is the fallback */ });
  }

  function getCart() {
    return readStore(STORAGE.cart, []);
  }

  function saveCart(cart) {
    writeStore(STORAGE.cart, cart);
    syncCartToSession(cart); // persist to PHP $_SESSION as required by Task 5
    window.dispatchEvent(new CustomEvent('elitegear:cart'));
  }
  // ──────────────────────────────────────────────────────────────────────────

  function cartCount() {
    return getCart().reduce(function (sum, item) {
      return sum + Number(item.quantity || 0);
    }, 0);
  }

  function cartTotal() {
    return getCart().reduce(function (sum, item) {
      return sum + discountedPrice(item) * Number(item.quantity || 0);
    }, 0);
  }

  function findProduct(id) {
    return getProducts().find(function (product) {
      return product.id === id;
    }) || null;
  }

  function cartKey(productId, options) {
    options = options || {};
    return [productId, options.size || '', options.color || ''].join('|');
  }

  // Task 5 / Add to cart: يتحقق من stock والكمية والمقاس قبل حفظ المنتج في السلة.
  function addToCart(product, quantity, options) {
    if (!product || Number(product.stock || 0) <= 0) {
      toast('Out of stock', 'error');
      return false;
    }

    options = options || {};
    var qty = Number(quantity);
    if (!isFinite(qty) || qty < 1) {
      toast('Please enter the quantity you want.', 'error');
      return false;
    }
    qty = Math.floor(qty);
    if (String(product.sizes || '').trim() && !options.size) {
      toast('Please choose a size before adding this product.', 'error');
      return false;
    }

    var cart = getCart();
    var key = cartKey(product.id, options);
    var existing = cart.find(function (item) {
      return (item.cart_key || cartKey(item.id, { size: item.selected_size, color: item.selected_color })) === key;
    });
    var existingQty = existing ? Number(existing.quantity || 0) : 0;

    if (existingQty + qty > Number(product.stock || 0)) {
      toast('Only ' + product.stock + ' item(s) available in stock.', 'error');
      return false;
    }

    if (existing) {
      existing.quantity = Number(existing.quantity || 0) + qty;
    } else {
      cart.push(Object.assign({}, product, {
        cart_key: key,
        quantity: qty,
        selected_size: options.size || '',
        selected_color: options.color || ''
      }));
    }

    saveCart(cart);
    return true;
  }

  function updateCartQuantity(productId, quantity) {
    var qty = Number(quantity);
    var cart = getCart();
    if (qty <= 0) {
      cart = cart.filter(function (item) { return (item.cart_key || item.id) !== productId; });
    } else {
      cart = cart.map(function (item) {
        return (item.cart_key || item.id) === productId ? Object.assign({}, item, { quantity: qty }) : item;
      });
    }
    saveCart(cart);
  }

  function removeFromCart(productId) {
    saveCart(getCart().filter(function (item) { return (item.cart_key || item.id) !== productId; }));
  }

  function clearCart() {
    saveCart([]);
  }

  function toast(message, type) {
    var stack = document.querySelector('[data-toast-stack]');
    if (!stack) return;
    var item = document.createElement('div');
    item.className = 'toast' + (type ? ' ' + type : '');
    item.textContent = message;
    stack.appendChild(item);
    window.setTimeout(function () {
      item.style.opacity = '0';
      item.style.transform = 'translateY(-0.25rem)';
    }, 2600);
    window.setTimeout(function () {
      item.remove();
    }, 3100);
  }

  function updateNavbar() {
    var count = cartCount();
    document.querySelectorAll('[data-cart-count]').forEach(function (node) {
      node.textContent = count;
      node.classList.toggle('visible', count > 0);
      if (count > 0) {
        node.classList.remove('pulse');
        void node.offsetWidth;
        node.classList.add('pulse');
      }
    });

    var user = getUser();
    var isAdmin = user && user.role === 'admin';
    document.querySelectorAll('[data-admin-only]').forEach(function (node) {
      node.style.display = isAdmin ? '' : 'none';
    });
    document.querySelectorAll('[data-user-name]').forEach(function (node) {
      node.textContent = user ? (user.full_name || user.user_name || user.email) : '';
    });
    document.querySelectorAll('[data-guest-auth]').forEach(function (node) {
      node.style.display = user ? 'none' : '';
    });
    document.querySelectorAll('[data-user-auth]').forEach(function (node) {
      if (node.classList.contains('user-actions')) {
        node.style.display = user ? 'flex' : 'none';
      } else {
        node.style.display = user ? 'block' : 'none';
      }
    });
    document.querySelectorAll('[data-mobile-user-link]').forEach(function (node) {
      node.style.display = user ? 'flex' : 'none';
    });
  }

  function setupNav() {
    var toggle = document.querySelector('[data-mobile-toggle]');
    var menu = document.querySelector('[data-mobile-menu]');
    if (toggle && menu) {
      toggle.addEventListener('click', function () {
        toggle.classList.toggle('is-open');
        menu.classList.toggle('is-open');
      });
      menu.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
          toggle.classList.remove('is-open');
          menu.classList.remove('is-open');
        }
      });
    }

    document.addEventListener('click', function (event) {
      var logoutButton = event.target.closest('[data-logout]');
      if (logoutButton) {
        event.preventDefault();
        logout();
      }

      var addButton = event.target.closest('[data-add-product]');
      if (addButton) {
        event.preventDefault();
        var product = findProduct(addButton.getAttribute('data-add-product'));
        if (product) {
          toast('Open the product page and choose size and quantity first.', 'error');
          window.location.href = 'product-detail.php?id=' + encodeURIComponent(product.id);
          return;
        }
      }
    });
  }

  // Task 3 + 13 / Products page interactions: search, category filtering, and sorting.
  function setupProductsPage() {
    var grid = document.querySelector('[data-products-grid]');
    if (!grid) return;

    var search = document.querySelector('[data-product-search]');
    var sort = document.querySelector('[data-product-sort]');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-category-filter] button'));
    var countNode = document.querySelector('[data-product-count]');
    var empty = document.querySelector('[data-products-empty]');
    var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-product-card]'));
    var activeCategory = (buttons.find(function (button) { return button.classList.contains('active'); }) || buttons[0]).dataset.category || 'All';

    function applyFilters() {
      var term = (search && search.value ? search.value : '').trim().toLowerCase();
      var visible = 0;

      cards.forEach(function (card) {
        var category = card.dataset.category || '';
        var haystack = [card.dataset.name, card.dataset.brand, card.dataset.category].join(' ').toLowerCase();
        var matchCategory = activeCategory === 'All' || category === activeCategory;
        var matchSearch = !term || haystack.indexOf(term) !== -1;
        var show = matchCategory && matchSearch;
        card.classList.toggle('is-hidden', !show);
        if (show) visible += 1;
      });

      if (countNode) countNode.textContent = visible;
      if (empty) empty.classList.toggle('is-hidden', visible > 0);
    }

    function applySort() {
      var value = sort ? sort.value : '-created_date';
      cards.sort(function (a, b) {
        if (value === 'price') return Number(a.dataset.price) - Number(b.dataset.price);
        if (value === '-price') return Number(b.dataset.price) - Number(a.dataset.price);
        if (value === '-rating') return Number(b.dataset.rating) - Number(a.dataset.rating);
        return String(b.dataset.created).localeCompare(String(a.dataset.created));
      }).forEach(function (card) {
        grid.appendChild(card);
      });
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        activeCategory = button.dataset.category || 'All';
        buttons.forEach(function (item) {
          var pressed = item === button;
          item.classList.toggle('active', pressed);
          item.setAttribute('aria-pressed', pressed ? 'true' : 'false');
        });
        var url = activeCategory === 'All' ? 'products.php' : 'products.php?category=' + encodeURIComponent(activeCategory);
        history.replaceState(null, '', url);
        applyFilters();
      });
    });

    if (search) search.addEventListener('input', applyFilters);
    if (sort) sort.addEventListener('change', function () {
      applySort();
      applyFilters();
    });

    applySort();
    applyFilters();
  }

  // Task 4 + 5 + 14 / Product Detail: quantity/size validation, Add to Cart, Help popup.
  function setupProductDetail() {
    var data = readInitial('product-detail-data', null);
    if (!data) return;

    var qty = document.querySelector('[data-detail-qty]');
    var add = document.querySelector('[data-detail-add]');
    var error = document.querySelector('[data-stock-error]');
    var colorLabel = document.querySelector('[data-selected-color]');
    var sizeLabel = document.querySelector('[data-selected-size]');
    var selectedColor = '';
    var selectedSize = '';
    var hasSizes = String(data.sizes || '').trim().length > 0;
    if (qty) qty.value = '';

    document.querySelectorAll('[data-option-color]').forEach(function (button) {
      button.addEventListener('click', function () {
        selectedColor = selectedColor === button.dataset.optionColor ? '' : button.dataset.optionColor;
        document.querySelectorAll('[data-option-color]').forEach(function (item) {
          item.classList.toggle('active', item.dataset.optionColor === selectedColor);
        });
        if (colorLabel) colorLabel.textContent = selectedColor ? ', ' + selectedColor : '';
      });
    });

    document.querySelectorAll('[data-option-size]').forEach(function (button) {
      button.addEventListener('click', function () {
        selectedSize = selectedSize === button.dataset.optionSize ? '' : button.dataset.optionSize;
        document.querySelectorAll('[data-option-size]').forEach(function (item) {
          item.classList.toggle('active', item.dataset.optionSize === selectedSize);
        });
        if (sizeLabel) sizeLabel.textContent = selectedSize ? ', ' + selectedSize : '';
      });
    });

    function showError(message) {
      if (!error) return;
      error.textContent = message;
      error.classList.remove('is-hidden');
    }

    if (qty) {
      qty.addEventListener('input', function () {
        if (error) error.classList.add('is-hidden');
      });
    }

    if (add) {
      add.addEventListener('click', function () {
        var quantity = Number(qty && qty.value ? qty.value : 0);
        if (hasSizes && !selectedSize) {
          showError('Please choose a size before adding this product.');
          return;
        }
        if (!quantity || quantity < 1) {
          showError('Please enter the quantity you want.');
          return;
        }
        if (quantity > Number(data.stock || 0)) {
          showError('Only ' + data.stock + ' items available in stock.');
          return;
        }
        if (error) error.classList.add('is-hidden');
        if (addToCart(data, quantity, { size: selectedSize, color: selectedColor })) {
          var label = add.querySelector('span');
          if (label) label.textContent = 'Added to Cart!';
          toast(data.name + ' added to cart!');
          window.setTimeout(function () {
            if (label) label.textContent = 'Add to Cart';
          }, 2000);
        }
      });
    }

    document.querySelectorAll('[data-go-back]').forEach(function (button) {
      button.addEventListener('click', function () {
        if (history.length > 1) history.back();
        else window.location.href = 'products.php';
      });
    });

    // CIS311 Task 14 / Help popup state: نتحكم بفتح وإغلاق نافذة المساعدة.
    var modal = document.querySelector('[data-help-modal]');
    var helpOpen = false;

    // setHelpOpen / دالة مركزية: تفتح أو تغلق المودال وتحدث aria-hidden للـ accessibility.
    function setHelpOpen(open) {
      helpOpen = !!open;
      if (!modal) return;
      modal.classList.toggle('is-hidden', !helpOpen);
      modal.setAttribute('aria-hidden', helpOpen ? 'false' : 'true');
      if (helpOpen) {
        var closeButton = modal.querySelector('[data-help-close]');
        if (closeButton) closeButton.focus();
      }
    }

    // Open popup / زر Need Help يفتح نافذة Product Help.
    document.querySelectorAll('[data-help-open]').forEach(function (button) {
      button.addEventListener('click', function () {
        setHelpOpen(true);
      });
    });

    // Close popup / أزرار الإغلاق تغلق المودال.
    document.querySelectorAll('[data-help-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        setHelpOpen(false);
      });
    });

    if (modal) {
      // Outside click / إذا ضغط المستخدم خارج صندوق المساعدة نقفل المودال.
      modal.addEventListener('click', function (event) {
        if (event.target === modal) setHelpOpen(false);
      });

      // Escape key / يسمح بالإغلاق من الكيبورد لسهولة الوصول.
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && helpOpen) setHelpOpen(false);
      });
    }
  }

  function setupPasswordToggles() {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
      button.addEventListener('click', function () {
        var field = button.parentElement ? button.parentElement.querySelector('[data-password-input]') : null;
        if (!field) return;
        field.type = field.type === 'password' ? 'text' : 'password';
      });
    });
  }

  function setFieldError(form, name, message) {
    var input = form.querySelector('[name="' + name + '"]');
    var error = form.querySelector('[data-error-for="' + name + '"]');
    var wrap = form.querySelector('[data-field-wrap="' + name + '"]');
    if (input) input.classList.toggle('error', !!message);
    if (error) error.textContent = message || '';
    if (wrap && message) {
      wrap.classList.remove('animate-shake');
      void wrap.offsetWidth;
      wrap.classList.add('animate-shake');
    }
  }

  // Task 13 / Register validation: يتحقق من الاسم، الإيميل، كلمة المرور، والتأكيد.
  function setupRegister() {
    var form = document.querySelector('[data-register-form]');
    if (!form) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var values = Object.fromEntries(new FormData(form).entries());
      var errors = {};
      if (!values.name || !values.name.trim()) errors.name = 'Full name is required.';
      if (!values.email || !values.email.trim()) errors.email = 'Email is required.';
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) errors.email = 'Enter a valid email.';
      if (!values.password) errors.password = 'Password is required.';
      else if (values.password.length < 6) errors.password = 'Password must be at least 6 characters.';
      if (values.password !== values.confirm) errors.confirm = 'Passwords do not match.';

      ['name', 'email', 'password', 'confirm'].forEach(function (name) {
        setFieldError(form, name, errors[name] || '');
      });
      if (Object.keys(errors).length) return;

      var customers = getCustomers();
      var existing = customers.find(function (customer) {
        return String(customer.email).toLowerCase() === values.email.toLowerCase();
      });
      var user = {
        id: existing ? existing.id : 'cust_' + Date.now(),
        full_name: values.name.trim(),
        email: values.email.trim(),
        password: values.password,
        role: 'customer',
        is_active: true
      };

      if (existing) {
        Object.assign(existing, user);
      } else {
        customers.push(user);
      }
      saveCustomers(customers);
      setUser(user);
      var label = form.querySelector('.auth-submit span');
      if (label) label.textContent = 'Redirecting...';
      toast('Account created successfully!');
      window.setTimeout(function () {
        window.location.href = 'my-orders.php';
      }, 500);
    });
  }

  // Task 13 / Contact validation: يتحقق من الحقول المطلوبة ثم يحفظ الرسالة.
  function setupContact() {
    var form = document.querySelector('[data-contact-form]');
    if (!form) return;
    var error = document.querySelector('[data-contact-error]');
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var values = Object.fromEntries(new FormData(form).entries());
      var invalid = !values.name || !values.email || !values.message;
      if (invalid) {
        if (error) {
          error.textContent = 'Please fill all required fields.';
          error.classList.remove('is-hidden');
        }
        toast('Please fill all required fields.', 'error');
        return;
      }
      if (error) error.classList.add('is-hidden');
      var messages = getMessages();
      messages.push({
        id: 'msg_' + Date.now(),
        created_date: new Date().toISOString(),
        name: values.name,
        email: values.email,
        subject: values.subject || '',
        message: values.message
      });
      saveMessages(messages);
      form.reset();
      toast('Message sent successfully!');
    });
  }

  function parseItems(order) {
    try {
      return JSON.parse(order.items_snapshot || '[]');
    } catch (error) {
      return [];
    }
  }

  function parsePayment(order) {
    try {
      return JSON.parse(order.payment_snapshot || 'null');
    } catch (error) {
      return null;
    }
  }

  function orderItemLabel(item) {
    var details = [
      item.selected_size ? 'Size: ' + item.selected_size : '',
      item.selected_color ? 'Color: ' + item.selected_color : ''
    ].filter(Boolean);
    return item.name + (details.length ? ' (' + details.join(', ') + ')' : '');
  }

  // --- COOKIE HELPERS for past purchases ---
  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  // Task 12 / Past purchases: يعرض آخر الطلبات للعميل الراجع.
  function renderPreviousGains() {
    var section = document.querySelector('[data-previous-gains]');
    if (!section) return;

    var user = getUser();
    var allOrders = getOrders();

    // Determine which email to look up:
    // 1. Logged-in user  2. Cookie from a previous session
    var emailToShow = (user && user.email) || getCookie('eg_customer_email');

    if (!emailToShow) {
      section.classList.add('is-hidden');
      return;
    }

    var orders = allOrders.filter(function (order) {
      return order.user_email === emailToShow;
    }).slice(0, 5);

    if (!orders.length) {
      section.classList.add('is-hidden');
      return;
    }

    var welcome = section.querySelector('[data-previous-welcome]');
    var list = section.querySelector('[data-previous-orders]');
    var displayName = (user && (user.full_name || user.user_name)) || 'Returning Customer';

    if (welcome) welcome.textContent = 'Welcome back, ' + displayName;
    if (list) {
      list.innerHTML = orders.map(function (order) {
        var items = parseItems(order);
        return '<div class="previous-order-card">' +
          '<p class="order-id">#' + escapeHTML(String(order.id).slice(-6).toUpperCase()) + '</p>' +
          '<p class="order-meta">' + items.length + ' item(s)</p>' +
          '<p class="order-total">' + money(order.total_amount) + ' SAR</p>' +
          '<span class="order-status">' + escapeHTML(order.status || '') + '</span>' +
          '</div>';
      }).join('');
    }
    section.classList.remove('is-hidden');
  }
  // -----------------------------------------

  function statusConfig(status) {
    var map = {
      confirmed: { label: 'Confirmed', color: '#00E676', icon: '&#10003;' },
      shipped: { label: 'Shipped', color: '#fbbf24', icon: '&#9632;' },
      delivered: { label: 'Delivered', color: '#00E676', icon: '&#8962;' },
      cancelled: { label: 'Cancelled', color: '#ef4444', icon: '&#10005;' },
      pending: { label: 'Pending', color: '#94a3b8', icon: '&#9711;' }
    };
    return map[status] || map.pending;
  }

  // Checkout support / يعرض تفاصيل تتبع الطلب حسب id في الرابط.
  function renderOrderTracking() {
    var root = document.querySelector('[data-order-tracking]');
    if (!root) return;
    var id = new URLSearchParams(window.location.search).get('id') || '';
    var order = getOrders().find(function (item) { return item.id === id; });
    if (!order) {
      root.innerHTML = '<div class="not-found-state"><p>Order not found</p><a href="index.php" class="primary-button">Go Home</a></div>';
      return;
    }
    var steps = [
      { key: 'confirmed', label: 'Order Confirmed', desc: 'Your order has been received and confirmed.', icon: '&#10003;' },
      { key: 'shipped', label: 'Shipped', desc: 'Your order is on its way.', icon: '&#9632;' },
      { key: 'delivered', label: 'Delivered', desc: 'Your order has been delivered.', icon: '&#8962;' }
    ];
    var statusIndex = { confirmed: 0, shipped: 1, delivered: 2 };
    var current = statusIndex[order.status] == null ? 0 : statusIndex[order.status];
    var isCancelled = order.status === 'cancelled';
    var items = parseItems(order);

    root.innerHTML =
      '<header class="page-banner"><div class="order-banner-inner">' +
      '<a href="index.php" class="order-back-link">&#8592; Back to Home</a>' +
      '<div class="section-heading banner-heading"><span></span><div><h1>Order Tracking</h1><p class="order-id-line">#' + escapeHTML(String(order.id).slice(-10).toUpperCase()) + '</p></div></div>' +
      '</div></header>' +
      '<section class="order-shell order-stack">' +
      (isCancelled ? '<div class="cancelled-alert">This order has been cancelled.</div>' : renderSteps(steps, current)) +
      renderOrderDetails(order, items) +
      '<a href="products.php" class="continue-link">Continue Shopping &#8594;</a>' +
      '</section>';
  }

  function renderSteps(steps, current) {
    return '<div class="status-card"><h2>Status</h2><div class="step-list">' + steps.map(function (step, index) {
      var complete = index <= current;
      return '<div class="step-item ' + (complete ? 'completed' : '') + '">' +
        '<div class="step-icon">' + step.icon + '</div>' +
        '<div class="step-copy"><p>' + step.label + (index === current ? '<span class="current-pill">Current</span>' : '') + '</p><small>' + step.desc + '</small></div>' +
        '</div>';
    }).join('') + '</div></div>';
  }

  function renderOrderDetails(order, items) {
    var payment = parsePayment(order);
    var paymentText = payment
      ? (escapeHTML(payment.brand || 'Card') + ' ending ' + escapeHTML(payment.last4 || '----') + ' (' + escapeHTML(order.payment_status || 'paid') + ')')
      : escapeHTML(order.payment_method || 'Cash on delivery');
    return '<div class="order-detail-card"><h2>Order Details</h2>' +
      '<div class="detail-lines">' +
      '<div><span>Name</span><span>' + escapeHTML(order.user_name || order.user_email || '') + '</span></div>' +
      '<div><span>Address</span><span>' + escapeHTML(order.shipping_address || '') + '</span></div>' +
      '<div><span>Phone</span><span>' + escapeHTML(order.phone || '') + '</span></div>' +
      '<div><span>Payment</span><span>' + paymentText + '</span></div>' +
      '<div class="total"><span>Total</span><span>' + money(order.total_amount) + ' SAR</span></div>' +
      '</div>' +
      (items.length ? '<div class="items-block"><p>Items</p>' + items.map(function (item) {
        return '<div class="order-item-line"><span>' + escapeHTML(orderItemLabel(item)) + ' &times; ' + Number(item.quantity || 0) + '</span><span>' + money(Number(item.price || 0) * Number(item.quantity || 0)) + ' SAR</span></div>';
      }).join('') + '</div>' : '') +
      '</div>';
  }

  // My Orders / يعرض طلبات المستخدم الحالي بعد تسجيل الدخول.
  function renderMyOrders() {
    var root = document.querySelector('[data-my-orders]');
    if (!root) return;
    var user = getUser();
    if (!user) {
      root.innerHTML = '<div class="not-found-state"><div class="empty-icon">&#9632;</div><p>Login Required</p><a href="admin.php" class="primary-button">Login</a></div>';
      return;
    }
    var orders = getOrders().filter(function (order) {
      return order.user_email === user.email;
    });
    root.innerHTML =
      '<header class="page-banner"><div class="order-banner-inner"><div class="section-heading banner-heading"><span></span><div><h1>My Orders</h1><p class="catalog-count">' + orders.length + ' order(s) found</p></div></div></div></header>' +
      '<section class="order-shell">' +
      (orders.length ? '<div class="orders-list">' + orders.map(renderOrderCard).join('') + '</div>' : '<div class="empty-state"><div class="empty-icon">&#9632;</div><p>No orders yet</p><span>Start shopping to place your first order.</span><a href="products.php" class="primary-button clip-shear">Shop Now</a></div>') +
      '</section>';
  }

  function renderOrderCard(order) {
    var items = parseItems(order);
    var status = statusConfig(order.status);
    var payment = parsePayment(order);
    var paymentLine = payment ? 'Paid by ' + escapeHTML(payment.brand || 'Card') + ' ending ' + escapeHTML(payment.last4 || '----') : 'Payment on delivery';
    return '<div class="order-card">' +
      '<div class="order-card-main">' +
      '<div class="order-card-left"><div class="order-status-icon" style="color:' + status.color + '">' + status.icon + '</div>' +
      '<div><h3>Order #' + escapeHTML(String(order.id).slice(-8).toUpperCase()) + '</h3><p>' + items.length + ' item(s), ' + money(order.total_amount) + ' SAR</p>' +
      '<p class="payment-line">' + paymentLine + '</p>' +
      (order.shipping_address ? '<address>' + escapeHTML(order.shipping_address) + '</address>' : '') + '</div></div>' +
      '<div class="order-card-actions"><span class="status-pill" style="color:' + status.color + ';background:' + status.color + '20;border-color:' + status.color + '40">' + status.label + '</span>' +
      '<a href="order-tracking.php?id=' + encodeURIComponent(order.id) + '" class="track-link">Track &#8594;</a></div>' +
      '</div>' +
      (items.length ? '<div class="order-item-chips">' + items.map(function (item) {
        return '<span>' + escapeHTML(orderItemLabel(item)) + ' &times; ' + Number(item.quantity || 0) + '</span>';
      }).join('') + '</div>' : '') +
      '</div>';
  }

  // Public app API / واجهة مشتركة تستخدمها cart.js و admin.js بدل تكرار الكود.
  window.EliteGear = {
    STORAGE: STORAGE,
    escapeHTML: escapeHTML,
    money: money,
    discountedPrice: discountedPrice,
    getProducts: getProducts,
    saveProducts: saveProducts,
    getOrders: getOrders,
    saveOrders: saveOrders,
    getCustomers: getCustomers,
    saveCustomers: saveCustomers,
    getAdmins: getAdmins,
    getUser: getUser,
    setUser: setUser,
    logout: logout,
    getCart: getCart,
    saveCart: saveCart,
    addToCart: addToCart,
    updateCartQuantity: updateCartQuantity,
    removeFromCart: removeFromCart,
    clearCart: clearCart,
    cartCount: cartCount,
    cartTotal: cartTotal,
    findProduct: findProduct,
    toast: toast,
    updateNavbar: updateNavbar
  };

  document.addEventListener('DOMContentLoaded', function () {
    setupNav();
    setupProductsPage();
    setupProductDetail();
    setupPasswordToggles();
    setupRegister();
    setupContact();
    renderPreviousGains();
    renderOrderTracking();
    renderMyOrders();
    updateNavbar();
  });

  window.addEventListener('elitegear:cart', updateNavbar);
  window.addEventListener('elitegear:auth', function () {
    updateNavbar();
    renderPreviousGains();
    renderMyOrders();
  });
})();
