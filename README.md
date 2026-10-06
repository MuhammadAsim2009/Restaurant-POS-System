# 🍔 Restaurant POS System

A modern, fast, and feature-rich **Restaurant Point of Sale (POS) & Management System** built with PHP, MySQL, Vanilla JavaScript, and a sleek glassmorphism dark-theme UI. Designed for high efficiency in fast food restaurants, cafés, and dine-in establishments.

---

## ✨ Features

### 🛒 1. POS & Ordering Terminal (`pos.php`)
- **Multi-Tab Cart Support**: Run and switch between multiple customer orders simultaneously without losing progress.
- **Dynamic Categories & Items**: Instant category filtering, real-time item search, and category-themed icons.
- **Order Types**: Supports **Dine-In**, **Takeaway**, and **Delivery**.
- **Table Selector Validation**: Automatic validation ensuring Dine-In orders have an assigned table before checkout.
- **Multi-Payment Gateway Support**: Cash, Card, Easypaisa, and JazzCash with automatic change calculations and validation.
- **Thermal Receipt Generator**: Instant receipt generation with print support.

### 🪑 2. Table Management (`tables.php`)
- Visual floor plan with real-time status indicators (**Available**, **Occupied**, **Reserved**).
- Full CRUD operations: Add new tables with custom names and seating capacities, edit, and delete tables.

### 🍽️ 3. Menu & Category Management (`menu.php`)
- **Category CRUD**: Create, edit, and delete food categories with customizable food icons.
- **Menu Item CRUD**: Add, edit, or delete dishes with pricing, descriptions, and categories.
- **Instant Availability Toggle**: Switch item availability on the fly without refreshing the page.

### 👨‍🍳 4. Kitchen Display System (KDS) (`kitchen.php`)
- Real-time kitchen view displaying live incoming tickets.
- One-click order state advancement: **Pending ➔ Preparing ➔ Ready ➔ Delivered**.

### 🧾 5. Order History & Management (`orders.php`)
- Complete historical record of all transactions.
- Filter by date range, order status, and order type.
- View detailed order breakdowns and reprint receipts anytime.

### 📈 6. Reports & Analytics (`reports.php`)
- Sales performance overview (Revenue, Total Orders, Average Order Value).
- Interactive charts (Revenue trends, Category distributions, Top-selling items).
- Date presets (**Today**, **This Week**, **This Month**, Custom Range).
- One-click **CSV Data Export**.

### 🔐 7. Security & User Roles
- Role-Based Access Control (**Manager** and **Cashier**).
- Secure authentication with session guards and `bcrypt` password hashing.

---

## 🛠️ Tech Stack

- **Backend:** PHP 7.4+ / PHP 8.x (Pure PHP, PDO Prepared Statements)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3 (Modern Glassmorphism & Custom Properties), Vanilla JavaScript (ES6+)
- **Icons & Fonts:** FontAwesome 6, Google Fonts (Inter & Outfit)
- **Charts:** Chart.js

---

## 🚀 Getting Started / Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (or WampServer / LAMP / LEMP stack with PHP 7.4+ and MySQL)

### Step 1: Clone or Download Repository
Clone the repository into your web server's root directory (`htdocs` for XAMPP):
```bash
git clone https://github.com/MuhammadAsim2009/Restaurant-POS-System.git Resturent_POS
```
*(Directory path: `xampp/htdocs/Resturent_POS`)*

### Step 2: Database Setup
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Click on **Import** tab.
4. Choose the SQL file located at:
   ```
   db/schema.sql
   ```
5. Click **Import**. This creates the `restaurant_pos` database with all required tables and seed data.

### Step 3: Database Configuration (Optional)
If your MySQL credentials differ from the standard XAMPP defaults (`root` with no password), update [`config/database.php`](config/database.php):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_pos');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 4: Launch the Application
Open your web browser and navigate to:
```
http://localhost/Resturent_POS/
```

---

## 🔑 Default Credentials

| Role | Username | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Manager** | `manager` | `password` | Full Access (Dashboard, Menu, Reports, POS, Tables, Kitchen) |
| **Cashier** | `cashier1` | `password` | POS Terminal, Orders, Kitchen & Tables |

---

## 📁 Project Structure

```
Resturent_POS/
├── api/                  # RESTful JSON API endpoints
│   ├── auth.php          # Login, logout & session checks
│   ├── dashboard.php     # KPI metrics & charts data
│   ├── kitchen.php       # KDS live status & ticket updates
│   ├── menu.php          # Category & Menu CRUD
│   ├── orders.php        # Order placement, filtering & history
│   ├── reports.php       # Sales aggregation & analytics
│   └── tables.php        # Table floor plan & status updates
├── config/
│   ├── database.php      # PDO database connection
│   └── session.php       # Auth guards & role-based checks
├── css/
│   └── style.css         # Dark theme & glassmorphic design system
├── db/
│   └── schema.sql        # Database schema & initial seed data
├── js/
│   └── app.js            # Shared frontend API helper & utility methods
├── partials/
│   └── sidebar.php       # Global navigation sidebar component
├── dashboard.php         # Analytics and KPIs overview
├── index.php             # Login & authentication portal
├── kitchen.php           # Kitchen display system
├── menu.php              # Food & category management
├── orders.php            # Order logs & reprint receipt
├── pos.php               # Point of Sale terminal
├── reports.php           # Sales analytics & reports
└── tables.php            # Table layout management
```

---

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).
