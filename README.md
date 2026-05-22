# EliteGear-Project--web-Based-Course

# EliteGear Localhost PHP Project

This project is a plain localhost version of the EliteGear storefront.

## Run locally

Use XAMPP, MAMP, Laragon, or PHP's built-in server from this folder:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/index.php
```

## Demo logins

Admin:

```text
admin@elitegear.com
admin123
```

Customer:

```text
customer@elitegear.com
customer123
```

Cart state uses browser localStorage. Products, orders, customers, admins, and contact messages sync through `api.php` into MySQL when XAMPP MySQL is running, with JSON files kept as a local fallback.

## Database note

The project connects to a local MySQL/phpMyAdmin database named `elitegear_store` using the default XAMPP account:

```text
host: 127.0.0.1
user: root
password: empty
```

Start MySQL in XAMPP, then open phpMyAdmin and import:

```text
database/elitegear_store.sql
```

That creates and fills the products, orders, order items, customers, admins, and contact messages tables with the project demo data. If MySQL is running and the database is empty, the PHP app can also create the tables and seed them from the JSON files automatically on first load.
