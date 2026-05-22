(function () {
  'use strict';

  /*
   * Admin JavaScript / سكربت لوحة الإدارة:
   * Task 8: Authenticates admin/customer login from local data.
   * Task 9: Adds new products through product modal form.
   * Task 10: Edits/deletes/searches products and syncs changes.
   * Task 7/Admin Orders: Shows customer orders and updates order status.
   * Task 13: Validates product form fields before save.
   * Author / المنفذ: EliteGear Team.
   */

  // Shared app object / دوال مشتركة من main.js للمنتجات والطلبات والتوست.
  function EG() {
    return window.EliteGear;
  }

  // Admin page state / حالة الصفحة: الدور المختار، المنتج قيد التعديل، وصورة الرفع.
  var selectedRole = null;
  var editingId = null;
  var uploadDataUrl = '';

  function productFallback() {
    return '&#127947;&#65039;';
  }

  // Task 8 / Render admin: يقرر هل يعرض login أو dashboard حسب المستخدم الحالي.
  function renderAdmin() {
    var eg = EG();
    var page = document.querySelector('[data-admin-page]');
    if (!eg || !page) return;

    var user = eg.getUser();
    var login = document.querySelector('[data-admin-login]');
    var dashboard = document.querySelector('[data-admin-dashboard]');
    if (login) login.classList.toggle('is-hidden', !!(user && user.role === 'admin'));
    if (dashboard) dashboard.classList.toggle('is-hidden', !(user && user.role === 'admin'));
    if (user && user.role === 'admin') {
      var name = document.querySelector('[data-admin-name]');
      if (name) name.textContent = user.user_name || user.full_name || user.email;
      renderProducts();
      renderOrders();
    }
  }

  // Task 10 / Product search: يفلتر المنتجات في dashboard حسب الاسم/التصنيف/الماركة.
  function filteredProducts() {
    var eg = EG();
    var search = document.querySelector('[data-admin-search]');
    var term = search && search.value ? search.value.trim().toLowerCase() : '';
    return eg.getProducts().filter(function (product) {
      if (!term) return true;
      return [product.name, product.category, product.brand].join(' ').toLowerCase().indexOf(term) !== -1;
    });
  }

  // Task 9 + 10 / Render products table: يعرض المنتجات مع أزرار edit/delete.
  function renderProducts() {
    var eg = EG();
    if (!eg) return;
    var products = eg.getProducts();
    var list = filteredProducts();
    var tbody = document.querySelector('[data-admin-products]');
    var total = document.querySelector('[data-total-products]');
    var inStock = document.querySelector('[data-in-stock]');
    var outStock = document.querySelector('[data-out-stock]');
    var totalOrders = document.querySelector('[data-total-orders]');
    if (total) total.textContent = products.length;
    if (inStock) inStock.textContent = products.filter(function (item) { return Number(item.stock || 0) > 0; }).length;
    if (outStock) outStock.textContent = products.filter(function (item) { return Number(item.stock || 0) <= 0; }).length;
    if (totalOrders) totalOrders.textContent = eg.getOrders().length;
    if (!tbody) return;

    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><div class="empty-icon">&#9632;</div><p>No products found.</p></div></td></tr>';
      return;
    }

    tbody.innerHTML = list.map(function (product) {
      var eg = EG();
      var image = product.image_url
        ? '<img src="' + eg.escapeHTML(product.image_url) + '" alt="' + eg.escapeHTML(product.name) + '">'
        : productFallback();
      var stockColor = Number(product.stock || 0) > 5 ? '#00E676' : Number(product.stock || 0) > 0 ? '#fbbf24' : '#ef4444';
      return '<tr>' +
        '<td><div class="table-product"><div class="table-thumb">' + image + '</div><div><p>' + eg.escapeHTML(product.name) + '</p>' +
        (product.brand ? '<small>' + eg.escapeHTML(product.brand) + '</small>' : '') +
        (product.is_featured ? '<span class="mini-badge">Featured</span>' : '') +
        '</div></div></td>' +
        '<td><span class="category-pill">' + eg.escapeHTML(product.category || '') + '</span></td>' +
        '<td><strong style="color:#00E676">' + eg.money(product.price) + ' SAR</strong>' + (Number(product.discount_percent || 0) > 0 ? '<small style="display:block;color:#ef4444">-' + Number(product.discount_percent || 0) + '% OFF</small>' : '') + '</td>' +
        '<td><strong style="color:' + stockColor + '">' + Number(product.stock || 0) + '</strong></td>' +
        '<td><div class="table-actions"><button type="button" data-edit-product="' + eg.escapeHTML(product.id) + '">&#9998; Edit</button><button type="button" class="delete" data-delete-product="' + eg.escapeHTML(product.id) + '">&#128465; Delete</button></div></td>' +
        '</tr>';
    }).join('');
  }

  function parseItems(order) {
    try {
      return JSON.parse(order.items_snapshot || '[]');
    } catch (error) {
      return [];
    }
  }

  function orderItemLabel(item) {
    var details = [
      item.selected_size ? 'Size: ' + item.selected_size : '',
      item.selected_color ? 'Color: ' + item.selected_color : ''
    ].filter(Boolean);
    return item.name + (details.length ? ' (' + details.join(', ') + ')' : '');
  }

  // Admin orders / عرض طلبات العملاء وتغيير status من لوحة الإدارة.
  function renderOrders() {
    var eg = EG();
    if (!eg) return;
    var list = document.querySelector('[data-admin-orders]');
    var totalOrders = document.querySelector('[data-total-orders]');
    var orders = eg.getOrders().slice().sort(function (a, b) {
      return String(b.created_date || '').localeCompare(String(a.created_date || ''));
    });
    if (totalOrders) totalOrders.textContent = orders.length;
    if (!list) return;

    if (!orders.length) {
      list.innerHTML = '<div class="empty-state admin-empty"><p>No orders yet</p><span>Customer orders will appear here after checkout.</span></div>';
      return;
    }

    list.innerHTML = orders.map(function (order) {
      var items = parseItems(order);
      var shortId = String(order.id || '').slice(-8).toUpperCase();
      var options = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'].map(function (status) {
        return '<option value="' + status + '"' + (order.status === status ? ' selected' : '') + '>' + status.charAt(0).toUpperCase() + status.slice(1) + '</option>';
      }).join('');
      return '<article class="admin-order-card" data-admin-order="' + eg.escapeHTML(order.id || '') + '">' +
        '<div class="admin-order-main">' +
          '<div><h3>Order #' + eg.escapeHTML(shortId) + '</h3><p>' + eg.escapeHTML(order.user_name || order.user_email || '') + '</p><small>' + eg.escapeHTML(order.created_date || '') + '</small></div>' +
          '<div class="admin-order-total">' + eg.money(order.total_amount) + ' SAR</div>' +
        '</div>' +
        '<div class="admin-order-details">' +
          '<p><strong>Email:</strong> ' + eg.escapeHTML(order.user_email || '') + '</p>' +
          '<p><strong>Phone:</strong> ' + eg.escapeHTML(order.phone || '') + '</p>' +
          '<p><strong>Address:</strong> ' + eg.escapeHTML(order.shipping_address || '') + '</p>' +
        '</div>' +
        (items.length ? '<div class="admin-order-items">' + items.map(function (item) {
          return '<span>' + eg.escapeHTML(orderItemLabel(item)) + ' x ' + Number(item.quantity || 0) + '</span>';
        }).join('') + '</div>' : '') +
        '<div class="admin-order-actions"><label>Status <select data-order-status="' + eg.escapeHTML(order.id || '') + '">' + options + '</select></label><a href="order-tracking.php?id=' + encodeURIComponent(order.id || '') + '">View Tracking</a></div>' +
      '</article>';
    }).join('');
  }

  // Order status update / تحديث حالة الطلب وحفظها في orders data/database.
  function updateOrderStatus(orderId, status) {
    var eg = EG();
    var orders = eg.getOrders().map(function (order) {
      return order.id === orderId ? Object.assign({}, order, { status: status }) : order;
    });
    eg.saveOrders(orders);
    eg.toast('Order status updated.');
    renderOrders();
  }

  function setLoginRole(role) {
    selectedRole = role;
    var roleSelect = document.querySelector('[data-role-select]');
    var formWrap = document.querySelector('[data-login-form-wrap]');
    var title = document.querySelector('[data-login-title]');
    var subtitle = document.querySelector('[data-login-subtitle]');
    var email = document.querySelector('#local-login-email');
    var submit = document.querySelector('[data-local-login] .auth-submit');
    if (roleSelect) roleSelect.classList.add('is-hidden');
    if (formWrap) formWrap.classList.remove('is-hidden');

    if (role === 'admin') {
      if (title) title.textContent = 'Admin login';
      if (subtitle) subtitle.textContent = 'Login to continue';
      if (email) email.placeholder = 'admin@elitegear.com';
      if (submit) {
        submit.style.background = '#00E676';
        submit.style.color = '#0A1628';
      }
    } else {
      if (title) title.textContent = 'Customer login';
      if (subtitle) subtitle.textContent = 'Welcome back';
      if (email) email.placeholder = 'you@example.com';
      if (submit) {
        submit.style.background = '#1e3a52';
        submit.style.color = '#F8FAFC';
      }
    }
  }

  function resetRole() {
    selectedRole = null;
    var roleSelect = document.querySelector('[data-role-select]');
    var formWrap = document.querySelector('[data-login-form-wrap]');
    var error = document.querySelector('[data-login-error]');
    var form = document.querySelector('[data-local-login]');
    if (roleSelect) roleSelect.classList.remove('is-hidden');
    if (formWrap) formWrap.classList.add('is-hidden');
    if (error) error.classList.add('is-hidden');
    if (form) form.reset();
  }

  function showLoginError(message) {
    var error = document.querySelector('[data-login-error]');
    if (!error) return;
    error.textContent = message;
    error.classList.remove('is-hidden');
  }

  // Task 8 / Authenticate: يقارن email/password مع admins أو customers.
  function authenticate(values) {
    var eg = EG();
    var email = String(values.email || '').trim().toLowerCase();
    var password = String(values.password || '');
    if (selectedRole === 'admin') {
      var admin = eg.getAdmins().find(function (item) {
        return String(item.user_email || '').toLowerCase() === email && String(item.password || '') === password && item.is_active !== false;
      });
      if (!admin) return null;
      return {
        id: admin.id,
        email: admin.user_email,
        user_name: admin.user_name,
        full_name: admin.user_name,
        role: 'admin'
      };
    }

    var customer = eg.getCustomers().find(function (item) {
      return String(item.email || '').toLowerCase() === email && String(item.password || '') === password && item.is_active !== false;
    });
    if (!customer) return null;
    return {
      id: customer.id,
      email: customer.email,
      full_name: customer.full_name,
      role: 'customer'
    };
  }

  // Product modal / يفتح نافذة المنتج للإضافة أو التعديل حسب وجود product.
  function openProductModal(product) {
    var eg = EG();
    var modal = document.querySelector('[data-product-modal]');
    var form = document.querySelector('[data-product-form]');
    if (!modal || !form) return;
    editingId = product ? product.id : null;
    uploadDataUrl = product && product.image_url ? product.image_url : '';
    form.reset();
    form.elements.id.value = product ? product.id : '';
    form.elements.name.value = product ? product.name || '' : '';
    form.elements.brand.value = product ? product.brand || '' : '';
    form.elements.price.value = product ? product.price || '' : '';
    form.elements.stock.value = product ? product.stock || '' : '';
    form.elements.discount_percent.value = product ? product.discount_percent || '' : '';
    form.elements.rating.value = product ? product.rating || '' : '';
    form.elements.category.value = product ? product.category || 'Football' : 'Football';
    form.elements.colors.value = product ? product.colors || '' : '';
    form.elements.sizes.value = product ? product.sizes || '' : '';
    form.elements.description.value = product ? product.description || '' : '';
    form.elements.image_url.value = product ? product.image_url || '' : '';
    form.elements.is_featured.checked = !!(product && product.is_featured);
    form.querySelectorAll('.error').forEach(function (node) { node.classList.remove('error'); });
    form.querySelectorAll('.field-error').forEach(function (node) { node.textContent = ''; });

    var title = document.querySelector('[data-modal-title]');
    var save = document.querySelector('[data-save-label]');
    if (title) title.textContent = product ? 'Edit Product' : 'New Product';
    if (save) save.textContent = product ? 'Update Product' : 'Add Product';
    updateImagePreview(product ? product.image_url : '');
    modal.classList.remove('is-hidden');
  }

  function closeProductModal() {
    var modal = document.querySelector('[data-product-modal]');
    if (modal) modal.classList.add('is-hidden');
    editingId = null;
    uploadDataUrl = '';
  }

  function updateImagePreview(url) {
    var eg = EG();
    var preview = document.querySelector('[data-image-preview]');
    if (!preview) return;
    if (url) {
      preview.innerHTML = '<img src="' + eg.escapeHTML(url) + '" alt="Preview">';
    } else {
      preview.innerHTML = '<svg class="icon icon-xl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v14H4z"/><circle cx="9" cy="10" r="2"/><path d="m4 17 4-4 3 3 4-5 5 6"/></svg>';
    }
  }

  // Task 13 / Product form validation: يتأكد من name, price, stock قبل الحفظ.
  function validateProduct(form) {
    var valid = true;
    var checks = {
      name: !form.elements.name.value.trim() ? 'Required' : '',
      price: !form.elements.price.value || Number(form.elements.price.value) <= 0 ? 'Enter valid price' : '',
      stock: form.elements.stock.value === '' || Number(form.elements.stock.value) < 0 ? 'Enter valid stock' : ''
    };
    Object.keys(checks).forEach(function (key) {
      var input = form.elements[key];
      var error = form.querySelector('[data-error-for="' + key + '"]');
      if (checks[key]) valid = false;
      if (input) input.classList.toggle('error', !!checks[key]);
      if (error) error.textContent = checks[key];
    });
    return valid;
  }

  // Task 9 + 10 / Save product: يضيف منتج جديد أو يحدث المنتج الحالي.
  function saveProduct(form) {
    var eg = EG();
    if (!validateProduct(form)) return;
    var products = eg.getProducts();
    var imageUrl = uploadDataUrl || form.elements.image_url.value.trim();
    var data = {
      id: editingId || 'prod_' + Date.now(),
      created_date: editingId
        ? (products.find(function (product) { return product.id === editingId; }) || {}).created_date || new Date().toISOString()
        : new Date().toISOString(),
      name: form.elements.name.value.trim(),
      description: form.elements.description.value.trim(),
      price: Number(form.elements.price.value),
      stock: Number(form.elements.stock.value),
      category: form.elements.category.value,
      brand: form.elements.brand.value.trim(),
      image_url: imageUrl,
      colors: form.elements.colors.value.trim(),
      sizes: form.elements.sizes.value.trim(),
      discount_percent: form.elements.discount_percent.value ? Number(form.elements.discount_percent.value) : 0,
      is_featured: form.elements.is_featured.checked,
      rating: form.elements.rating.value ? Number(form.elements.rating.value) : 0
    };

    if (editingId) {
      products = products.map(function (product) {
        return product.id === editingId ? data : product;
      });
      eg.toast('Product updated!');
    } else {
      products.unshift(data);
      eg.toast('Product added!');
    }
    eg.saveProducts(products);
    closeProductModal();
    renderProducts();
  }

  // Main admin setup / يربط كل أزرار login, search, add, edit, delete, image upload.
  function setupAdmin() {
    var eg = EG();
    var page = document.querySelector('[data-admin-page]');
    if (!eg || !page) return;

    document.querySelectorAll('[data-role]').forEach(function (button) {
      button.addEventListener('click', function () {
        setLoginRole(button.dataset.role);
      });
    });

    var back = document.querySelector('[data-back-role]');
    if (back) back.addEventListener('click', resetRole);

    var loginForm = document.querySelector('[data-local-login]');
    if (loginForm) {
      loginForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var values = Object.fromEntries(new FormData(loginForm).entries());
        if (!values.email || !values.password) {
          showLoginError('Please enter your email and password.');
          return;
        }
        var user = authenticate(values);
        if (!user) {
          showLoginError(selectedRole === 'admin' ? 'Invalid credentials. Make sure you have admin access.' : 'Invalid email or password. Please try again.');
          return;
        }
        eg.setUser(user);
        if (user.role === 'admin') {
          resetRole();
          renderAdmin();
        } else {
          window.location.href = 'index.php';
        }
      });
    }

    var search = document.querySelector('[data-admin-search]');
    if (search) search.addEventListener('input', renderProducts);

    var add = document.querySelector('[data-add-product-admin]');
    if (add) add.addEventListener('click', function () { openProductModal(null); });

    document.querySelectorAll('[data-close-product-modal]').forEach(function (button) {
      button.addEventListener('click', closeProductModal);
    });

    var modal = document.querySelector('[data-product-modal]');
    if (modal) {
      modal.addEventListener('click', function (event) {
        if (event.target === modal) closeProductModal();
      });
    }

    document.addEventListener('click', function (event) {
      var edit = event.target.closest('[data-edit-product]');
      var del = event.target.closest('[data-delete-product]');
      if (edit) {
        var product = eg.getProducts().find(function (item) { return item.id === edit.dataset.editProduct; });
        if (product) openProductModal(product);
      }
      if (del) {
        var products = eg.getProducts();
        var target = products.find(function (item) { return item.id === del.dataset.deleteProduct; });
        if (!target) return;
        if (!window.confirm('Delete "' + target.name + '"?')) return;
        eg.saveProducts(products.filter(function (item) { return item.id !== target.id; }));
        eg.toast('Product deleted.');
        renderProducts();
      }
    });

    document.addEventListener('change', function (event) {
      var status = event.target.closest('[data-order-status]');
      if (status) {
        updateOrderStatus(status.dataset.orderStatus, status.value);
      }
    });

    var imageUrl = document.querySelector('[data-product-form] [name="image_url"]');
    if (imageUrl) {
      imageUrl.addEventListener('input', function () {
        uploadDataUrl = '';
        updateImagePreview(imageUrl.value.trim());
      });
    }

    var file = document.querySelector('[data-image-file]');
    if (file) {
      file.addEventListener('change', function () {
        var picked = file.files && file.files[0];
        if (!picked) return;
        var reader = new FileReader();
        var label = document.querySelector('[data-upload-label]');
        if (label) label.textContent = 'Uploading...';
        reader.onload = function () {
          uploadDataUrl = String(reader.result || '');
          var form = document.querySelector('[data-product-form]');
          if (form) form.elements.image_url.value = uploadDataUrl;
          updateImagePreview(uploadDataUrl);
          if (label) label.textContent = 'Upload Image';
          eg.toast('Image uploaded!');
        };
        reader.readAsDataURL(picked);
      });
    }

    var productForm = document.querySelector('[data-product-form]');
    if (productForm) {
      productForm.addEventListener('submit', function (event) {
        event.preventDefault();
        saveProduct(productForm);
      });
    }

    renderAdmin();
  }

  document.addEventListener('DOMContentLoaded', setupAdmin);
  window.addEventListener('elitegear:auth', renderAdmin);
})();
