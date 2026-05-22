<?php
/*
 * Database Layer / طبقة قاعدة البيانات:
 * Task 1 Database Design: Creates and uses normalized MySQL tables.
 * Task 3 Display Products: Fetches products from database for the shop pages.
 * Task 7 Buy: Saves orders/order_items and updates customer statistics.
 * Task 16 Efficiency: Centralizes all PDO database code in one reusable file.
 * Author / المنفذ: EliteGear Team.
 */

// XAMPP database credentials / إعدادات الاتصال الافتراضية في XAMPP.
if (!defined('EG_DB_HOST')) {
    define('EG_DB_HOST', '127.0.0.1');
    define('EG_DB_NAME', 'elitegear_store');
    define('EG_DB_USER', 'root');
    define('EG_DB_PASS', '');
}

// PDO connection / الاتصال بقاعدة البيانات: يرجع null إذا MySQL غير شغال.
if (!function_exists('eg_db')) {
    function eg_db() {
        static $pdo = null;
        static $attempted = false;

        if ($attempted) {
            return $pdo;
        }

        $attempted = true;

        try {
            $pdo = new PDO(
                'mysql:host=' . EG_DB_HOST . ';charset=utf8mb4',
                EG_DB_USER,
                EG_DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . EG_DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . EG_DB_NAME . '`');
            eg_db_bootstrap_schema($pdo);
        } catch (Throwable $error) {
            $pdo = null;
        }

        return $pdo;
    }
}

// Quick availability check / فحص سريع هل قاعدة البيانات متاحة.
if (!function_exists('eg_db_available')) {
    function eg_db_available() {
        return eg_db() instanceof PDO;
    }
}

// Schema bootstrap / إنشاء الجداول تلقائيا إذا لم تكن موجودة.
if (!function_exists('eg_db_bootstrap_schema')) {
    function eg_db_bootstrap_schema(PDO $pdo) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $statements = [
            // Task 1 / products table: stores mandatory product fields plus category/brand/ratings.
            "CREATE TABLE IF NOT EXISTS products (
                id VARCHAR(80) PRIMARY KEY,
                created_date DATETIME NOT NULL,
                name VARCHAR(180) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL DEFAULT 0,
                stock INT NOT NULL DEFAULT 0,
                category VARCHAR(80) NOT NULL,
                brand VARCHAR(120),
                image_url TEXT,
                colors VARCHAR(255),
                sizes VARCHAR(255),
                discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
                is_featured TINYINT(1) NOT NULL DEFAULT 0,
                rating DECIMAL(3,1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            // Task 12 / customers table: stores customers and purchase history counters.
            "CREATE TABLE IF NOT EXISTS customers (
                id VARCHAR(80) PRIMARY KEY,
                full_name VARCHAR(160) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(40),
                address TEXT,
                city VARCHAR(100),
                role VARCHAR(40) NOT NULL DEFAULT 'customer',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                total_orders INT NOT NULL DEFAULT 0,
                total_spent DECIMAL(10,2) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            // Task 8 / admins table: stores manager/admin login accounts.
            "CREATE TABLE IF NOT EXISTS admins (
                id VARCHAR(80) PRIMARY KEY,
                user_name VARCHAR(120) NOT NULL,
                user_email VARCHAR(190) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                permissions VARCHAR(80),
                notes TEXT,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            // Task 6 + 7 / orders table: stores checkout result and payment/shipping snapshots.
            "CREATE TABLE IF NOT EXISTS orders (
                id VARCHAR(80) PRIMARY KEY,
                created_date DATETIME NOT NULL,
                user_email VARCHAR(190) NOT NULL,
                user_name VARCHAR(160),
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
                status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'confirmed',
                payment_status VARCHAR(60) NOT NULL DEFAULT 'paid',
                payment_method VARCHAR(60) NOT NULL DEFAULT 'card',
                payment_snapshot JSON,
                shipping_address TEXT,
                phone VARCHAR(40),
                notes TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            // Normalized order items / عناصر الطلب: علاقة one-to-many مع orders.
            "CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id VARCHAR(80) NOT NULL,
                product_id VARCHAR(80) NOT NULL,
                product_name VARCHAR(180) NOT NULL,
                selected_size VARCHAR(80),
                selected_color VARCHAR(80),
                quantity INT NOT NULL DEFAULT 1,
                price DECIMAL(10,2) NOT NULL DEFAULT 0,
                INDEX idx_order_items_order_id (order_id),
                CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            // Task 11/13 support / contact messages from Contact page form.
            "CREATE TABLE IF NOT EXISTS contact_messages (
                id VARCHAR(80) PRIMARY KEY,
                created_date DATETIME NOT NULL,
                name VARCHAR(160) NOT NULL,
                email VARCHAR(190) NOT NULL,
                subject VARCHAR(190),
                message TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        $alterStatements = [
            "ALTER TABLE customers ADD COLUMN phone VARCHAR(40) NULL",
            "ALTER TABLE customers ADD COLUMN address TEXT NULL",
            "ALTER TABLE customers ADD COLUMN city VARCHAR(100) NULL",
            "ALTER TABLE customers ADD COLUMN total_spent DECIMAL(10,2) NOT NULL DEFAULT 0",
            "ALTER TABLE admins ADD COLUMN permissions VARCHAR(80) NULL",
            "ALTER TABLE admins ADD COLUMN notes TEXT NULL",
            "ALTER TABLE order_items ADD INDEX idx_order_items_order_id (order_id)",
        ];

        foreach ($alterStatements as $statement) {
            try {
                $pdo->exec($statement);
            } catch (Throwable $error) {
                // Existing local databases may already have these columns or indexes.
            }
        }
    }
}

// Resource mapper / يربط JSON filenames بأسماء resources المستخدمة في api.php.
if (!function_exists('eg_db_resource_for_path')) {
    function eg_db_resource_for_path($relativePath) {
        $map = [
            'data/products.json' => 'products',
            'data/orders.json' => 'orders',
            'data/customers.json' => 'customers',
            'data/admins.json' => 'admins',
            'data/contact-messages.json' => 'messages',
        ];

        return $map[str_replace('\\', '/', ltrim($relativePath, '/'))] ?? null;
    }
}

// Date conversion / يحول ISO date من JavaScript إلى MySQL DATETIME.
if (!function_exists('eg_iso_to_mysql')) {
    function eg_iso_to_mysql($value) {
        if (!$value) {
            return gmdate('Y-m-d H:i:s');
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : gmdate('Y-m-d H:i:s');
    }
}

// Date conversion / يحول MySQL DATETIME إلى ISO string للـ JavaScript.
if (!function_exists('eg_mysql_to_iso')) {
    function eg_mysql_to_iso($value) {
        if (!$value) {
            return '';
        }

        $timestamp = strtotime((string)$value . ' UTC');
        return $timestamp ? gmdate('Y-m-d\TH:i:s\Z', $timestamp) : (string)$value;
    }
}

// Boolean conversion / يحول tinyint من قاعدة البيانات إلى true/false في PHP arrays.
if (!function_exists('eg_db_bool')) {
    function eg_db_bool($value) {
        return !empty($value) && $value !== '0';
    }
}

// JSON encoder / يحفظ snapshots مثل payment/items بشكل JSON string.
if (!function_exists('eg_db_json_string')) {
    function eg_db_json_string($value, $fallback = '[]') {
        if (is_string($value) && $value !== '') {
            json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? $fallback : $encoded;
    }
}

// JSON decoder / يقرأ items_snapshot ويرجعه array.
if (!function_exists('eg_db_json_array')) {
    function eg_db_json_array($value) {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}

// Fetch records / جلب البيانات من MySQL حسب resource المطلوب.
if (!function_exists('eg_db_fetch_all')) {
    function eg_db_fetch_all($resource) {
        $pdo = eg_db();
        if (!$pdo) {
            return null;
        }

        try {
            if ($resource === 'products') {
                $rows = $pdo->query('SELECT * FROM products ORDER BY created_date DESC')->fetchAll();
                return array_map(function ($row) {
                    return [
                        'id' => $row['id'],
                        'created_date' => eg_mysql_to_iso($row['created_date']),
                        'name' => $row['name'],
                        'description' => $row['description'] ?? '',
                        'price' => (float)$row['price'],
                        'stock' => (int)$row['stock'],
                        'category' => $row['category'],
                        'brand' => $row['brand'] ?? '',
                        'image_url' => $row['image_url'] ?? '',
                        'colors' => $row['colors'] ?? '',
                        'sizes' => $row['sizes'] ?? '',
                        'discount_percent' => (float)$row['discount_percent'],
                        'is_featured' => eg_db_bool($row['is_featured']),
                        'rating' => (float)$row['rating'],
                    ];
                }, $rows);
            }

            if ($resource === 'customers') {
                $rows = $pdo->query('SELECT * FROM customers ORDER BY full_name ASC')->fetchAll();
                return array_map(function ($row) {
                    return [
                        'id' => $row['id'],
                        'full_name' => $row['full_name'],
                        'email' => $row['email'],
                        'password' => $row['password'],
                        'phone' => $row['phone'] ?? '',
                        'address' => $row['address'] ?? '',
                        'city' => $row['city'] ?? '',
                        'role' => $row['role'] ?? 'customer',
                        'is_active' => eg_db_bool($row['is_active']),
                        'total_orders' => (int)$row['total_orders'],
                        'total_spent' => (float)($row['total_spent'] ?? 0),
                    ];
                }, $rows);
            }

            if ($resource === 'admins') {
                $rows = $pdo->query('SELECT * FROM admins ORDER BY user_name ASC')->fetchAll();
                return array_map(function ($row) {
                    return [
                        'id' => $row['id'],
                        'user_email' => $row['user_email'],
                        'user_name' => $row['user_name'],
                        'password' => $row['password'],
                        'permissions' => $row['permissions'] ?? '',
                        'is_active' => eg_db_bool($row['is_active']),
                        'notes' => $row['notes'] ?? '',
                    ];
                }, $rows);
            }

            if ($resource === 'messages') {
                $rows = $pdo->query('SELECT * FROM contact_messages ORDER BY created_date DESC')->fetchAll();
                return array_map(function ($row) {
                    return [
                        'id' => $row['id'],
                        'created_date' => eg_mysql_to_iso($row['created_date']),
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'subject' => $row['subject'] ?? '',
                        'message' => $row['message'],
                    ];
                }, $rows);
            }

            if ($resource === 'orders') {
                $orders = $pdo->query('SELECT * FROM orders ORDER BY created_date DESC')->fetchAll();
                $items = $pdo->query('SELECT * FROM order_items ORDER BY id ASC')->fetchAll();
                $itemsByOrder = [];
                foreach ($items as $item) {
                    $itemsByOrder[$item['order_id']][] = [
                        'id' => $item['product_id'],
                        'name' => $item['product_name'],
                        'quantity' => (int)$item['quantity'],
                        'price' => (float)$item['price'],
                        'selected_size' => $item['selected_size'] ?? '',
                        'selected_color' => $item['selected_color'] ?? '',
                    ];
                }

                return array_map(function ($row) use ($itemsByOrder) {
                    $items = $itemsByOrder[$row['id']] ?? [];
                    return [
                        'id' => $row['id'],
                        'created_date' => eg_mysql_to_iso($row['created_date']),
                        'user_email' => $row['user_email'],
                        'user_name' => $row['user_name'] ?? '',
                        'total_amount' => (float)$row['total_amount'],
                        'status' => $row['status'],
                        'payment_status' => $row['payment_status'],
                        'payment_method' => $row['payment_method'],
                        'payment_snapshot' => $row['payment_snapshot'] ?? '',
                        'shipping_address' => $row['shipping_address'] ?? '',
                        'phone' => $row['phone'] ?? '',
                        'notes' => $row['notes'] ?? '',
                        'items_snapshot' => eg_db_json_string($items),
                    ];
                }, $orders);
            }
        } catch (Throwable $error) {
            return null;
        }

        return null;
    }
}

// Save records / حفظ البيانات في MySQL عند تغييرات admin/cart/contact.
if (!function_exists('eg_db_save_records')) {
    function eg_db_save_records($resource, $records) {
        $pdo = eg_db();
        if (!$pdo || !is_array($records)) {
            return false;
        }

        try {
            $pdo->beginTransaction();

            if ($resource === 'products') {
                $pdo->exec('DELETE FROM products');
                $stmt = $pdo->prepare('INSERT INTO products (id, created_date, name, description, price, stock, category, brand, image_url, colors, sizes, discount_percent, is_featured, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                foreach ($records as $record) {
                    $stmt->execute([
                        $record['id'] ?? ('prod_' . uniqid()),
                        eg_iso_to_mysql($record['created_date'] ?? null),
                        $record['name'] ?? 'Untitled Product',
                        $record['description'] ?? '',
                        (float)($record['price'] ?? 0),
                        (int)($record['stock'] ?? 0),
                        $record['category'] ?? 'Gym',
                        $record['brand'] ?? '',
                        $record['image_url'] ?? '',
                        $record['colors'] ?? '',
                        $record['sizes'] ?? '',
                        (float)($record['discount_percent'] ?? 0),
                        !empty($record['is_featured']) ? 1 : 0,
                        (float)($record['rating'] ?? 0),
                    ]);
                }
            } elseif ($resource === 'customers') {
                $pdo->exec('DELETE FROM customers');
                $stmt = $pdo->prepare('INSERT INTO customers (id, full_name, email, password, phone, address, city, role, is_active, total_orders, total_spent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                foreach ($records as $record) {
                    $stmt->execute([
                        $record['id'] ?? ('cust_' . uniqid()),
                        $record['full_name'] ?? ($record['name'] ?? 'Customer'),
                        $record['email'] ?? '',
                        $record['password'] ?? '',
                        $record['phone'] ?? '',
                        $record['address'] ?? '',
                        $record['city'] ?? '',
                        $record['role'] ?? 'customer',
                        array_key_exists('is_active', $record) ? (!empty($record['is_active']) ? 1 : 0) : 1,
                        (int)($record['total_orders'] ?? 0),
                        (float)($record['total_spent'] ?? 0),
                    ]);
                }
            } elseif ($resource === 'admins') {
                $pdo->exec('DELETE FROM admins');
                $stmt = $pdo->prepare('INSERT INTO admins (id, user_name, user_email, password, permissions, notes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                foreach ($records as $record) {
                    $stmt->execute([
                        $record['id'] ?? ('admin_' . uniqid()),
                        $record['user_name'] ?? 'Admin',
                        $record['user_email'] ?? '',
                        $record['password'] ?? '',
                        $record['permissions'] ?? '',
                        $record['notes'] ?? '',
                        array_key_exists('is_active', $record) ? (!empty($record['is_active']) ? 1 : 0) : 1,
                    ]);
                }
            } elseif ($resource === 'messages') {
                $pdo->exec('DELETE FROM contact_messages');
                $stmt = $pdo->prepare('INSERT INTO contact_messages (id, created_date, name, email, subject, message) VALUES (?, ?, ?, ?, ?, ?)');
                foreach ($records as $record) {
                    $stmt->execute([
                        $record['id'] ?? ('msg_' . uniqid()),
                        eg_iso_to_mysql($record['created_date'] ?? null),
                        $record['name'] ?? '',
                        $record['email'] ?? '',
                        $record['subject'] ?? '',
                        $record['message'] ?? '',
                    ]);
                }
            } elseif ($resource === 'orders') {
                $pdo->exec('DELETE FROM order_items');
                $pdo->exec('DELETE FROM orders');
                // Add city column to orders if it doesn't exist yet
                try { $pdo->exec('ALTER TABLE orders ADD COLUMN city VARCHAR(100) NULL'); } catch (Throwable $e) {}
                $orderStmt = $pdo->prepare('INSERT INTO orders (id, created_date, user_email, user_name, total_amount, status, payment_status, payment_method, payment_snapshot, shipping_address, city, phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, selected_size, selected_color, quantity, price) VALUES (?, ?, ?, ?, ?, ?, ?)');
                foreach ($records as $record) {
                    $orderId = $record['id'] ?? ('ord_' . uniqid());
                    $orderStmt->execute([
                        $orderId,
                        eg_iso_to_mysql($record['created_date'] ?? null),
                        $record['user_email'] ?? '',
                        $record['user_name'] ?? '',
                        (float)($record['total_amount'] ?? 0),
                        $record['status'] ?? 'confirmed',
                        $record['payment_status'] ?? 'paid',
                        $record['payment_method'] ?? 'card',
                        !empty($record['payment_snapshot']) ? eg_db_json_string($record['payment_snapshot'], '{}') : null,
                        $record['shipping_address'] ?? '',
                        $record['city'] ?? '',
                        $record['phone'] ?? '',
                        $record['notes'] ?? '',
                    ]);

                    foreach (eg_db_json_array($record['items_snapshot'] ?? []) as $item) {
                        $itemStmt->execute([
                            $orderId,
                            $item['id'] ?? ($item['product_id'] ?? ''),
                            $item['name'] ?? ($item['product_name'] ?? 'Product'),
                            $item['selected_size'] ?? '',
                            $item['selected_color'] ?? '',
                            (int)($item['quantity'] ?? 1),
                            (float)($item['price'] ?? 0),
                        ]);
                    }
                }

                // ── Recalculate total_orders + total_spent per customer from saved orders ──
                // and also sync address, city, phone from the most recent order.
                $custStats = [];
                $custLatest = [];
                foreach ($records as $record) {
                    $email = $record['user_email'] ?? '';
                    if (!$email) continue;
                    if (!isset($custStats[$email])) {
                        $custStats[$email] = ['total_orders' => 0, 'total_spent' => 0.0];
                        $custLatest[$email] = ['created_date' => '', 'address' => '', 'city' => '', 'phone' => ''];
                    }
                    $custStats[$email]['total_orders'] += 1;
                    $custStats[$email]['total_spent']  += (float)($record['total_amount'] ?? 0);
                    // Track the most recent order for contact info
                    if (($record['created_date'] ?? '') >= $custLatest[$email]['created_date']) {
                        $custLatest[$email] = [
                            'created_date' => $record['created_date'] ?? '',
                            'address'      => $record['shipping_address'] ?? '',
                            'city'         => $record['city'] ?? '',
                            'phone'        => $record['phone'] ?? '',
                        ];
                    }
                }
                $custUpdateStmt = $pdo->prepare(
                    'UPDATE customers SET total_orders = ?, total_spent = ?, address = ?, city = ?, phone = ? WHERE email = ?'
                );
                foreach ($custStats as $email => $stats) {
                    $latest = $custLatest[$email];
                    $custUpdateStmt->execute([
                        $stats['total_orders'],
                        round($stats['total_spent'], 2),
                        $latest['address'],
                        $latest['city'],
                        $latest['phone'],
                        $email,
                    ]);
                }
                // ─────────────────────────────────────────────────────────────────
            } else {
                $pdo->rollBack();
                return false;
            }

            $pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }
}
