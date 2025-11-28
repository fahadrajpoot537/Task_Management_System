<?php
/**
 * Quick IMAP Extension Test
 * Run: php test_imap.php
 */

echo "=== PHP IMAP Extension Test ===\n\n";

// Check if IMAP function exists
if (function_exists('imap_open')) {
    echo "✓ IMAP extension is ENABLED\n";
    echo "✓ You can use the email sync feature\n";
} else {
    echo "✗ IMAP extension is NOT ENABLED\n";
    echo "\n";
    echo "To enable IMAP in XAMPP:\n";
    echo "1. Open: C:\\xampp\\php\\php.ini\n";
    echo "2. Find: ;extension=imap\n";
    echo "3. Remove the semicolon: extension=imap\n";
    echo "4. Save the file\n";
    echo "5. Restart Apache in XAMPP Control Panel\n";
    echo "6. Run this test again\n";
}

echo "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "PHP ini file: " . php_ini_loaded_file() . "\n";

// Check if extension directory exists
$extDir = ini_get('extension_dir');
echo "Extension directory: " . ($extDir ?: 'Not set') . "\n";

// Check if php_imap.dll exists
$imapDll = $extDir . DIRECTORY_SEPARATOR . 'php_imap.dll';
if (file_exists($imapDll)) {
    echo "✓ php_imap.dll found at: $imapDll\n";
} else {
    echo "✗ php_imap.dll NOT found\n";
    echo "  Expected location: $imapDll\n";
    echo "  You may need to download the IMAP extension for your PHP version\n";
}

echo "\n";

