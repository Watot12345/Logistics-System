# Logistics Management System (Laravel Edition)

Modern, cloud-ready Logistics, Fleet, Inventory, and Delivery Dispatch management system built with **Laravel 13**, **Tailwind CSS**, and **SQLite / MySQL**.

## 🚀 Features

- **Operations Dashboard**: Real-time KPI cards for fleet readiness, active shipments, warehouse inventory levels, and maintenance alerts.
- **Fleet Management**: Asset tracking, vehicle health conditions, maintenance alert ticketing, and mechanic assignments.
- **Shipments & Dispatch**: Real-time delivery status updates (`pending`, `in_transit`, `delivered`, `delayed`), address tracking, and driver assignments.
- **Warehouse Inventory**: SKU tracking, stock replenishment alerts (`in_stock`, `low_stock`, `out_of_stock`), and vendor management.
- **Role-Based Authentication**: Built-in support for 6 organizational roles (`admin`, `fleet_manager`, `dispatcher`, `driver`, `mechanic`, `employee`).
- **Render Ready**: Fully dockerized matching the `Barangay v2` architecture with automated zero-config SQLite migrations and seeders on deploy.

---

## 👥 Demo User Accounts

| Role | Username / Email | Password |
|---|---|---|
| **Admin** | `joshua` / `asierr389@gmail.com` | `joshua` |
| **Fleet Manager** | `jay jay` / `jayjay@gmail.com` | `jayjay123` |
| **Dispatcher** | `jon jon` / `jonjon@gmail.com` | `jonjon123` |
| **Driver** | `john john` / `johnjohn@gmail.com` | `johnjohn123` |
| **Mechanic** | `john doe` / `johndoe@gmail.com` | `johndoe123` |
| **Employee** | `asierra389@gmail.com` | `Joshua123` |

---

## 🛠️ Local Development

### 1. Requirements
- PHP 8.2+
- Composer
- Node.js & npm (optional for Vite)

### 2. Setup
```bash
# Install PHP dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Run migrations and seed data
touch database/database.sqlite
php artisan migrate --seed

# Launch development server
php artisan serve
```
Visit `http://127.0.0.1:8000` and sign in with any of the demo accounts above.

---

## ☁️ Deployment on Render

This project is configured to deploy with zero database configuration on Render using **SQLite** (same architecture as `Barangay v2`):

1. Push this repository to GitHub.
2. In the [Render Dashboard](https://dashboard.render.com/):
   - Click **New +** -> **Blueprint**.
   - Connect your GitHub repository.
   - Render detects `render.yaml` automatically and configures Docker, port binding, and database initialization.
3. Once deployed, your site is immediately live with all tables and demo accounts ready!

---

## 🗄️ Database Flexibility
- **Default (Cloud & Local):** `DB_CONNECTION=sqlite` (Zero configuration, zero extra server costs).
- **External MySQL (Optional):** To use an external MySQL database (e.g., Railway or Aiven), simply change in `.env`:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=your-mysql-host
  DB_PORT=3306
  DB_DATABASE=logistics
  DB_USERNAME=your-user
  DB_PASSWORD=your-pass
  ```
  Laravel's Eloquent handles both seamlessly!
