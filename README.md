# DOi PC website (v2: database-driven bundles and admin)

## Set up (XAMPP)
1. Copy the project to `C:\xampp\htdocs\commercial-website\` (overwrite matching files).
2. Start Apache and MySQL. In phpMyAdmin, **Import** `database/database.sql` (it creates the `doi_pc` database; it drops existing tables, so use it on sample data only).
3. Copy `config/config.example.php` to `config/config.php` and set the database login and the owner's email.
4. Open `http://localhost/commercial-website/admin/setup.php`, create the admin account, then **delete `admin/setup.php`**.
5. Log in at `/admin/`. Replace the sample inventory and bundles with the shop's real stock.

## Delete these old files
`admin/config.php`, `admin/bundles.json`, `admin/index_1.php` (old admin page), `backend/inquiries.log`, `backend/config.php`, the root `main.js` (now `assets/js/main.js`).

## How stock controls the website
A bundle is made of inventory parts. It shows on the website only when it is switched on, every part is enabled, and every part has enough stock. When a quote linked to a bundle is marked **Converted**, its parts are subtracted from inventory once.

## Before going live
Replace the SAMPLE items in `site.php` (stats, trust badge, rating), the phone, Messenger and WhatsApp links, hours, and the map pin. Add real photos to `assets/img/` (`build-1.jpg` to `build-6.jpg`). Turn on HTTPS and use a strong database password.
