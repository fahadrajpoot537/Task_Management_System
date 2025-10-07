<?php
// Debug script to identify the exact error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Laravel Debug Information</h2>";

// Check PHP version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Check if required extensions are loaded
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
echo "<p><strong>Required Extensions:</strong></p><ul>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    echo "<li>{$ext}: {$status}</li>";
}
echo "</ul>";

// Check file permissions
echo "<p><strong>File Permissions:</strong></p><ul>";
$directories = ['storage', 'bootstrap/cache'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<li>{$dir}: {$perms}</li>";
    } else {
        echo "<li>{$dir}: Directory not found</li>";
    }
}
echo "</ul>";

// Check if .env exists
echo "<p><strong>.env File:</strong> " . (file_exists('.env') ? '✓ Exists' : '✗ Missing') . "</p>";

// Check if vendor directory exists
echo "<p><strong>Vendor Directory:</strong> " . (is_dir('vendor') ? '✓ Exists' : '✗ Missing') . "</p>";

// Try to load Laravel
echo "<h3>Laravel Loading Test:</h3>";
try {
    require_once __DIR__.'/../vendor/autoload.php';
    echo "<p>✓ Autoloader loaded successfully</p>";
    
    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo "<p>✓ Laravel app loaded successfully</p>";
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "<p>✓ Kernel created successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p><pre>" . $e->getTraceAsString() . "</pre>";
}

// Check database connection
echo "<h3>Database Connection Test:</h3>";
try {
    if (file_exists('.env')) {
        $env = file_get_contents('.env');
        preg_match('/DB_HOST=(.*)/', $env, $host);
        preg_match('/DB_DATABASE=(.*)/', $env, $database);
        preg_match('/DB_USERNAME=(.*)/', $env, $username);
        preg_match('/DB_PASSWORD=(.*)/', $env, $password);
        
        $host = trim($host[1] ?? 'localhost');
        $database = trim($database[1] ?? '');
        $username = trim($username[1] ?? '');
        $password = trim($password[1] ?? '');
        
        $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
        echo "<p>✓ Database connection successful</p>";
    } else {
        echo "<p>✗ .env file not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
}
?>
