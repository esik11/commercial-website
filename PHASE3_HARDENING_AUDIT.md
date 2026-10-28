# Phase 3: Security Hardening Audit

**Audit Date:** October 26, 2026  
**Auditor:** Automated Security Review

---

## 1. Prepared Statements for User Input ✅

### Status: **PASS**

**Findings:**
- All user input queries use prepared statements with parameter binding
- No direct query concatenation with user variables found
- Queries without parameters use `->query()` safely (e.g., static SQL for categories)

**Examples of Proper Usage:**
```php
// backend/send-quote.php
$st = $db->prepare('SELECT name FROM bundles WHERE id = ? AND is_active = 1');
$st->execute([$bundleId]);

// admin/quotes.php
$st = $db->prepare('SELECT * FROM quote_requests WHERE id = ?');
$st->execute([$id]);

// admin/inventory.php
$db->prepare('UPDATE hardware_items SET stock_quantity = ? WHERE id = ?')
   ->execute([(int)$s, $id]);
```

**Safe `->query()` Usage (No User Input):**
- `admin/index.php` - Static queries for dashboard stats
- `admin/inventory.php` - Fetching category list
- `admin/setup.php` - Counting admin users (no parameters)
- `admin/login.php` - Counting admin users (no parameters)

✅ **No SQL injection vulnerabilities found**

---

## 2. HTML Escaping with e() Function ✅

### Status: **PASS**

**Findings:**
- `e()` function properly defined in `includes/auth.php`:
  ```php
  function e($v): string { 
      return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); 
  }
  ```

- Admin templates use `e()` consistently:
  - All `<?= e($variable) ?>` patterns in admin panels
  - Form values escaped: `value="<?= e($v('price')) ?>"`
  - User data escaped before display

- Public pages (`index.php`, `site.php`) use similar escaping patterns

✅ **No XSS vulnerabilities found in output**

---

## 3. CSRF Protection on Admin POST Requests ✅

### Status: **PASS**

**Findings:**

All admin POST handlers call `csrf_require()`:
- ✅ `admin/setup.php` - Line 10
- ✅ `admin/login.php` - Line 10
- ✅ `admin/password.php` - Line 6
- ✅ `admin/inventory.php` - Line 9
- ✅ `admin/bundles.php` - Line 6
- ✅ `admin/bundle_edit.php` - Line 18
- ✅ `admin/quotes.php` - Line 8

**Exception (Allowed):**
- ✅ `admin/logout.php` - Uses `csrf_valid()` check (soft validation, appropriate for logout)

**Backend:**
- ✅ `backend/send-quote.php` - Uses `csrf_valid()` with 403 response

✅ **All POST requests properly protected**

---

## 4. Admin Authentication with requireAdmin() ✅

### Status: **PASS**

**Findings:**

All admin pages call `requireAdmin()`:
- ✅ `admin/index.php` - Line 3
- ✅ `admin/inventory.php` - Line 3
- ✅ `admin/bundles.php` - Line 3
- ✅ `admin/bundle_edit.php` - Line 3
- ✅ `admin/quotes.php` - Line 3
- ✅ `admin/password.php` - Line 3 (with `requireAdmin(true)` for password change enforcement)

**Exceptions (By Design):**
- ✅ `admin/setup.php` - Only accessible when no admin exists
- ✅ `admin/login.php` - Login page (obviously)
- ✅ `admin/logout.php` - Logout handler
- ✅ `admin/_boot.php` - Bootstrap file (not directly accessible)
- ✅ `admin/_top.php`, `admin/_bottom.php` - Template includes

✅ **All admin pages properly protected**

---

## 5. Protected Directory Access ⚠️

### Status: **PARTIAL** - Missing root .htaccess

**Findings:**

**✅ Protected Directories:**
- `config/.htaccess` - Blocks all access
- `includes/.htaccess` - Blocks all access
- `database/.htaccess` - Blocks all access
- `assets/uploads/.htaccess` - Blocks script execution, disables directory listing

**Content of protection files:**
```apache
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>
```

**❌ Missing: Root .htaccess**
- No root `.htaccess` file found
- Missing `Options -Indexes` directive for project root
- Missing security headers

**Action Required:** Create root `.htaccess` file (see fixes below)

---

## 6. Upload Security ✅

### Status: **PASS**

**Findings:**

`assets/uploads/.htaccess` properly configured:
```apache
# Uploaded files are images only: never execute scripts here
<FilesMatch "\.(php|phtml|phar|pl|py|cgi)$">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
</FilesMatch>
Options -Indexes
```

**File Upload Validation in `admin/bundle_edit.php`:**
- ✅ MIME type validation: `['image/jpeg', 'image/png', 'image/webp']`
- ✅ Size limit: 2MB max
- ✅ Random filename generation: `uniqid('bundle_', true) . $ext`
- ✅ Extension validation based on MIME type
- ✅ Old files cleaned up on replacement

✅ **Upload security properly implemented**

---

## 7. Error Display Configuration ⚠️

### Status: **NEEDS CONFIGURATION**

**Findings:**

**Current State:**
- No `ini_set('display_errors', '0')` found in code
- Relies on php.ini configuration
- Admin debug mode implemented in `admin/_boot.php`:
  ```php
  $debug = (cfg('app', [])['debug'] ?? false);
  echo 'Something went wrong...';
  if ($debug) { echo '<pre>' . e($ex->getMessage()) . '</pre>'; }
  ```

**Issues:**
- ❌ No explicit `display_errors` control in code
- ❌ No `error_reporting` configuration
- ⚠️ Depends on server php.ini settings

**Action Required:** 
- Add error display configuration to bootstrap files
- Ensure production php.ini has `display_errors = Off`
- Add to php.ini or .htaccess (see fixes below)

---

## 8. Apache AllowOverride Configuration

### Current Setup Check

To verify if your Apache honors .htaccess files, check your Apache configuration:

**Test Command:**
```bash
curl -I http://localhost/commercial-website/config/config.php
```

**Expected Results:**
- ✅ HTTP 403 Forbidden = .htaccess working
- ❌ HTTP 200 OK = .htaccess ignored (AllowOverride None)

**If .htaccess is ignored:**

Edit your Apache configuration file (typically `C:\xampp\apache\conf\httpd.conf` or `C:\xampp\apache\conf\extra\httpd-vhosts.conf`):

**Find this section:**
```apache
<Directory "C:/xampp/htdocs">
    AllowOverride None
    Require all granted
</Directory>
```

**Change to:**
```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
    Require all granted
</Directory>
```

**Then restart Apache:**
```bash
# Windows (XAMPP Control Panel)
Stop Apache -> Start Apache

# Or via command line
httpd -k restart
```

---

## Required Fixes

### FIX 1: Create Root .htaccess

Create `.htaccess` in project root:

```apache
# Security headers
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable directory listing
Options -Indexes

# Hide sensitive files
<FilesMatch "(^\.htaccess|^\.git|composer\.(json|lock)|package\.json|\.md$)">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
  </IfModule>
</FilesMatch>

# PHP error handling (if AllowOverride allows)
<IfModule mod_php7.c>
  php_flag display_errors Off
  php_flag display_startup_errors Off
  php_value error_reporting 32767
  php_flag log_errors On
</IfModule>
```

### FIX 2: Add Error Configuration to Bootstrap

Add to `admin/_boot.php` (after line 7):

```php
// Production error handling
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
```

Add to `index.php` and `site.php` (at top, after `<?php`):

```php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
```

### FIX 3: Add Security Headers to Public Pages

Add to `index.php` and `site.php` (after error config):

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
```

---

## Summary

| Check | Status | Notes |
|-------|--------|-------|
| Prepared statements | ✅ PASS | All user input properly parameterized |
| HTML escaping with e() | ✅ PASS | Consistent usage across templates |
| CSRF on admin POST | ✅ PASS | All handlers protected |
| requireAdmin() calls | ✅ PASS | All admin pages protected |
| Protected directories | ⚠️ PARTIAL | .htaccess present, need root file |
| Upload security | ✅ PASS | MIME check, size limit, random names |
| Error display | ⚠️ NEEDS FIX | Add ini_set() controls |
| Directory listing | ⚠️ NEEDS FIX | Add Options -Indexes to root |

**Critical Issues:** 0  
**Warnings:** 3 (all related to missing root .htaccess and error config)  
**Recommendations:** Apply the 3 fixes above

---

## Next Steps

1. ✅ Create root `.htaccess` file (FIX 1)
2. ✅ Add error configuration to all bootstrap files (FIX 2)
3. ✅ Add security headers to public pages (FIX 3)
4. ✅ Verify Apache AllowOverride is set to "All"
5. ✅ Test protected directory access after changes
6. ✅ Review php.ini for production settings

**After applying fixes, re-run:** `php final_test.php` (Tests 9-10 should pass)
