# 🗄️ Database Setup Guide - DOi PC System

## Step 1: Create the Database

### Option A: Using phpMyAdmin (Easiest)
1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Click on **"New"** in the left sidebar
3. Database name: `doi_pc`
4. Collation: `utf8mb4_general_ci`
5. Click **"Create"**

### Option B: Using MySQL Command Line
```sql
CREATE DATABASE doi_pc CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

## Step 2: Import Database Schema

### Using phpMyAdmin:
1. Select the `doi_pc` database from left sidebar
2. Click **"Import"** tab at the top
3. Click **"Choose File"** 
4. Select: `C:\xampp\htdocs\commercial-website\admin\database.sql`
5. Click **"Go"** at the bottom
6. Wait for "Import has been successfully finished" message

### Using MySQL Command Line:
```bash
mysql -u root -p doi_pc < C:\xampp\htdocs\commercial-website\admin\database.sql
```

## Step 3: Verify Database Setup

1. In phpMyAdmin, select `doi_pc` database
2. You should see these tables:
   - `admin_users` (1 row - admin account)
   - `hardware_categories` (10 rows - CPU, GPU, etc.)
   - `hardware_items` (sample inventory)
   - `bundles` (5 sample bundles)
   - `bundle_components`
   - `custom_builds`
   - `custom_build_items`
   - `quote_requests`

## Step 4: Update Configuration

Open `admin/config.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'doi_pc');
define('DB_USER', 'root');
define('DB_PASS', ''); // Add password if you have one
```

## Step 5: Test the System

1. **Admin Login**: 
   - URL: `http://localhost/commercial-website/admin/`
   - Username: `admin`
   - Password: `changeme123`

2. **What You Can Do Now:**
   - ✅ Manage hardware inventory (Add/Edit/Delete items)
   - ✅ Track stock levels
   - ✅ Create/Edit bundles
   - ✅ View quote requests
   - ✅ Manage custom builds

## Features Now Available:

### For Admin:
1. **Inventory Management**
   - Add new hardware (CPUs, GPUs, RAM, etc.)
   - Update stock quantities
   - Set prices
   - Mark items as featured
   - Disable out-of-stock items

2. **Bundle Management**
   - Create pre-built bundles
   - Link actual hardware items to bundles
   - Set stock status (In Stock/Low Stock/Out of Stock)
   - Enable/Disable bundles

3. **Custom Build Management**
   - View customer PC builder orders
   - Track build status
   - See component selections

4. **Quote Management**
   - View all quote requests
   - Update status
   - Track conversions

### For Customers:
1. **Browse Hardware**
   - See all available components
   - Filter by category
   - Check stock availability
   - Compare prices

2. **Custom PC Builder**
   - Select components step-by-step
   - See real-time pricing
   - Check compatibility warnings
   - Get total cost instantly

3. **Pre-Built Bundles**
   - Quick selection
   - See what's in stock
   - Request quote

## Sample Data Included:

### CPUs (5 items):
- Intel i3-12100 (₱6,500) - 10 in stock
- Intel i5-12400 (₱9,500) - 8 in stock
- Intel i7-12700K (₱18,500) - 5 in stock
- AMD Ryzen 5 5600 (₱8,500) - 12 in stock
- AMD Ryzen 7 5800X (₱16,500) - 6 in stock

### GPUs (5 items):
- NVIDIA GTX 1650 (₱12,000) - 15 in stock
- NVIDIA RTX 3060 (₱22,000) - 8 in stock
- NVIDIA RTX 4060 Ti (₱35,000) - 4 in stock
- AMD RX 6600 (₱18,000) - 10 in stock
- AMD RX 7700 XT (₱32,000) - 5 in stock

### Other Components:
- RAM: 3 options
- Storage: 3 options
- More categories ready for you to add!

## Next Steps:

1. ✅ **Run the SQL** to create database
2. ✅ **Login to admin** panel
3. 📝 **Add your actual inventory**
4. 🎨 **Customize bundles**
5. 🚀 **Launch to customers**

## Troubleshooting:

### "Database connection failed"
- Make sure XAMPP MySQL is running
- Check credentials in `admin/config.php`
- Verify database name is `doi_pc`

### "Table doesn't exist"
- Import the SQL file again
- Check phpMyAdmin for errors

### "Access denied"
- Default login: admin / changeme123
- Check `admin_users` table exists

## Security Recommendations:

Before going live:
1. Change admin password
2. Use strong database password
3. Enable HTTPS
4. Backup database regularly
5. Limit admin panel access by IP

---

**Need help?** The system is now ready for full inventory and custom PC builder features!
