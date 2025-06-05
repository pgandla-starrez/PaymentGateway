<?php

// tests/bootstrap.php

// Enable strict types for all test files (optional, but good practice)
declare(strict_types=1);

// Composer autoloader
$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

if (!$autoloader) {
    echo "Composer autoloader not found. Run 'composer install'.\n";
    exit(1);
}

// You can add any other global test setup here if needed
// For example, loading environment variables for tests:
// if (class_exists(Dotenv\Dotenv::class) && file_exists(dirname(__DIR__) . '/.env.testing')) {
//     $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env.testing');
//     $dotenv->load();
// } 