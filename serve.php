<?php

// Custom Laravel development server without logging issues
$host = '127.0.0.1';
$port = 8000;

echo "Starting Laravel development server...\n";
echo "Server running on http://{$host}:{$port}\n";
echo "Press Ctrl+C to stop the server\n\n";

// Start the server without logging
$command = "php -S {$host}:{$port} -t public";
passthru($command);
