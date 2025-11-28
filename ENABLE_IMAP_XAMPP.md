# Enable IMAP Extension in XAMPP (Windows)

## Problem
You're getting the error: `Call to undefined function imap_open()` because the PHP IMAP extension is not enabled.

## Solution: Enable IMAP Extension

### Step 1: Locate php.ini File
Your `php.ini` file is located at:
```
C:\xampp\php\php.ini
```

### Step 2: Edit php.ini

1. **Open** `C:\xampp\php\php.ini` in a text editor (Notepad++ or similar)
   - **Important**: You may need to run the editor as Administrator

2. **Search** for the line containing `extension=imap` (use Ctrl+F)
   - It will likely be commented out with a semicolon: `;extension=imap`

3. **Remove the semicolon** to uncomment it:
   ```ini
   ;extension=imap    ← Change this
   extension=imap    ← To this
   ```

4. **Also check** for these related extensions and enable them if commented:
   ```ini
   extension=imap
   extension=openssl
   extension=mbstring
   ```

5. **Save** the file

### Step 3: Restart Apache

1. Open **XAMPP Control Panel**
2. **Stop** Apache
3. **Start** Apache again

### Step 4: Verify IMAP is Enabled

Run this command to verify:
```bash
php -m | findstr imap
```

You should see `imap` in the output. If not, check the steps above.

### Alternative: If extension=imap doesn't exist

If you can't find `extension=imap` in your php.ini, you may need to:

1. **Check if IMAP DLL exists**:
   - Look in `C:\xampp\php\ext\` folder
   - You should see `php_imap.dll`

2. **If php_imap.dll exists**, add this line to php.ini:
   ```ini
   extension=php_imap.dll
   ```

3. **If php_imap.dll doesn't exist**, you may need to:
   - Download the correct PHP version's IMAP extension
   - Or reinstall XAMPP with IMAP support

### Step 5: Test Again

After enabling IMAP and restarting Apache, test the email sync:
```bash
php artisan emails:sync --limit=5
```

## Troubleshooting

### Still getting "undefined function" error?

1. **Check PHP version compatibility**:
   ```bash
   php -v
   ```
   Make sure the IMAP DLL matches your PHP version

2. **Check for errors in php.ini**:
   ```bash
   php -i | findstr "Loaded Configuration File"
   ```
   Then check the file for syntax errors

3. **Verify extension directory**:
   In php.ini, check that `extension_dir` points to the correct folder:
   ```ini
   extension_dir = "C:\xampp\php\ext"
   ```

4. **Check Apache error logs**:
   Look in `C:\xampp\apache\logs\error.log` for any PHP extension loading errors

### Common Issues

- **Permission denied**: Run text editor as Administrator
- **Extension not found**: Verify `php_imap.dll` exists in `C:\xampp\php\ext\`
- **Still not working**: Restart your computer after making changes

## Quick Test Script

Create a file `test_imap.php` in your project root:

```php
<?php
if (function_exists('imap_open')) {
    echo "IMAP extension is ENABLED ✓\n";
} else {
    echo "IMAP extension is NOT ENABLED ✗\n";
    echo "Please follow the steps above to enable it.\n";
}
```

Run it:
```bash
php test_imap.php
```

