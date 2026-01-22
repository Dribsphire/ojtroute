# Authentication Middleware

This directory contains authentication middleware files to protect your pages from unauthorized access.

## Files

- **`requireAuth.php`** - Requires any authenticated user (admin, student, or instructor)
- **`requireAdmin.php`** - Requires admin role specifically

## Usage

### For Admin Pages

Add this at the very top of your PHP file (before any HTML output):

```php
<?php
// Require admin authentication
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
?>
<!DOCTYPE html>
...
```

### For Any Authenticated User Pages

```php
<?php
// Require authentication (any role)
require_once __DIR__ . '/../../app/middleware/requireAuth.php';
?>
<!DOCTYPE html>
...
```

## What It Does

1. **Starts session** if not already started
2. **Sets no-cache headers** to prevent browser back button access after logout
3. **Checks authentication** - redirects to login if not authenticated
4. **Checks role** (for requireAdmin) - redirects if not admin
5. **Destroys session** and redirects if authentication fails

## Security Features

- ✅ Prevents browser caching of protected pages
- ✅ Properly destroys sessions on logout
- ✅ Regenerates session IDs to prevent session fixation
- ✅ Clears session cookies
- ✅ Redirects unauthorized users immediately

## Example: Admin Page

```php
<?php
// Require admin authentication
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin Dashboard</title>
</head>
<body>
    <!-- Your admin page content -->
</body>
</html>
```

## Notes

- Always include the middleware **before any HTML output**
- The middleware will automatically redirect unauthorized users
- No need to manually check authentication after including the middleware
- Session is automatically destroyed if authentication fails


