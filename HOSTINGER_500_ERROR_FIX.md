# HOSTINGER DEPLOYMENT TROUBLESHOOTING GUIDE

## Issue: All PHP files returning 500 errors with empty response

### Probable Causes:
1. **Wrong directory structure** - Files uploaded to wrong location
2. **Missing Laravel files** - Critical Laravel files not uploaded
3. **File permissions** - Incorrect file/folder permissions
4. **Missing .htaccess** - Laravel routing not working

### IMMEDIATE CHECKS NEEDED:

#### 1. Directory Structure Check
Your files should be in the correct Hostinger directory:
- **For subdomain (laravelv2.turamunicipalboard.com)**: Files go in `/domains/turamunicipalboard.com/laravelv2/` 
- **NOT in the main public_html folder**

#### 2. Required Files Checklist
Verify these files exist in your subdomain folder:
```
✓ index.php (Laravel entry point)
✓ .htaccess (Laravel routing rules)
✓ vendor/ (entire folder from ZIP)
✓ app/ (entire folder)
✓ bootstrap/ (entire folder)
✓ config/ (entire folder)
✓ routes/ (entire folder)
✓ composer.json
✓ composer.lock
✓ .env (environment file)
```

#### 3. File Permissions
Set these permissions via Hostinger File Manager:
```
Folders: 755 (rwxr-xr-x)
PHP files: 644 (rw-r--r--)
.htaccess: 644 (rw-r--r--)
```

#### 4. Laravel .htaccess Content
Your .htaccess file should contain:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### 5. Laravel public/.htaccess Content
Your public/.htaccess file should contain:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### IMMEDIATE ACTIONS:

1. **Check file location** - Log into Hostinger File Manager, navigate to your subdomain folder
2. **Verify file structure** - Ensure all Laravel files are in the subdomain root (not public_html)
3. **Check .env file** - Make sure .env exists and has correct database settings
4. **Test file permissions** - Set folders to 755, files to 644

### If basic PHP still doesn't work:
- Contact Hostinger support - there may be a server-level PHP configuration issue
- Check if your hosting plan supports PHP 8.2
- Verify the subdomain is properly configured

### Test Commands After Fixing:
```bash
curl "https://laravelv2.turamunicipalboard.com/basic_test.php"
curl "https://laravelv2.turamunicipalboard.com/api/admit-card/health"
```