#  BloomTrack — Flower Shop Inventory Management System

##  System Overview

BloomTrack is a complete web-based inventory management system for a flower shop, built with:

- Frontend: HTML5, CSS3, JavaScript (vanilla)
- Backend: PHP 7.4+
- Database: MySQL 5.7+ via XAMPP
- Charts: Chart.js (CDN)
- Icons: Font Awesome 6 (CDN)
- Fonts: Playfair Display + DM Sans (Google Fonts)


##  User Roles

| Role | Username | Default Password | Capabilities |
|---|---|---|---|
| Administrator | `admin` | `password` | Manage all users, view logs, system reports, reply to enquiries |
| Shop Owner | `owner` | `password` | Full inventory CRUD, view all sales, reports, manage staff, restock |
| Staff | `staff` | `password` | Process sales (POS), adjust stock, manage order queue |
| Customer | `customer` | `password` | Browse flowers, add to cart, place orders, track orders, submit enquiries |



##  XAMPP Setup — Step by Step

### Step 1: Install & Start XAMPP

1. Download XAMPP from https://www.apachefriends.org/
2. Install and open XAMPP Control Panel
3. Click Start next to Apache
4. Click Start next to MySQL

### Step 2: Copy Project Files

Copy the `flowershop` folder into your XAMPP web root:

- Windows: `C:\xampp\htdocs\flowershop\`
- macOS: `/Applications/XAMPP/htdocs/flowershop/`
- Linux: `/opt/lampp/htdocs/flowershop/`

### Step 3: Run the Installer

Open your browser and go to:


http://localhost/flowershop/setup.php


This will:
- Create the `flowershop_db` database
- Create all tables (users, flowers, orders, sales, etc.)
- Insert seed/demo data
- Set all demo account passwords to `password`

### Step 4: Log In

Go to: `http://localhost/flowershop/login.php`

Use any of the demo credentials from the table above.

### Authentication
- Secure bcrypt password hashing (`password_hash` / `password_verify`)
- Role-based session management
- Automatic redirect based on role after login
- Account activation/deactivation by admin

### Inventory Management (Owner/Staff)
- Add, edit, deactivate flowers
- Track stock per flower type with reorder level
- Visual stock progress bars (OK / Low / Critical)
- Record restocking, wastage, damage, corrections
- Full adjustment history with before/after quantities

### Sales Processing (Staff POS)
- Select flowers + quantities from live inventory
- Auto-deducts stock on sale confirmation
- Supports cash, M-Pesa, card, bank payment methods
- Unique sale number generation

### Order Management
- Customer places orders via cart (stored in localStorage)
- Server validates against live stock on submission
- Staff/Owner update order status through pipeline
- Customer can track order status with visual progress steps
- Customer can cancel pending orders

### Reports (Owner + Admin)
- Monthly revenue trend (line chart)
- Seasonal demand analysis (bar chart)
- Top selling flowers by units and revenue
- Wastage/damage analysis by flower
- Orders by status (doughnut chart)

### Enquiries
- Customer submits enquiry with subject + message
- Admin replies via admin panel
- Customer sees replies in their enquiry page
- Status tracking: Open → Replied → Closed

##  Configuration

Edit `includes/config.php` to change:

```php
define('DB_HOST', 'localhost');   // MySQL host
define('DB_USER', 'root');        // MySQL username
define('DB_PASS', '');            // MySQL password (empty for XAMPP default)
define('DB_NAME', 'flowershop_db'); // Database name
define('APP_URL', 'http://localhost/flowershop'); // Full URL to your install

## Security Notes

- All user inputs sanitized with `htmlspecialchars`, `strip_tags`, `real_escape_string`
- Passwords stored as bcrypt hashes only
- Role checks on every protected page via `requireRole()`
- SQL injection prevented via MySQLi prepared statements throughout
- Session-based authentication with configurable lifetime

##  Database Schema

| Table | Purpose |
|---|---|
| `users` | All user accounts (admin, owner, staff, customer) |
| `categories` | Flower categories (Roses, Lilies, etc.) |
| `flowers` | Flower inventory with stock levels |
| `orders` | Customer orders |
| `order_items` | Line items for each order |
| `sales` | Completed sales transactions |
| `stock_adjustments` | Audit log of all stock changes |
| `enquiries` | Customer support messages + replies |
| `activity_log` | System-wide audit trail |
